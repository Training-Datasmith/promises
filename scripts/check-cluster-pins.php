<?php

declare(strict_types=1);

/**
 * Verify cluster (composer-index) packages match expected SHAs and aliases.
 */

const INDEX_PHPUNIT_SHA_A = '698cbbc1e75a1ef1968f9cbfec89be056b288d0f';
const INDEX_PHPUNIT_SHA_B = 'a37d66fabf01af0eedabffe271b70dbe71723001';

/** @var array<string, array{reference: string, alias?: string}> $expected */
$expected = [];

$root = dirname(__DIR__);
$installedPhpPath = $root . '/vendor/composer/installed.php';
$installedJsonPath = $root . '/vendor/composer/installed.json';

if (!is_readable($installedPhpPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.php not found; run composer update first.\n");
    exit(1);
}
if (!is_readable($installedJsonPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.json not found; run composer update first.\n");
    exit(1);
}

/**
 * @return array{versions: array<string, array<string, mixed>>}
 */
function loadInstalledPhp(string $path): array
{
    /** @var array{versions: array<string, array<string, mixed>>} $installed */
    $installed = require $path;

    return $installed;
}

/**
 * @return array<string, array{name: string, version?: string, source?: array{reference?: string}}>
 */
function loadInstalledJsonPackages(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "check-cluster-pins: could not read installed.json.\n");
        exit(1);
    }

    /** @var array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>} $data */
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fwrite(STDERR, "check-cluster-pins: invalid installed.json.\n");
        exit(1);
    }

    $byName = [];
    foreach (array_merge($data['packages'] ?? [], $data['packages-dev'] ?? []) as $package) {
        if (!isset($package['name']) || !is_string($package['name'])) {
            continue;
        }
        $byName[$package['name']] = $package;
    }

    return $byName;
}

/**
 * @param array<string, array{reference: string, alias?: string}> $expectedMap
 * @param array{versions: array<string, array<string, mixed>>} $installedPhp
 * @param array<string, array{name: string, source?: array{reference?: string}}> $installedJson
 */
/**
 * @param array<string, string>|null $errorMessages
 */
function verifyExpected(array $expectedMap, array $installedPhp, array $installedJson, $reportErrors = true, &$errorMessages = null): bool
{
    $versions = $installedPhp['versions'] ?? [];
    $failed = false;
    $emit = static function ($message) use ($reportErrors, &$errorMessages): void {
        if ($errorMessages !== null) {
            $errorMessages[] = $message;
        }
        if ($reportErrors) {
            fwrite(STDERR, $message);
        }
    };

    foreach ($expectedMap as $name => $want) {
        if (!isset($versions[$name])) {
            $emit(sprintf("check-cluster-pins: %s is not installed.\n", $name));
            $failed = true;
            continue;
        }

        $pkg = $versions[$name];
        if (isset($want['alias'])) {
            $aliases = $pkg['aliases'] ?? [];
            if (!is_array($aliases) || !in_array($want['alias'], $aliases, true)) {
                $emit(sprintf(
                    "check-cluster-pins: %s alias mismatch (expected %s in aliases %s).\n",
                    $name,
                    $want['alias'],
                    json_encode($aliases)
                ));
                $failed = true;
            }
        }

        $reference = $pkg['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            $emit(sprintf("check-cluster-pins: %s has no reference in installed.php.\n", $name));
            $failed = true;
        } elseif (!hash_equals($want['reference'], $reference)) {
            $emit(sprintf(
                "check-cluster-pins: %s installed.php reference mismatch (expected %s, got %s).\n",
                $name,
                $want['reference'],
                $reference
            ));
            $failed = true;
        }

        if (!isset($installedJson[$name])) {
            $emit(sprintf("check-cluster-pins: %s is missing from installed.json.\n", $name));
            $failed = true;
            continue;
        }

        $jsonRef = $installedJson[$name]['source']['reference'] ?? null;
        if (!is_string($jsonRef) || $jsonRef === '') {
            $emit(sprintf("check-cluster-pins: %s has no source.reference in installed.json.\n", $name));
            $failed = true;
        } elseif (!hash_equals($want['reference'], $jsonRef)) {
            $emit(sprintf(
                "check-cluster-pins: %s installed.json source.reference mismatch (expected %s, got %s).\n",
                $name,
                $want['reference'],
                $jsonRef
            ));
            $failed = true;
        }
    }

    return !$failed;
}

/**
 * @param array{versions: array<string, array<string, mixed>>} $installedPhp
 */
function verifyPhpunitGapGuard(array $installedPhp): bool
{
    $versions = $installedPhp['versions'] ?? [];
    if (!isset($versions['phpunit/phpunit'])) {
        fwrite(STDERR, "check-cluster-pins: phpunit/phpunit is not installed.\n");

        return false;
    }

    $pkg = $versions['phpunit/phpunit'];
    $aliases = $pkg['aliases'] ?? [];
    if (is_array($aliases) && $aliases !== []) {
        fwrite(STDERR, "check-cluster-pins: phpunit/phpunit must resolve from Packagist (expected empty aliases).\n");

        return false;
    }

    $pretty = $pkg['pretty_version'] ?? '';
    if (is_string($pretty) && strpos($pretty, 'dev-') === 0) {
        fwrite(STDERR, "check-cluster-pins: phpunit/phpunit must not be a dev-index version.\n");

        return false;
    }

    $reference = $pkg['reference'] ?? '';
    if (!is_string($reference)) {
        fwrite(STDERR, "check-cluster-pins: phpunit/phpunit has no reference.\n");

        return false;
    }

    if (hash_equals(INDEX_PHPUNIT_SHA_A, $reference) || hash_equals(INDEX_PHPUNIT_SHA_B, $reference)) {
        fwrite(STDERR, "check-cluster-pins: phpunit/phpunit reference matches composer-index fork SHA (must stay on Packagist).\n");

        return false;
    }

    return true;
}

function errorMatchesInstalledPhpMismatch(array $errorMessages): bool
{
    foreach ($errorMessages as $message) {
        if (strpos($message, 'installed.php reference mismatch') !== false) {
            return true;
        }
    }

    return false;
}

function errorMatchesInstalledJsonMismatch(array $errorMessages): bool
{
    foreach ($errorMessages as $message) {
        if (strpos($message, 'installed.json source.reference mismatch') !== false) {
            return true;
        }
    }

    return false;
}

function runWrongShaDemo(): bool
{
    $goodRef = str_repeat('a', 40);
    $badRef = str_repeat('b', 40);

    $installedPhpGood = [
        'versions' => [
            'example/pkg' => [
                'reference' => $goodRef,
                'aliases' => ['1.2.3'],
            ],
        ],
    ];

    $installedJsonGood = [
        'example/pkg' => [
            'name' => 'example/pkg',
            'source' => ['reference' => $goodRef],
        ],
    ];

    $expectedGood = [
        'example/pkg' => ['reference' => $goodRef, 'alias' => '1.2.3'],
    ];

    $installedPhpBad = $installedPhpGood;
    $installedPhpBad['versions']['example/pkg']['reference'] = $badRef;

    $errors = [];
    if (verifyExpected($expectedGood, $installedPhpBad, $installedJsonGood, false, $errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo failed to reject installed.php-only mutation.\n");

        return false;
    }
    if (!errorMatchesInstalledPhpMismatch($errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo did not report installed.php reference mismatch.\n");

        return false;
    }
    if (errorMatchesInstalledJsonMismatch($errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo must not report installed.json mismatch for installed.php-only mutation.\n");

        return false;
    }

    $installedJsonBad = $installedJsonGood;
    $installedJsonBad['example/pkg']['source']['reference'] = $badRef;

    $errors = [];
    if (verifyExpected($expectedGood, $installedPhpGood, $installedJsonBad, false, $errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo failed to reject installed.json-only mutation.\n");

        return false;
    }
    if (!errorMatchesInstalledJsonMismatch($errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo did not report installed.json source.reference mismatch.\n");

        return false;
    }
    if (errorMatchesInstalledPhpMismatch($errors)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo must not report installed.php mismatch for installed.json-only mutation.\n");

        return false;
    }

    if (!verifyExpected($expectedGood, $installedPhpGood, $installedJsonGood, false)) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo fixture must pass with matching SHAs.\n");

        return false;
    }

    return true;
}

if (!runWrongShaDemo()) {
    exit(1);
}

$installedPhp = loadInstalledPhp($installedPhpPath);
$installedJson = loadInstalledJsonPackages($installedJsonPath);

$failed = false;
if (!verifyExpected($expected, $installedPhp, $installedJson)) {
    $failed = true;
}
if (!verifyPhpunitGapGuard($installedPhp)) {
    $failed = true;
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: all cluster packages match expected SHAs and aliases.\n");
exit(0);
