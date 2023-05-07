Drupal Dependency Quality Gate Composer Audit plugin
---

This project extends `compsoer audit` command with new "advisories" originating from the results generated by the
[mxr576/ddqg](https://github.com/mxr576/ddqg) project that aims to help run Drupal projects on secure and high-quality
Drupal dependencies.

<img alt="Family Guy, Consuela says: No, no, no low-quality dependencies" height="250" src="https://i.imgflip.com/7ijrpx.jpg"/>

## Installation

```shell
$ composer require --dev mxr576/ddqg-composer-audit
```

## Example output

```
$ composer audit
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/apigee_edge                                                               |
| CVE               | DDQG-non-D10-compatible-drupal-apigee_edge                                       |
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
| CVE               | DDQG-unsupported-drupal-feeds                                                    |
| Title             | The installed "3.0.0.0-beta3" version is unsupported. (Reported by Drupal Depend |
|                   | ency Quality Gate.)                                                              |
| URL               | https://www.drupal.org/project/feeds                                             |
| Affected versions | 2.x-dev|3.0.0-alpha1|3.0.0-alpha2|3.0.0-alpha3|3.0.0-alpha4|3.0.0-alpha5|3.0.0-a |
|                   | lpha6|3.0.0-alpha7|3.0.0-alpha8|3.0.0-alpha9|3.0.0-alpha10|3.0.0-alpha11|3.0.0-b |
|                   | eta1|3.0.0-beta2|3.0.0-beta3|3.x-dev                                             |
| Reported at       | 2023-05-07T13:49:57+00:00                                                        |
+-------------------+----------------------------------------------------------------------------------+
```

## Configuration

Quality Assurance can feel painful, but it is an important part of professional software development. The goal of this
project is to bring attention about dependency quality problems on a project. For all these reasons, it deliberately
comes with minimal opt-out options.

### Silence warning about an unsupported package version

In a project's root composer.json, under the `extra` property, add a definition like this:

```json
        "ddqg-composer-audit": {
            "ignore-unsupported-versions": {
                "vendor/package": "an_explicit_version_string",
                "drupal/tamper": "1.0.0-alpha3"
            }
        }
```

The other option is defining a comma separate list of ignore rules in the
`DDQG_COMPOSER_AUDIT_IGNORE_UNSUPPORTED_VERSIONS` environment
variable, e.g,
`DDQG_COMPOSER_AUDIT_IGNORE_UNSUPPORTED_VERSIONS=drupal/tamper:1.0.0-alpha3,vendor/package:1.x-dev`

The environment variable has a higher precedence; if it is defined, the definition in a project's root composer.json is
ignored completely.

Notice: A warning is still displayed about the ignored unsupported package on STDERR.

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

## Known issues

* Composer currently only displays advisories from one repository for a package. If multiple ones provide advisories,
  only the first one is visible; see more details at https://github.com/composer/composer/issues/11435.
