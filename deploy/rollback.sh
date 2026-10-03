#!/usr/bin/env bash
# Switches the site back to the previous release (code only; the database is left as it is —
# migrations are written to be backward compatible for one release).
#   bash <deploy-path>/current/deploy/rollback.sh <deploy-path>
set -euo pipefail

BASE="$1"
PHP="${PHP_BIN:-php}"
CURRENT="$(readlink -f "$BASE/current")"

PREVIOUS=""
for dir in $(ls -1dt "$BASE"/releases/*/); do
    dir="${dir%/}"
    if [ "$(readlink -f "$dir")" != "$CURRENT" ] && [ -e "$dir/artisan" ]; then
        PREVIOUS="$dir"
        break
    fi
done

if [ -z "$PREVIOUS" ]; then
    echo "No previous release to roll back to." >&2
    exit 1
fi

ln -sfn "$PREVIOUS" "$BASE/current.next"
mv -Tf "$BASE/current.next" "$BASE/current"
cd "$BASE/current"
"$PHP" artisan optimize
"$PHP" artisan up
echo "Rolled back to: $(basename "$PREVIOUS")"
