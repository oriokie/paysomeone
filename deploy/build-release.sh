#!/usr/bin/env bash
# Build deploy/new-psw-release.zip from a clean copy.
# The zip has production PHP libraries and built pages. It has no .env,
# no secrets, no tests, and no node_modules.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
stage="$(mktemp -d)"
name="new-psw"

cleanup() { rm -rf "$stage"; }
trap cleanup EXIT

mkdir -p "$stage/$name"
rsync -a \
  --exclude '.git' \
  --exclude '.env' \
  --exclude '.env.example' \
  --exclude '.env.backup' \
  --exclude 'node_modules' \
  --exclude 'tests' \
  --exclude 'phpunit.xml' \
  --exclude 'playwright.config.ts' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude 'vite.config.js' \
  --exclude 'vendor' \
  --exclude 'storage/logs' \
  --exclude 'storage/framework/cache/data' \
  --exclude 'storage/framework/sessions' \
  --exclude 'storage/framework/views' \
  --exclude 'storage/pail' \
  --exclude 'public/hot' \
  --exclude 'public/storage' \
  --exclude 'database/seeders' \
  --exclude 'database/factories' \
  --exclude 'database/schema' \
  --exclude 'deploy/*.zip' \
  --exclude '.phpunit.cache' \
  --exclude '.phpunit.result.cache' \
  --exclude 'bootstrap/cache/*.php' \
  "$root/" "$stage/$name/"

cd "$stage/$name"
cp .env.production.example .env
rm -f bootstrap/cache/*.php
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan key:generate --force --ansi
php artisan filament:assets --ansi
rm -f .env bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

cd "$stage"
rm -f "$root/deploy/new-psw-release.zip"
zip -r -q "$root/deploy/new-psw-release.zip" "$name" \
  -x "$name/.env" \
  -x "$name/**/.env"

echo "Wrote $root/deploy/new-psw-release.zip"
