<?php

$image = $argv[1] ?? 'nginx';

if (!in_array($image, ['nginx', 'caddy'], true)) {
  echo "Server can be only nginx or caddy" . PHP_EOL;
  exit(1);
}

$supportedVersions = ['8.0', '8.1', '8.2'];
$index = [];
$tpl = file_get_contents('Dockerfile.' . $image . '-php.template');
$versionRegex ='/^(?<version>\d\.\d\.\d{1,})/m';

$workflow = <<<YML
version: 2.1

parameters:
  build-image:
    type: boolean
    default: false

jobs:
YML;

$stages = [];
$dockerMerges = [];

foreach ($supportedVersions as $supportedVersion)
{
    $apiResponse = json_decode(file_get_contents('https://hub.docker.com/v2/repositories/library/php/tags/?page_size=50&page=1&name=' . $supportedVersion. '.'), true);

    if (!is_array($apiResponse)) {
        throw new \RuntimeException("invalid api response");
    }

    $curVersion = null;
    $patchVersion = null;

    foreach ($apiResponse['results'] as $entry) {
        if (strpos($entry['name'], 'RC') !== false) {
            continue;
        }

        preg_match($versionRegex, $entry['name'], $patchVersion);

        if (count($patchVersion) > 0) {
            break;
        }
    }

    if ($patchVersion === null) {
        throw new \RuntimeException('There is no version found for PHP ' . $supportedVersion);
    }

    $folder = $image . '/' . $supportedVersion . '/';
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    file_put_contents($folder . 'Dockerfile', str_replace('${PHP_VERSION}', $patchVersion['version'], $tpl));
    $index[$supportedVersion] = $patchVersion['version'];

    $workflowTpl = <<<'TPL'

  php${PHP_VERSION_SHORT}-arm64:
    machine:
      image: ubuntu-2004:current
      docker_layer_caching: true 
    resource_class: arm.medium
    steps:
      - checkout

      - run: echo "$GHCR_PASSWORD" | docker login ghcr.io -u shyim --password-stdin

      - run: docker build -t ghcr.io/shyim/shopware-${SERVER}:${PHP_VERSION}-arm64 -t ghcr.io/shyim/shopware-php:${PHP_PATCH_VERSION}-arm64 -f ${SERVER}/${PHP_VERSION}/Dockerfile .

      - run: docker push ghcr.io/shyim/shopware-${SERVER}:${PHP_VERSION}-arm64

      - run: docker push ghcr.io/shyim/shopware-${SERVER}:${PHP_PATCH_VERSION}-arm64

  php${PHP_VERSION_SHORT}-amd64:
      machine:
        image: ubuntu-2004:current
        docker_layer_caching: true 
      resource_class: medium
      steps:
        - checkout
  
        - run: echo "$GHCR_PASSWORD" | docker login ghcr.io -u shyim --password-stdin
  
        - run: docker build -t ghcr.io/shyim/shopware-${SERVER}:${PHP_VERSION}-amd64 -t ghcr.io/shyim/shopware-${SERVER}:${PHP_PATCH_VERSION}-amd64 -f ${SERVER}/${PHP_VERSION}/Dockerfile .
  
        - run: docker push ghcr.io/shyim/shopware-${SERVER}:${PHP_VERSION}-amd64

        - run: docker push ghcr.io/shyim/shopware-${SERVER}:${PHP_PATCH_VERSION}-amd64
  
TPL;

    $phpShort = str_replace('.', '', $supportedVersion);
    $replaces = [
      '${PHP_VERSION_SHORT}' => $phpShort,
      '${PHP_VERSION}' => $supportedVersion,
      '${PHP_PATCH_VERSION}' => $patchVersion['version'],
    ];

    $workflow .= str_replace(array_keys($replaces), array_values($replaces), $workflowTpl);

    $dockerMerges[] = 'docker manifest create ghcr.io/shyim/shopware-' . $image . ':' . $supportedVersion . ' --amend ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'] . '-amd64 --amend ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'] . '-arm64';
    $dockerMerges[] = 'docker manifest create ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'] . ' --amend ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'] . '-amd64 --amend ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'] . '-arm64';
    $dockerMerges[] = 'docker manifest push ghcr.io/shyim/shopware-' . $image . ':' . $supportedVersion;
    $dockerMerges[] = 'docker manifest push ghcr.io/shyim/shopware-' . $image . ':' . $patchVersion['version'];

    $stages[] = 'php' . $phpShort . '-arm64';
    $stages[] = 'php' . $phpShort . '-amd64';
}

$workflow .= '

  merge-manifest:
    machine:
      image: ubuntu-2004:current
      docker_layer_caching: true 
    resource_class: medium
    steps:
      - run: echo "$GHCR_PASSWORD" | docker login ghcr.io -u shyim --password-stdin

';

foreach ($dockerMerges as $merge) {
  $workflow .= "      - run: " . $merge . "\n\n";
}

$workflow .= 'workflows:
  build-base-image:
    when: << pipeline.parameters.build-image >>
    jobs:
';

foreach ($stages as $stage) {
  $workflow .= '      - ' . $stage . "\n";
}


$workflow .= "      - merge-manifest:
          requires:\n";

foreach ($stages as $stage) {
  $workflow .= '            - ' . $stage . "\n";
}

file_put_contents('.circleci/' . $image . '.yml', $workflow);
file_put_contents('index_php.json', json_encode($index, true, JSON_PRETTY_PRINT));
