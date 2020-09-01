import * as semver from "https://deno.land/x/semver/mod.ts";

async function main() {
    let releases = await getReleases();

    // Build
    for (let release of releases) {
        for (let tag of release.tags) {
            for (let imageName of release.imageNames) {
                let process = Deno.run({
                    cmd: ['docker', 'build', '-t', `${imageName}:${tag}`, '--build-arg', `SHOPWARE_DL=${release.download}`, '--build-arg', `SHOPWARE_VERSION=${release.version}`, '.'],
                    stdout: 'inherit'
                });
    
                const {success} = await process.status();
    
                if (!success) {
                    Deno.exit(-1);
                }
            }
        }
    }

    // Push
    for (let release of releases) {
        for (let tag of release.tags) {
            for (let imageName of release.imageNames) {
                let process = Deno.run({
                    cmd: ['docker', 'push', `${imageName}:${tag}`],
                    stdout: 'inherit'
                });

                const {success} = await process.status();

                if (!success) {
                    Deno.exit(-1);
                }
            }
        }
    }
}

function getMajorVersion(version: string) {
    let majorVersion = /\d+\.\d+/gm.exec(version);

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
        try {
            if (semver.lt(release.version, '6.2.0')) {
                continue;
            }
        } catch (e) {
        }

        const majorVersion = getMajorVersion(release.version);

        let image = {
            imageNames: ['ghcr.io/shyim/shopware', 'shyim/shopware'],
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
