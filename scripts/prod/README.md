# Production ops (aidev)

Laptop → `root@aidev` (`${AIDEV_IP}`). Public URL: `https://${DOMAIN}` (e.g. `https://lms.example.com`).

Caddy terminates TLS and `php_fastcgi`s to a dedicated PHP 8.4 pool. MySQL holds the academy. Queue + scheduler run under supervisor. Vite assets are **built on the laptop** before deploy.

Deploy requires a clean git tree, `git push`es HEAD so origin has the SHA, rsyncs that tree, then writes `/var/www/enterlms/REVISION`. `./scripts/prod.sh health` prints it. The droplet is not a git checkout.

```bash
./scripts/prod.sh help
```

`provision` is additive (new PHP pool, new MySQL DB, Caddy site import). It does not replace an existing Caddyfile.

SSH on the droplet bans bursty connections via fail2ban. The scripts reuse the `aidev` ControlMaster socket. If port 22 refuses, wait a few minutes.

TLS: DNS A `${DOMAIN}` → server IP. If Cloudflare is orange-cloud and Caddy cannot mint a cert, grey-cloud the A record for two minutes, reload Caddy, then orange-cloud again. Cloudflare SSL mode: **Full (strict)**.

Hermes is **not** installed on the remote droplet by default. Production can point `TUTOR_RUNTIME_URL` at a local Hermes API server over Tailscale (`scripts/tutor-laptop-for-prod.sh`, `POST /v1/chat/completions`). PHP-FPM and Caddy read timeouts are 200s so a Tutor turn can wait.

First seed creates `admin@enterlms.test` and `learner@enterlms.test` (and the other catalog users) with the local demo password. Rotate every production user to a unique secret before anyone logs in. Do not leave the demo password in production.
