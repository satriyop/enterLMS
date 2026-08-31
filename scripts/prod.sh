#!/usr/bin/env bash
# EnterLMS production CLI — run from this laptop against aidev.
#
#   ./scripts/prod.sh help
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BIN="${ROOT}/scripts/prod"

cmd="${1:-help}"
shift || true

case "${cmd}" in
    help|-h|--help)
        cat <<'EOF'
EnterLMS → aidev (lms.pamungkas.org)

First time
  ./scripts/prod.sh ssh-config   add Host aidev to ~/.ssh/config if missing
  ./scripts/prod.sh inspect      what is already on the droplet
  ./scripts/prod.sh provision    PHP pool, MySQL DB, Caddy site (additive)
  ./scripts/prod.sh env-init     APP_KEY + DB password
  ./scripts/prod.sh deploy       push HEAD, rsync that SHA, migrate --force
  ./scripts/prod.sh seed-academy first catalog only. Then rotate every user password.
  ./scripts/prod.sh health

Every release
  git status must be clean; deploy pushes HEAD then rsyncs that SHA
  ./scripts/prod.sh deploy
  ./scripts/prod.sh health

Day-2
  ./scripts/prod.sh ssh
  ./scripts/prod.sh logs [laravel|queue|scheduler|php|caddy|follow]
  ./scripts/prod.sh artisan migrate --force
  ./scripts/prod.sh artisan queue:restart
  ./scripts/prod.sh backup          mysqldump to storage/backups/aidev/

Never
  migrate:fresh / db:wipe / db:seed via artisan wrapper.
  Use seed-academy once.

Env
  AIDEV_HOST=root@aidev DOMAIN=lms.pamungkas.org PROD_YES=1
EOF
        ;;
    ssh-config)
        # shellcheck source=prod/lib.sh
        source "${BIN}/lib.sh"
        ensure_ssh_alias
        echo "Host aidev ready (${AIDEV_IP})"
        ;;
    inspect) exec bash "${BIN}/inspect.sh" "$@" ;;
    provision) exec bash "${BIN}/provision.sh" "$@" ;;
    env-init) exec bash "${BIN}/env-init.sh" "$@" ;;
    deploy) exec bash "${BIN}/deploy.sh" "$@" ;;
    seed-academy) exec bash "${BIN}/seed-academy.sh" "$@" ;;
    health|status) exec bash "${BIN}/health.sh" "$@" ;;
    logs) exec bash "${BIN}/logs.sh" "$@" ;;
    backup) exec bash "${BIN}/backup.sh" "$@" ;;
    artisan) exec bash "${BIN}/artisan.sh" "$@" ;;
    ssh)
        # shellcheck source=prod/lib.sh
        source "${BIN}/lib.sh"
        ensure_ssh_alias
        exec ssh -o ControlMaster=auto -o ControlPath="${CTRL_PATH}" -o ControlPersist=10m "${AIDEV_HOST}" "$@"
        ;;
    *)
        echo "unknown command: ${cmd}" >&2
        echo "try: $0 help" >&2
        exit 1
        ;;
esac
