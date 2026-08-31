#!/usr/bin/env bash
# First catalog only. Seeded users share the local demo password — rotate them on production before anyone logs in.
set -euo pipefail

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

ensure_ssh_alias
confirm "Seed the academy catalog on ${DOMAIN}? Seeded users keep the local demo password until you rotate them." \
    || die "aborted"

ssh_aidev "APP_DIR='${APP_DIR}' APP_USER='${APP_USER}' DB_NAME='${DB_NAME}' bash -s" <<'REMOTE'
set -euo pipefail
cd "${APP_DIR}"
count="$(mysql -N -e "SELECT COUNT(*) FROM ${DB_NAME}.users" 2>/dev/null || echo 0)"
if [[ "${count}" != "0" && "${FORCE_SEED:-}" != "1" ]]; then
    echo "users table already has ${count} row(s). Refusing to seed."
    echo "If you meant it: FORCE_SEED=1 ./scripts/prod.sh seed-academy"
    exit 1
fi
sudo -u "${APP_USER}" -H php artisan db:seed --force
echo "SEED_OK"
echo "Rotate every seeded user password before anyone logs in. Do not leave the local demo password in production."
REMOTE
