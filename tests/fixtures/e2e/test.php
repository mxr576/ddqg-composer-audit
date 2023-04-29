#!/usr/bin/env php
<?php

/**
 * Copyright (c) 2023 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

$audit_output = $argv[1] ?? (stream_get_contents(STDIN) ?: null);
if (null === $audit_output) {
    throw new \LogicException('Missing "composer audit" command output.');
}

fwrite(STDERR, $audit_output);

try {
    $audit_result = json_decode($audit_output, true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    throw new \LogicException(sprintf('Malformed JSON input: "%s". %s', base64_encode(gzdeflate($audit_output, 9)), $e->getMessage()), 0, $e);
}

$is_feeds_flagged = false;
foreach ($audit_result['advisories']['drupal/feeds'] as $advisory) {
    if ('DDQG-unsupported-drupal-feeds' === $advisory['advisoryId']) {
        $is_feeds_flagged = true;
        break;
    }
}
assert(true === $is_feeds_flagged, 'drupal/feeds is flagged as unsupported by DDQG Composer Audit extension');

$is_apigee_edge_flagged = false;
foreach ($audit_result['advisories']['drupal/apigee_edge'] as $advisory) {
    if ('DDQG-insecure-drupal-apigee_edge' === $advisory['advisoryId']) {
        $is_apigee_edge_flagged = true;
        break;
    }
}
assert($is_apigee_edge_flagged, 'drupal/apigee_edge is flagged as insecure by DDQG Composer Audit extension');

$is_drupal_core_flagged = false;
foreach ($audit_result['advisories']['drupal/core'] as $advisory) {
    if ('DDQG-insecure-drupal-core' === $advisory['advisoryId']) {
        $is_drupal_core_flagged = true;
        break;
    }
}
assert(true === $is_drupal_core_flagged, 'drupal/core is flagged as insecure by DDQG Composer Audit extension');
