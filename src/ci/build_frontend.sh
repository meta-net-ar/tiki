#!/usr/bin/env bash
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

set -euox pipefail

case "${1:-}" in
    install)
        npm ci --prefer-offline
        ;;

    build)
        free -h
        NODE_OPTIONS="--max-old-space-size=4096" npm run build
        free -h
        ;;

    *)
        echo "Usage: $0 {install|build}"
        exit 1
        ;;
esac
