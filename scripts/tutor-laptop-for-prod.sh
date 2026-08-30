#!/usr/bin/env bash
# Run the enterlms-tutor Hermes API server on this Mac for
# https://lms.pamungkas.org over Tailscale. Keep this process (and the lid) open.
#
#   ./scripts/tutor-laptop-for-prod.sh
#
# Profile ~/.hermes/profiles/enterlms-tutor/.env must have:
#   API_SERVER_ENABLED=true
#   API_SERVER_HOST=<this Mac's tailscale IPv4>
#   API_SERVER_PORT=8642
#   API_SERVER_KEY=<same as aidev TUTOR_RUNTIME_API_KEY>
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

PORT="${TUTOR_API_PORT:-8642}"
TS_HOST="${TUTOR_TAILSCALE_HOST:-$(tailscale ip -4 | head -1)}"
PROFILE_ENV="${HOME}/.hermes/profiles/enterlms-tutor/.env"

if [[ ! -f .env ]]; then
    echo "missing .env" >&2
    exit 1
fi

if ! grep -q '^TUTOR_HERMES_PROFILE=enterlms-tutor' .env; then
    echo "TUTOR_HERMES_PROFILE must be enterlms-tutor" >&2
    exit 1
fi

if [[ ! -f "${PROFILE_ENV}" ]] || ! grep -q '^API_SERVER_ENABLED=true' "${PROFILE_ENV}"; then
    echo "enable the API server in ${PROFILE_ENV}" >&2
    exit 1
fi

if [[ -z "${TS_HOST}" ]]; then
    echo "tailscale ip -4 failed" >&2
    exit 1
fi

echo "==> Hermes API http://${TS_HOST}:${PORT}/v1/chat/completions (profile enterlms-tutor)"
echo "==> aidev TUTOR_RUNTIME_URL=http://${TS_HOST}:${PORT}"
echo "==> keep this Mac awake. Ctrl-C stops Tutor on production."

caffeinate -dims hermes -p enterlms-tutor gateway run --accept-hooks
