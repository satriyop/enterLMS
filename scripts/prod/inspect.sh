#!/usr/bin/env bash
set -euo pipefail

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

ensure_ssh_alias
ssh_aidev 'bash -s' <<'REMOTE'
set -euo pipefail
echo "===== host ====="
hostname; uname -a
. /etc/os-release 2>/dev/null && echo "$PRETTY_NAME" || true
echo "===== disk/mem ====="
df -h / | awk 'NR==1 || NR==2'
free -h | head -2
echo "===== caddy ====="
caddy version 2>/dev/null || true
ls -la /etc/caddy/sites 2>/dev/null || true
echo "===== www ====="
ls -la /var/www 2>/dev/null || true
echo "===== php ====="
php -v 2>/dev/null | head -1
ls /run/php 2>/dev/null || true
systemctl is-active php8.4-fpm 2>/dev/null || true
echo "===== mysql ====="
systemctl is-active mysql 2>/dev/null || true
mysql -N -e "SHOW DATABASES;" 2>/dev/null || true
echo "===== listen ====="
ss -lntp 2>/dev/null | grep -E ':80|:443|:22|:3306' || true
echo "===== enterlms ====="
ls -la /var/www/enterlms 2>/dev/null | head || echo "(not installed)"
REMOTE
