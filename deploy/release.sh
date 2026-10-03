#!/usr/bin/env bash
# Activates an uploaded release on the server. Called by .github/workflows/deploy.yml:
#   bash releases/<release>/deploy/release.sh <deploy-path> <release>
#
# Layout under <deploy-path> (see docs/03-deployment.md):
#   releases/<release>/   code of each version (the last 3 are kept)
#   shared/.env           secrets, created once by hand (chmod 600)
#   shared/storage/       uploads, logs, sessions, backups — survive every release
#   current -> releases/<release>
#   public_html -> current/public
set -euo pipefail

BASE="$1"
RELEASE="$2"
DIR="$BASE/releases/$RELEASE"
PHP="${PHP_BIN:-php}"

cd "$DIR"

# Shared, persistent pieces.
if [ ! -f "$BASE/shared/.env" ]; then
    echo "Missing $BASE/shared/.env — create it from .env.production.example first." >&2
    exit 1
fi
mkdir -p "$BASE/shared/storage"/{app/public,app/private,app/backups,framework/cache/data,framework/sessions,framework/views,logs}
ln -sfn "$BASE/shared/.env" .env
rm -rf storage
ln -sfn "$BASE/shared/storage" storage
mkdir -p bootstrap/cache
"$PHP" artisan storage:link --relative --force

# If anything below fails, the previous version stays live and leaves maintenance mode.
trap 'echo "Release failed: the previous version stays live." >&2; [ -e "$BASE/current/artisan" ] && (cd "$BASE/current" && "$PHP" artisan up) || true' ERR

# Maintenance on the running version while the database changes, if one is live.
if [ -e "$BASE/current/artisan" ]; then
    (cd "$BASE/current" && "$PHP" artisan down --retry=30 || true)
fi

"$PHP" artisan migrate --force
# Permissions and default settings: only adds what is missing, never overwrites the admin's changes.
"$PHP" artisan db:seed --class=RolesAndPermissionsSeeder --force
"$PHP" artisan db:seed --class=SettingsSeeder --force
"$PHP" artisan optimize

# Atomic switch to the new version.
ln -sfn "$DIR" "$BASE/current.next"
mv -Tf "$BASE/current.next" "$BASE/current"

"$PHP" artisan up
echo "Live: $RELEASE"

# Keep the three newest releases for rollback.
cd "$BASE/releases"
ls -1dt -- */ | tail -n +4 | xargs -r rm -rf --
