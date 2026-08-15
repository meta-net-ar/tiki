#!/usr/bin/env bash

# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

set -euo pipefail

PACKAGE_FILENAME="${PACKAGE_FILENAME:-tiki-package.tar.gz}"

PACKAGE_EXCLUDES=(
    "tests"
    "doc/devtools"
    ".git"
    ".gitignore"
    ".composercache"
    ".husky"
    ".gitpod"
    ".editorconfig"
    ".gitattributes"
    ".gitlab-ci.yml"
    ".gitlab-ci-local-env"
    ".gitlab-ci-local-variables.yml"
    ".gitpod.yml"
    ".phplint.yml"
    ".prettierrc"
    ".vimrc"
    "auto-imports.d.ts"
    "components.d.ts"
    "check_composer_exists.php"
    "eslint.config.js"
    "commitlint.config.cjs"
)

echo "=> Removing Composer dev dependencies..."

composer -V | grep "version 2" || composer self-update --2

composer --ansi install \
    -d vendor_bundled \
    --no-dev \
    --optimize-autoloader \
    --no-progress \
    --prefer-dist \
    -n

php console.php dev:buildwsconfs --generate

echo "=> Cleanup..."

rm -rf temp
git checkout -- temp

rm -rf bin
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

TAR_EXCLUDES=()

for path in "${PACKAGE_EXCLUDES[@]}"; do
    TAR_EXCLUDES+=("--exclude=${path}")
done

tar \
    "${TAR_EXCLUDES[@]}" \
    -pczf "$PACKAGE_FILENAME" \
    *

echo "=> Package created: $PACKAGE_FILENAME"
