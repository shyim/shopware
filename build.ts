import * as semver from "https://deno.land/x/semver/mod.ts";

async function main() {
    let releases = await getReleases();

    const availableArch: string[] = ['amd64', 'arm64'];
    const ghConfig = {
        amd64: {
            'fail-fast': false,
            matrix: {
                include: [] as any
            }
        },
        arm64: {
            'fail-fast': false,
            matrix: {
                include: [] as any
            }
        },
        merge: {
            'fail-fast': false,
            matrix: {
                include: [] as any
            }
        }
    };

    let mergeCommands = '';

    // Build
    for (let release of releases) {
        ghConfig.amd64.matrix.include.push({
            name: `AMD64: Shopware ${release.tags.join(',')}`,
            runs: {
                build: `docker build --build-arg SHOPWARE_DL=${release.download} --build-arg SHOPWARE_VERSION=${release.version} ${buildImageTags(release.tags, 'amd64', '--tag ', '')} .`,
                push: `${buildImageTags(release.tags, 'amd64', 'docker push ', ';')}`
            }
        });

        ghConfig.arm64.matrix.include.push({
            name: `ARM64: Shopware ${release.tags.join(',')}`,
            runs: {
                build: `docker build --build-arg SHOPWARE_DL=${release.download} --build-arg SHOPWARE_VERSION=${release.version} ${buildImageTags(release.tags, 'arm64', '--tag ', '')} .`,
                push: `${buildImageTags(release.tags, 'arm64', 'docker push ', ';')}`
            }
        });

        for (let tag of release.tags) {
            const images = ['shyim/shopware', 'ghcr.io/shyim/shopware'];

            for (let image of images) {
                mergeCommands = `${mergeCommands}docker manifest create ${image}:${tag} --amend ${image}:${tag}-amd64 --amend ${image}:${tag}-arm64;`
                mergeCommands = `${mergeCommands}docker manifest push ${image}:${tag};`
            }
        }
    }

    ghConfig.merge.matrix.include.push({
        name: 'Create Manifest',
        runs: {
            merge: mergeCommands
        }
    });

    await Deno.stdout.write(new TextEncoder().encode(JSON.stringify(ghConfig)));
}

function buildImageTags(tags : string[], arch: string, prefix: string, suffix: string): string {
    let ret = '';

    const images = ['shyim/shopware', 'ghcr.io/shyim/shopware'];

    for (let image of images) {
        for (let tag of tags) {
            ret = `${ret} ${prefix}${image}:${tag}-${arch}${suffix}`;
        }
    }

    return ret.trim();
}


function getMajorVersion(version: string) {
    let majorVersion = /\d+\.\d+/gm.exec(version);

    if (majorVersion && majorVersion[0]) {
        return majorVersion[0];
    } 

    return '';
}

function getVersion(version: string) {
    let majorVersion = /\d+\.\d+.\d+/gm.exec(version);

    if (majorVersion && majorVersion[0]) {
        return majorVersion[0];
    } 

    return '';
}

main();

async function getReleases() {
    let json = await (await fetch('https://update-api.shopware.com/v1/releases/install?major=6')).json();
    let releases = [];
    let givenTags: string[] = [];


    for (let release of json) {
        if (semver.lt(getVersion(release.version), '6.4.0')) {
            continue;
        }

        const majorVersion = getMajorVersion(release.version);

        let image = {
            version: release.version,
            download: release.uri,
            tags: [release.version]
        }

        if (!givenTags.includes(majorVersion)) {
            image.tags.push(majorVersion);
            givenTags.push(majorVersion);
        }

        if (!givenTags.includes('latest')) {
            image.tags.push('latest');
            givenTags.push('latest');
        }

        releases.push(image);
    }

    return releases;
}
