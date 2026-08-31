# Production ops (aidev)

Laptop → `root@aidev` (`146.190.87.122`). Public URL: `https://lms.pamungkas.org`.

Caddy terminates TLS and `php_fastcgi`s to a dedicated PHP 8.4 pool. MySQL holds the academy. Queue + scheduler run under supervisor. Vite assets are **built on the laptop** (the droplet is 2GB).

Deploy requires a clean git tree, `git push`es HEAD so origin has the SHA, rsyncs that tree, then writes `/var/www/enterlms/REVISION`. `./scripts/prod.sh health` prints it. The droplet is not a git checkout.

```bash
./scripts/prod.sh help
```

Aidev already runs Caddy + Sipamungkas + Enter365 + OpenClaw. `provision` is additive (new PHP pool, new MySQL DB, Caddy site import). It does not replace the existing Caddyfile.

SSH on this droplet bans bursty connections. The scripts reuse the `aidev` ControlMaster. If port 22 refuses, wait a few minutes.

TLS: DNS A `lms.pamungkas.org` → this host. If Cloudflare is orange-cloud and Caddy cannot mint a cert, grey-cloud the A record for two minutes, reload Caddy, then orange-cloud again. Cloudflare SSL mode: **Full (strict)**.

Hermes is **not** installed on aidev. Production points `TUTOR_RUNTIME_URL` at this Mac’s Hermes API server over Tailscale (`scripts/tutor-laptop-for-prod.sh`, `POST /v1/chat/completions`). Lid closed = Tutor down. PHP-FPM and Caddy read timeouts are 200s so a Tutor turn can wait.

First seed creates `admin@enterlms.test` and `learner@enterlms.test` (and the other catalog users) with the local demo password. Rotate every production user to a unique secret before anyone logs in. Do not leave the demo password in production.
