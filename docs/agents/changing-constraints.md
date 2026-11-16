# Changing constraints in `composer.json`

Follow every step that applies to your change. The change is done when both commands pass in the repo root and in `tests/fixtures/composer_home`:

```shell
composer validate --no-check-publish     # fails on a stale content-hash
composer why-not php <lowest> --locked   # exit 0: every locked package installs on <lowest>
```

`<lowest>` is the lowest version the `php` constraint allows (e.g. `8.3.0`). CI installs both locks on it, but Composer resolves against the running PHP, so a lock updated on a newer PHP can hold packages `<lowest>` cannot install. Every lock update below therefore runs under a **platform pin**:

```shell
composer config platform.php <lowest>
composer update <packages>
composer config --unset platform
composer update --lock   # the content-hash covers config.platform, so unsetting the pin makes it stale
```

1. **`php` constraint:** update the `php-version` matrices in `.github/workflows/` to match. The lint job runs on the lowest allowed version.
2. **Root lock:** refresh `composer.lock` under the platform pin, naming the changed packages with `--with-dependencies`.
3. **COMPOSER_HOME lock:** `tests/fixtures/composer_home/composer.lock` pins a snapshot of this plugin's own `composer.json`, so any `require` change makes it stale. Refresh it from `tests/fixtures/composer_home` under the platform pin, with `<packages>` set to `mxr576/ddqg-composer-audit <stale packages>`. Name the stale packages explicitly and leave out `-W`, which would bump Composer and Symfony across majors.

`tools/rector/composer.lock` only has to run Rector, so it needs no platform pin.

## Raising the PHP floor

1. Change the `php` constraint in `composer.json`.
2. Update the workflow `php-version` matrices, with the Lint job on the new lowest version.
3. Refresh the root lock and the `composer_home` lock under the platform pin (steps 2 and 3 above).
4. Run `tools/rector/vendor/bin/rector process` from the repo root. It reads the new floor from `composer.json`.
5. Run `vendor/bin/php-cs-fixer fix`.
6. Type by hand the constants Rector skips: constants in anonymous classes that use traits, and enum-valued constants. Skip a constant when the class's trait declares one with the same name.
7. Update the `<lowest>` example at the top of this doc.
