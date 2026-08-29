#!/usr/bin/env bash
# First-time aidev host setup for EnterLMS. Additive: does not replace Caddyfile
# or other sites (sipamungkas / Enter365 / OpenClaw stay).
set -euo pipefail

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

ensure_ssh_alias
info "Provisioning ${DOMAIN} on ${AIDEV_HOST} (additive)"

confirm "Install PHP-FPM pool, MySQL DB, and Caddy site for ${DOMAIN} on aidev?" || die "aborted"

REMOTE_TMP="/tmp/enterlms-provision-$$"
ssh_aidev "mkdir -p ${REMOTE_TMP}"
scp_aidev \
    "${PROD_SCRIPTS}/templates/Caddyfile.site" \
    "${PROD_SCRIPTS}/templates/php-fpm-pool.conf" \
    "${PROD_SCRIPTS}/templates/supervisor-enterlms.conf" \
    "${PROD_SCRIPTS}/templates/env.production.example" \
    "${AIDEV_HOST}:${REMOTE_TMP}/"

ssh_aidev "DOMAIN='${DOMAIN}' APP_DIR='${APP_DIR}' DB_NAME='${DB_NAME}' DB_USER='${DB_USER}' REMOTE_TMP='${REMOTE_TMP}' bash -s" <<'REMOTE'
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

if [[ ${EUID} -ne 0 ]]; then
    echo "must run as root" >&2
    exit 1
fi

echo "==> packages"
apt-get update -y
apt-get install -y ca-certificates curl gnupg unzip git rsync acl supervisor mysql-client

if ! command -v caddy >/dev/null; then
    echo "Caddy is missing. aidev already serves pamungkas.org via Caddy." >&2
    exit 1
fi

PHP_VER=""
for v in 8.4 8.3; do
    if command -v "php${v}" >/dev/null 2>&1 || apt-cache policy "php${v}-fpm" 2>/dev/null | grep -q Candidate; then
        PHP_VER="$v"
        break
    fi
done
if [[ -z "${PHP_VER}" ]]; then
    echo "php-fpm 8.4/8.3 not found" >&2
    exit 1
fi

apt-get install -y \
    "php${PHP_VER}-fpm" "php${PHP_VER}-cli" "php${PHP_VER}-mysql" \
    "php${PHP_VER}-mbstring" "php${PHP_VER}-xml" "php${PHP_VER}-curl" \
    "php${PHP_VER}-zip" "php${PHP_VER}-bcmath" "php${PHP_VER}-intl" \
    "php${PHP_VER}-gd" "php${PHP_VER}-readline"

if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> dirs"
mkdir -p "${APP_DIR}" /var/log/enterlms /etc/caddy/sites /var/backups/enterlms
chown -R www-data:www-data "${APP_DIR}" /var/log/enterlms
chmod 750 /var/log/enterlms

CADDY_USER="$(ps -o user= -C caddy 2>/dev/null | awk 'NF{print; exit}')"
CADDY_USER="${CADDY_USER:-caddy}"
id "${CADDY_USER}" >/dev/null 2>&1 || CADDY_USER=www-data
PHP_SOCK="/run/php/php${PHP_VER}-fpm-enterlms.sock"

POOL_DIR="/etc/php/${PHP_VER}/fpm/pool.d"
mkdir -p "${POOL_DIR}"
sed -e "s|__PHP_SOCK__|${PHP_SOCK}|g" -e "s|__CADDY_USER__|${CADDY_USER}|g" \
    "${REMOTE_TMP}/php-fpm-pool.conf" >"${POOL_DIR}/enterlms.conf"

sed -e "s|__APP_DIR__|${APP_DIR}|g" \
    "${REMOTE_TMP}/supervisor-enterlms.conf" >/etc/supervisor/conf.d/enterlms.conf

sed -e "s|__DOMAIN__|${DOMAIN}|g" \
    -e "s|__APP_DIR__|${APP_DIR}|g" \
    -e "s|__PHP_SOCK__|${PHP_SOCK}|g" \
    "${REMOTE_TMP}/Caddyfile.site" >/etc/caddy/sites/${DOMAIN}.caddy

if ! grep -q 'import /etc/caddy/sites/\*\.caddy' /etc/caddy/Caddyfile 2>/dev/null; then
    cp -a /etc/caddy/Caddyfile "/etc/caddy/Caddyfile.bak.enterlms.$(date -u +%Y%m%dT%H%M%SZ)"
    printf '\n# EnterLMS / Enter365 sites\nimport /etc/caddy/sites/*.caddy\n' >>/etc/caddy/Caddyfile
fi

echo "==> mysql"
systemctl enable --now mysql
if ! mysql -N -e "SELECT 1 FROM mysql.user WHERE User='${DB_USER}' AND Host='127.0.0.1'" | grep -q 1; then
    DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)"
    mysql -e "CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    echo "${DB_PASS}" >/root/enterlms-db-pass.txt
    chmod 600 /root/enterlms-db-pass.txt
    echo "DB password written to /root/enterlms-db-pass.txt (not echoed)"
else
    echo "role ${DB_USER} already exists"
fi
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "==> php-fpm + caddy + supervisor"
systemctl enable --now "php${PHP_VER}-fpm"
systemctl reload "php${PHP_VER}-fpm" || systemctl restart "php${PHP_VER}-fpm"

caddy validate --config /etc/caddy/Caddyfile
systemctl reload caddy || systemctl restart caddy

systemctl enable --now supervisor
supervisorctl reread
supervisorctl update

if [[ ! -f "${APP_DIR}/.env" ]]; then
    cp "${REMOTE_TMP}/env.production.example" "${APP_DIR}/.env"
    if [[ -f /root/enterlms-db-pass.txt ]]; then
        DB_PASS="$(cat /root/enterlms-db-pass.txt)"
        sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" "${APP_DIR}/.env"
    fi
    chown www-data:www-data "${APP_DIR}/.env"
    chmod 640 "${APP_DIR}/.env"
    echo "Wrote ${APP_DIR}/.env — generate APP_KEY after first rsync: ./scripts/prod.sh env-init"
fi

rm -rf "${REMOTE_TMP}"
echo "PROVISION_OK php=${PHP_VER} sock=${PHP_SOCK} caddy_user=${CADDY_USER}"
echo "TLS: DNS is already ${DOMAIN} → this host. If Cloudflare is orange-cloud and Caddy cannot mint a cert, grey-cloud the A record, reload Caddy, then proxy again. SSL mode Full (strict)."
REMOTE

green "Provision finished. Next:"
echo "  ./scripts/prod.sh env-init"
echo "  ./scripts/prod.sh deploy"
echo "  ./scripts/prod.sh seed-academy"
echo "  ./scripts/prod.sh health"
