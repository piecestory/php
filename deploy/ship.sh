#!/usr/bin/env bash
# Packages the current checkout (dependencies and assets already built for production), uploads it
# and activates it with deploy/release.sh. Used by .github/workflows/deploy.yml, and by hand:
#
#   composer install --no-dev --optimize-autoloader && npm run build
#   SSH_KEY=.dev/deploy/id_ed25519 deploy/ship.sh <user@host> <port> <deploy-path> [php-binary]
#
# Hostinger runs PHP 8.3 by default on the command line; this project needs 8.4: /opt/alt/php84/usr/bin/php
set -euo pipefail

TARGET="$1"
PORT="$2"
BASE="$3"
PHP_BIN="${4:-php}"
KEY_OPT=()
[ -n "${SSH_KEY:-}" ] && KEY_OPT=(-i "$SSH_KEY")

RELEASE="$(date -u +%Y%m%d%H%M%S)-$(git rev-parse --short=7 HEAD)"
ARCHIVE="$(mktemp -d)/$RELEASE.tar.gz"

tar --exclude=./.git --exclude=./node_modules --exclude=./tests --exclude=./.github --exclude=./.dev \
    --exclude='./.env*' --exclude=./storage --exclude=./docs/qa --exclude=./public/hot --exclude=./public/storage \
    -czf "$ARCHIVE" .

ssh "${KEY_OPT[@]}" -p "$PORT" "$TARGET" "mkdir -p '$BASE/releases/$RELEASE'"
scp "${KEY_OPT[@]}" -P "$PORT" "$ARCHIVE" "$TARGET:$BASE/releases/$RELEASE.tar.gz"
ssh "${KEY_OPT[@]}" -p "$PORT" "$TARGET" "cd '$BASE/releases' && tar -xzf '$RELEASE.tar.gz' -C '$RELEASE' && rm '$RELEASE.tar.gz' \
    && PHP_BIN='$PHP_BIN' bash '$RELEASE/deploy/release.sh' '$BASE' '$RELEASE'"

rm -f "$ARCHIVE"
echo "Shipped $RELEASE"
