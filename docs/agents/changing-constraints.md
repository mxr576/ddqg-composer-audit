# Changing constraints in `composer.json`

Follow every step that applies to your change. The change is done when each lock passes `composer install --dry-run` in its directory.

1. **`php` constraint:** update the `php-version` matrices in `.github/workflows/` to match. The lint job runs on the lowest allowed version.
2. **Root lock:** refresh `composer.lock`.
3. **COMPOSER_HOME lock:** `tests/fixtures/composer_home/composer.lock` pins a snapshot of this plugin's own `composer.json`, so any `require` change makes it stale. Refresh it from `tests/fixtures/composer_home` with `composer update mxr576/ddqg-composer-audit <stale packages>`. Name the stale packages explicitly and leave out `-W`, which would bump Composer and Symfony across majors.
