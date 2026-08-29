#!/usr/bin/env bash
# Ship this laptop's tree to aidev. Does not push git.
set -euo pipefail

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

SKIP_BUILD=0
while [[ $# -gt 0 ]]; do
    case "$1" in
        --skip-build) SKIP_BUILD=1; shift ;;
        -h|--help)
            echo "Usage: $0 [--skip-build]"
            exit 0
            ;;
        *) die "unknown arg: $1" ;;
    esac
done

require_cmd rsync
require_cmd ssh
ensure_ssh_alias

[[ -f "${PROD_ROOT}/artisan" ]] || die "not the enterlms root: ${PROD_ROOT}"
ssh_aidev "test -d ${APP_DIR} && test -f ${APP_DIR}/.env" \
    || die "${APP_DIR}/.env missing on aidev — run ./scripts/prod.sh provision && ./scripts/prod.sh env-init"

if [[ "${SKIP_BUILD}" -eq 0 ]]; then
    require_cmd npm
    info "building Vite assets on this laptop (aidev is 2GB — do not npm there)"
    (cd "${PROD_ROOT}" && npm run build)
fi
[[ -f "${PROD_ROOT}/public/build/manifest.json" ]] || die "missing public/build/manifest.json — run npm run build"

info "rsync → ${AIDEV_HOST}:${APP_DIR}"
rsync -az --delete --human-readable \
    --exclude-from="${PROD_SCRIPTS}/rsync-exclude.txt" \
    -e "ssh -o ControlMaster=auto -o ControlPath=${CTRL_PATH} -o ControlPersist=10m -o BatchMode=yes" \
    "${PROD_ROOT}/" "${AIDEV_HOST}:${APP_DIR}/"

ssh_aidev "chown -R ${APP_USER}:${APP_USER} ${APP_DIR} && chmod 640 ${APP_DIR}/.env"

info "remote release (composer, migrate --force, optimize)"
scp_aidev "${PROD_SCRIPTS}/remote-release.sh" "${AIDEV_HOST}:/tmp/enterlms-remote-release.sh"
ssh_aidev "APP_DIR='${APP_DIR}' APP_USER='${APP_USER}' bash /tmp/enterlms-remote-release.sh"

green "Deployed to https://${DOMAIN}"
echo "Check: ./scripts/prod.sh health"
