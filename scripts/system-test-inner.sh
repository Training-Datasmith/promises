#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

COMPOSER_SHA256="6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c"
COMPOSER_URL="https://github.com/composer/composer/releases/download/2.11.1/composer.phar"
COMPOSER_PHAR="${ROOT_DIR}/.system-test/composer.phar"
BASELINE="${ROOT_DIR}/scripts/pre-rename-baseline.txt"
PHPUNIT_OUT="${ROOT_DIR}/.system-test/phpunit.out"

mkdir -p "${ROOT_DIR}/.system-test"

if [[ ! -f "${COMPOSER_PHAR}" ]]; then
    curl -fsSL -o "${COMPOSER_PHAR}" "${COMPOSER_URL}"
    echo "${COMPOSER_SHA256}  ${COMPOSER_PHAR}" | sha256sum -c -
    chmod +x "${COMPOSER_PHAR}"
fi

install -m 0755 "${COMPOSER_PHAR}" /usr/local/bin/composer

git config --global --add safe.directory "${ROOT_DIR}"

export COMPOSER_ALLOW_SUPERUSER=1

composer update --no-interaction --prefer-stable --no-progress
composer audit --no-interaction --no-ansi --abandoned=report

php scripts/check-cluster-pins.php
php scripts/check-rename-complete.php

vendor/bin/phpunit --colors=never --do-not-cache-result 2>&1 | tee "${PHPUNIT_OUT}"

# shellcheck source=scripts/validate-phpunit-output.sh
source "${ROOT_DIR}/scripts/validate-phpunit-output.sh"
validate_phpunit_output "${PHPUNIT_OUT}" "${BASELINE}"
