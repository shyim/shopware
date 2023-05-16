#!/usr/bin/env bash

if [[ -e /state/installed_version ]]; then
    IMAGE_VERSION=$(cat /shopware_version)
    INSTALLED_VERSION=$(cat /state/installed_version)

    if [[ "$IMAGE_VERSION" != "$INSTALLED_VERSION" ]]; then
      for f in /etc/shopware/scripts/on-update/*; do source $f; done
      
      cp /shopware_version /state/installed_version
    fi
else
   for f in /etc/shopware/scripts/on-install/*; do source $f; done
fi
