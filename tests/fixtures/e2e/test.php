#!/usr/bin/env php
<?php

/**
 * Copyright (c) 2023-2024 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

require_once $_composer_autoload_path ?? __DIR__ . '/vendor/autoload.php';

use Webmozart\Assert\Assert;

$audit_output = $argv[1] ?? (stream_get_contents(STDIN) ?: null);
if (null === $audit_output) {
    throw new LogicException('Missing "composer audit" command output.');
}

fwrite(STDERR, $audit_output);

try {
    $audit_result = json_decode($audit_output, true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    throw new LogicException(sprintf('Malformed JSON input: "%s". %s', base64_encode(gzdeflate($audit_output, 9)), $e->getMessage()), 0, $e);
}

$package_has_advisory_by_id = static function (array $audit_result, string $package, string $advisory_id_needle): bool {
    $result = false;
    foreach ($audit_result['advisories'][$package] ?? [] as $advisory) {
        if (str_contains($advisory['advisoryId'], $advisory_id_needle)) {
            $result = true;
            break;
        }
    }

    return $result;
};

// Unsupported
Assert::true($package_has_advisory_by_id($audit_result, 'drupal/feeds', 'DDQG-unsupported-drupal-feeds-3.0.0.0-beta3'), 'drupal/feeds is flagged as unsupported by DDQG Composer Audit extension');
Assert::false($package_has_advisory_by_id($audit_result, 'drupal/tamper', 'DDQG-unsupported-tamper'), 'drupal/tamper is on the ignore list so it was not flagged as unsupported by DDQG Composer Audit extension');

// Deprecated
Assert::true($package_has_advisory_by_id($audit_result, 'drupal/breakpoint_js_settings', 'DDQG-deprecated-drupal-breakpoint_js_settings-1.0.0.0'), 'drupal/breakpoint_js_settings is flagged as deprecated by DDQG Composer Audit extension');
Assert::false($package_has_advisory_by_id($audit_result, 'drupal/variationcache', 'DDQG-insecure-variationcache'), 'drupal/variationcache is on the ignore list so it was not flagged as deprecated by DDQG Composer Audit extension');
Assert::false($package_has_advisory_by_id($audit_result, 'drupal/swiftmailer', 'DDQG-deprecated-swiftmailer'), 'drupal/swiftmailer is on the ignore list so it was not flagged as deprecated by DDQG Composer Audit extension');

// Insecure
// @TODO Requires Composer 2.6.0 and its multi-repo security advisory support.
// Assert::true($package_has_advisory_by_id($audit_result, 'drupal/apigee_edge', 'DDQG-insecure-drupal-apigee_edge'), 'The installed version of drupal/apigee_edge is flagged as insecure by DDQG Composer Audit extension0');
Assert::true($package_has_advisory_by_id($audit_result, 'drupal/core', 'DDQG-insecure-drupal-core'), 'drupal/core is flagged as insecure by DDQG Composer Audit extension');

// D10 incompatibility
Assert::true($package_has_advisory_by_id($audit_result, 'drupal/apigee_edge', 'DDQG-D10-incompatible-drupal-apigee_edge'), 'The installed version of drupal/apigee_edge is flagged by DDQG Composer Audit extension because it does not support Drupal 10');
