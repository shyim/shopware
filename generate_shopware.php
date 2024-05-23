<?php

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: PHP'
        ]
    ]
];
$context = stream_context_create($opts);
$shopwareTags = json_decode(
    file_get_contents('https://api.github.com/repos/shopware/platform/tags', false, $context),
    true
);

if ($shopwareTags === null) {
    die('cannot fetch shopware tags');
}

$shopwareVersions = $shopwareVersions = json_decode(
    file_get_contents('https://update-api.shopware.com/v1/releases/install?major=6'),
    true
);;

$findShopwareDL = static function ($version) use ($shopwareVersions): string {
    foreach ($shopwareVersions as $shopwareVersion) {
        if ($shopwareVersion['version'] === $version) {
            return $shopwareVersion['uri'];
        }
    }

    return "";
};

$phpMatrix = [
    '6.5.0.0' => ['8.1', '8.2'],
    '6.4.18.0' => ['8.1', '8.2'],
    '6.4.7.0' => ['8.1'],
    'default' => [],
];
$phpIndex = json_decode(file_get_contents('index_php.json'), true);
$usedTags = [];


$workflow = <<<YML
name: Build Shopware
on:
  workflow_dispatch:
  push:
    branches:
      - main
    paths:
      - ".github/workflows/shopware.yml"
      - "version.txt"
jobs:
YML;

exec('rm -rf shopware');

foreach ($shopwareTags as $shopwareTag) {
    $dockerTpl = file_get_contents('Dockerfile.template');
    $tagName = ltrim($shopwareTag['name'], 'v');

    // skip rc versions
    if (str_contains($tagName, '-rc')) {
        continue;
    }

    // skip very old versions
    if (version_compare('6.4.10.0', $tagName, '>')) {
        continue;
    }

    if (version_compare('6.5.0.0', $tagName, '<=')) {
        $dockerTpl = file_get_contents('Dockerfile_65.template');
    }

    $versionTags = [];

    if (!isset($usedTags['latest'])) {
        $usedTags['latest'] = 1;
        $versionTags[] = 'latest';
    }

    preg_match('/^\d+\.\d+/', $tagName, $majorVersion);
    preg_match('/^\d+\.\d+.\d+/', $tagName, $minorVersion);

    if (!isset($usedTags[$majorVersion[0]])) {
        $versionTags[] = $majorVersion[0];
        $usedTags[$majorVersion[0]] = 1;
    }

    if (!isset($usedTags[$minorVersion[0]])) {
        $versionTags[] = $minorVersion[0];
        $usedTags[$minorVersion[0]] = 1;
    }

    if (!isset($usedTags[$tagName])) {
        $versionTags[] = $tagName;
    }

    $phpVersions = [];

    foreach ($phpMatrix as $matrix => $versions) {
        if ($matrix === 'default' || version_compare($tagName, $matrix, ">=")) {
            $phpVersions = $versions;
            break;
        }
    }

    foreach ($phpVersions as $i => $php) {
        $folder = 'shopware/' . $php . '/' . $tagName;

        if (!file_exists($folder)) {
            if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
        }

        $replacements = [
            '${PHP_VERSION}' => $phpIndex[$php] ?? $php,
            '${SHOPWARE_VERSION}' => $tagName,
            '${SHOPWARE_DL}' => $findShopwareDL($tagName),
        ];

        file_put_contents($folder . '/Dockerfile', str_replace(array_keys($replacements), $replacements, $dockerTpl));
        file_put_contents(
            $folder . '/Dockerfile.cli',
            str_replace(
                array_keys($replacements),
                $replacements,
                $dockerTpl
            ) . PHP_EOL . 'ENV RUN_NGINX=0' . PHP_EOL . 'HEALTHCHECK NONE'
        );

        $workflowTpl = <<<'TPL'

  #JOBKEY#:
    name: #NAME#
    runs-on: ubuntu-20.04
    steps:
      - uses: actions/checkout@v3
    
      - name: Login into Docker Hub Registery
        run: echo "${{ secrets.DOCKER_PASSWORD }}" | docker login -u "shyim" --password-stdin

      - name: Login into Github Docker Registery
        run: echo "${{ secrets.GITHUB_TOKEN }}" | docker login ghcr.io -u ${{ github.actor }} --password-stdin

      - name: Set up QEMU
        uses: docker/setup-qemu-action@v2

      - name: Set up Docker Buildx
        id: buildx
        uses: docker/setup-buildx-action@v2

      - name: Build PHP Web
        run: docker buildx build -f #DOCKER_FILE# --platform linux/amd64,linux/arm64 #TAGS# --push .

      - name: Build PHP CLI
        run: docker buildx build -f #DOCKER_FILE#.cli --platform linux/amd64,linux/arm64 #CLI_TAGS# --push .
TPL;

        $tags = '';
        $cliTags = '';

        foreach ($versionTags as $tag) {
            // default php version is always lowest
            if ($i === 0) {
                $tags .= '--tag ghcr.io/shyim/shopware:' . $tag . ' ';
                $tags .= '--tag shyim/shopware:' . $tag . ' ';

                $cliTags .= '--tag ghcr.io/shyim/shopware:cli-' . $tag . ' ';
                $cliTags .= '--tag shyim/shopware:cli-' . $tag . ' ';
            }

            $tags .= '--tag ghcr.io/shyim/shopware:' . $tag . '-php' . $php . ' ';
            $tags .= '--tag shyim/shopware:' . $tag . '-php' . $php . ' ';

            $cliTags .= '--tag ghcr.io/shyim/shopware:cli-' . $tag . '-php' . $php . ' ';
            $cliTags .= '--tag shyim/shopware:cli-' . $tag . '-php' . $php . ' ';

            if ($php !== $phpIndex[$php] ?? $php) {
                $tags .= '--tag ghcr.io/shyim/shopware:' . $tag . '-php' . $phpIndex[$php] . ' ';
                $tags .= '--tag shyim/shopware:' . $tag . '-php' . $phpIndex[$php] . ' ';

                $cliTags .= '--tag ghcr.io/shyim/shopware:cli-' . $tag . '-php' . $phpIndex[$php] . ' ';
                $cliTags .= '--tag shyim/shopware:cli-' . $tag . '-php' . $phpIndex[$php] . ' ';
            }
        }

        $replacements = [
            '#JOBKEY#' => str_replace('.', '_', 'shopware-' . $shopwareTag['name'] . '-' . $php),
            '#NAME#' => $shopwareTag['name'] . ' with PHP ' . $php,
            '#TAGS#' => $tags,
            '#CLI_TAGS#' => $cliTags,
            '#DOCKER_FILE#' => './' . $folder . '/Dockerfile'
        ];

        $workflow .= str_replace(array_keys($replacements), $replacements, $workflowTpl);
    }
}

file_put_contents('.github/workflows/shopware.yml', $workflow);
