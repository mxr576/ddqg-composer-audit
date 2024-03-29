Drupal Dependency Quality Gate Composer Audit plugin
---

This project extends `composer audit` command with new "advisories" originating from the results generated by the
[mxr576/ddqg](https://packagist.org/packages/mxr576/ddqg) project that aims to help run Drupal projects on secure and high-quality
Drupal dependencies.

<img alt="Family Guy, Consuela says: No, no, no low-quality dependencies" height="250" src="https://i.imgflip.com/7ijrpx.jpg"/>

**CHECKOUT** the [mxr576/composer-audit-changes](https://packagist.org/packages/mxr576/composer-audit-changes)
"alternative" `composer audit` command because it can help with the adoption of this package on existing projects
with collected technical debt.

## Installation

```shell
$ composer require --dev mxr576/ddqg-composer-audit
```

## Example output

```
$ composer audit
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/apigee_edge                                                               |
| CVE               | DDQG-D10-incompatible-drupal-apigee_edge                                         |
| Title             | The installed "2.0.7.0" version is not compatible with Drupal 10. (Reported by D |
|                   | rupal Dependency Quality Gate.)                                                  |
| URL               | https://www.drupal.org/project/apigee_edge                                       |
| Affected versions | 2.0.7.0                                                                          |
| Reported at       | 2023-05-07T13:49:57+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/apigee_edge                                                               |
| CVE               | DDQG-insecure-drupal-apigee_edge                                                 |
| Title             | The installed "2.0.7.0" version is insecure. (Reported by Drupal Dependency Qual |
|                   | ity Gate.)                                                                       |
| URL               | https://www.drupal.org/project/apigee_edge                                       |
| Affected versions | >=1.0.0,<1.27.0|>=2.0.0,<2.0.8                                                   |
| Reported at       | 2023-05-07T13:49:57+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/core                                                                      |
| CVE               | DDQG-insecure-drupal-core                                                        |
| Title             | The installed "9.4.0.0" version is insecure. (Reported by Drupal Dependency Qual |
|                   | ity Gate.)                                                                       |
| URL               | https://www.drupal.org/project/core                                              |
| Affected versions | >=9.4.0,<9.4.14|>=9.5.0,<9.5.8|>=10.0.0,<10.0.8                                  |
| Reported at       | 2023-05-07T13:49:57+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/feeds                                                                     |
| CVE               | DDQG-unsupported-drupal-feeds-3.0.0.0-beta3                                      |
| Title             | The installed "3.0.0.0-beta3" version is unsupported. (Reported by Drupal Depend |
|                   | ency Quality Gate.)                                                              |
| URL               | https://www.drupal.org/project/feeds                                             |
| Affected versions | 2.x-dev|3.0.0-alpha1|3.0.0-alpha2|3.0.0-alpha3|3.0.0-alpha4|3.0.0-alpha5|3.0.0-a |
|                   | lpha6|3.0.0-alpha7|3.0.0-alpha8|3.0.0-alpha9|3.0.0-alpha10|3.0.0-alpha11|3.0.0-b |
|                   | eta1|3.0.0-beta2|3.0.0-beta3|3.x-dev                                             |
| Reported at       | 2023-05-07T13:49:57+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/variationcache                                                            |
| CVE               | DDQG-deprecated-drupal-variationcache-1.2.0.0                                    |
| Title             | The installed "1.2.0.0" version is deprecated. (Reported by Drupal Dependency Qu |
|                   | ality Gate.)                                                                     |
| URL               | https://www.drupal.org/project/variationcache                                    |
| Affected versions | *                                                                                |
| Reported at       | 2024-01-08T12:15:20+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
```

## Configuration

Quality Assurance can feel painful, but it is an important part of professional software development. The goal of this
project is to bring attention about dependency quality problems on a project. For all these reasons, it deliberately
comes with minimal opt-out options.

### Silence warning about a deprecated- or unsupported package version in use

> [!WARNING]
> This feature is **deprecated** and it is going to be removed in version 2.0.0. Composer's built-in [audit ignore](https://getcomposer.org/doc/06-config.md#ignore) feature replaced it.

In a project's root composer.json, under the `extra` property, add a definition like this:

```json
        "ddqg-composer-audit": {
            "ignore-deprecated-versions": {
                "vendor/package": "an_explicit_version_string",
                "drupal/swiftmailer": "2.4.0"
            }
            "ignore-unsupported-versions": {
                "vendor/package": "an_explicit_version_string",
                "drupal/tamper": "1.0.0-alpha3"
            }
        }
```

The other option is defining a comma separate list of ignore rules in
`DDQG_COMPOSER_AUDIT_IGNORE_DEPRECATED_VERSIONS` and `DDQG_COMPOSER_AUDIT_IGNORE_UNSUPPORTED_VERSIONS` environment
variables respectfully, e.g,
`DDQG_COMPOSER_AUDIT_IGNORE_DEPRECATED_VERSIONS=drupal/swiftmailer:2.4.0,vendor/package:1.x-dev` or
`DDQG_COMPOSER_AUDIT_IGNORE_UNSUPPORTED_VERSIONS=drupal/tamper:1.0.0-alpha3,vendor/package:1.x-dev`

An environment variable has a higher precedence than a configuration in composer.json; if it is defined, the definition in a project's root composer.json is
ignored completely.

Notice: A warning is still displayed about the ignored deprecated- or unsupported package on STDERR.

**Not supporting version ranges in the definition was a conscious decision because (again) the goal is making
dependency quality problems constantly visible and not sweeping them under the carpet.**

### Check Drupal 10 compatibility

For projects running on Drupal 9 still. When this feature is enabled then `composer audit` can also check whether an
installed package dependency version is also compatible with Drupal 10 or not. This can make the Drupal 10 upgrade more
painless.

**The feature is disabled by default**, it can be enabled with:

```json
        "ddqg-composer-audit": {
            "check-d10-compatibility": true
        }
```

or by setting the `DDQG_COMPOSER_AUDIT_CHECK_D10_COMPATIBILITY=true` environment variable.

**This is a seasonal feature that will be removed after Drupal 9 EOL.**

## Integrations

* "Unofficial" [build definition](https://gist.github.com/mxr576/5f87063eb2e1e2b125257878018f048d) for a Docker
  image that installs the latest version from this Composer plugin and the [composer audit-changes](https://packagist.org/packages/mxr576/composer-audit-changes)
  command

## FAQ

### Drupal Packagist already provides package advisories, so why should I care about this plugin?

This feature is only available on Drupal Packagist since 21 September 2023. Security advisory data via
Drupal Packagist only contains information based on published security advisories; it does not contain
releases flagged as ["insecure"](https://www.drupal.org/taxonomy/term/188131), but this Composer plugin does.
