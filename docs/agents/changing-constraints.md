# Changing constraints in `composer.json`

Follow every step that applies to your change. The change is done when both commands pass in the repo root and in `tests/fixtures/composer_home`:

```shell
composer validate --no-check-publish     # fails on a stale content-hash
composer why-not php <lowest> --locked   # exit 0: every locked package installs on <lowest>
```

`<lowest>` is the lowest version the `php` constraint allows (e.g. `8.1.0`). CI installs both locks on it, but Composer resolves against the running PHP, so a lock updated on a newer PHP can hold packages `<lowest>` cannot install. Every lock update below therefore runs under a **platform pin**:

```shell
composer config platform.php <lowest>
composer update <packages>
composer config --unset platform.php
composer update --lock   # the content-hash covers config.platform, so unsetting the pin makes it stale
```

1. **`php` constraint:** update the `php-version` matrices in `.github/workflows/` to match. The lint job runs on the lowest allowed version.
2. **Root lock:** refresh `composer.lock` under the platform pin, naming the changed packages with `--with-dependencies`.
3. **COMPOSER_HOME lock:** `tests/fixtures/composer_home/composer.lock` pins a snapshot of this plugin's own `composer.json`, so any `require` change makes it stale. Refresh it from `tests/fixtures/composer_home` under the platform pin, with `<packages>` set to `mxr576/ddqg-composer-audit <stale packages>`. Name the stale packages explicitly and leave out `-W`, which would bump Composer and Symfony across majors.
