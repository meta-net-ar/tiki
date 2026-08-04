#!/usr/bin/env bash
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

set -euo pipefail

PACKAGE_FILENAME="${PACKAGE_FILENAME:-tiki-package.tar.gz}"

echo "=> Removing Composer dev dependencies..."
composer -V | grep "version 2" || composer self-update --2

composer \
    --ansi install \
    -d vendor_bundled \
    --no-dev \
    --optimize-autoloader \
    --no-progress \
    --prefer-dist \
    -n

php console.php dev:buildwsconfs --generate

echo "=> Cleanup..."
rm -fr temp && git checkout temp
rm -fr bin
rm -rf node_modules

echo "=> Optimize language files..."
find lang/ \
    -name language.php \
    -exec php doc/devtools/stripcomments.php {} \;

find lang/ -name 'language.php.old' -delete

echo "=> Permissions..."
find . -type f -exec chmod 0664 {} \;
chmod 0775 setup.sh
find . -type d -exec chmod 0755 {} \;

echo "=> Creating package..."
tar \
    --exclude='tests' \
    --exclude='doc/devtools' \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.composercache' \
    -pczf "$PACKAGE_FILENAME" \
    *

echo "=> Package created: $PACKAGE_FILENAME"