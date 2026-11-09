#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

COMPOSER_SHA256="6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c"
COMPOSER_URL="https://github.com/composer/composer/releases/download/2.11.1/composer.phar"
COMPOSER_PHAR="${ROOT_DIR}/.system-test/composer.phar"
BASELINE="${ROOT_DIR}/scripts/pre-rename-baseline.txt"
PHPUNIT_OUT="${ROOT_DIR}/.system-test/phpunit.out"
UPDATE_OUT="${ROOT_DIR}/.system-test/composer-update.out"
AUDIT_OUT="${ROOT_DIR}/.system-test/composer-audit.out"
PINS_OUT="${ROOT_DIR}/.system-test/check-cluster-pins.out"
RENAME_OUT="${ROOT_DIR}/.system-test/check-rename-complete.out"

mkdir -p "${ROOT_DIR}/.system-test"

# shellcheck source=scripts/validate-system-gates.sh
source "${ROOT_DIR}/scripts/validate-system-gates.sh"

if [[ ! -f "${COMPOSER_PHAR}" ]]; then
    curl -fsSL -o "${COMPOSER_PHAR}" "${COMPOSER_URL}"
    echo "${COMPOSER_SHA256}  ${COMPOSER_PHAR}" | sha256sum -c -
    chmod +x "${COMPOSER_PHAR}"
fi

install -m 0755 "${COMPOSER_PHAR}" /usr/local/bin/composer

git config --global --add safe.directory "${ROOT_DIR}"

export COMPOSER_ALLOW_SUPERUSER=1

composer update --no-interaction --prefer-stable --no-progress 2>&1 | tee "${UPDATE_OUT}"
validate_composer_update_output "${UPDATE_OUT}"

composer audit --no-interaction --no-ansi --abandoned=report 2>&1 | tee "${AUDIT_OUT}"
validate_composer_audit_output "${AUDIT_OUT}"

php scripts/check-cluster-pins.php > "${PINS_OUT}"
validate_check_cluster_pins_output "${PINS_OUT}"

php scripts/check-rename-complete.php > "${RENAME_OUT}"
validate_check_rename_complete_output "${RENAME_OUT}"

vendor/bin/phpunit --colors=never --do-not-cache-result 2>&1 | tee "${PHPUNIT_OUT}"

# shellcheck source=scripts/validate-phpunit-output.sh
source "${ROOT_DIR}/scripts/validate-phpunit-output.sh"
validate_phpunit_output "${PHPUNIT_OUT}" "${BASELINE}"
