# Running Motherboard on your shop's network

This guide covers running Motherboard with Docker on a machine in your shop, so
staff can reach it from any computer on the LAN. The development stack in
`docker-compose.yml` is not suitable for this — it binds every port to
localhost, ships a shared encryption key, prints debug output to visitors, and
logs phpMyAdmin in automatically. Use `docker-compose.prod.yml` instead.

## Before you start

- A machine on your network with Docker Engine and the Compose plugin.
- A fixed LAN address for it — a static IP, or a DHCP reservation on your
  router. If the address changes, `BASE_URL` stops matching and links break.
- SMTP credentials, if you want the app to send email.

The image contains no application code: `public_html` is bind-mounted from a
git clone. So you run the stack from a clone, not from a downloaded image.

## Setup

```bash
git clone https://github.com/michaelstaake/Motherboard.git motherboard-app
cd motherboard-app
cp .env.example .env
chmod 600 .env
```

Generate an encryption key:

```bash
docker run --rm php:8.4-cli php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Edit `.env` and set, at minimum:

- `APP_ENCRYPTION_KEY` — the value you just generated
- `BASE_URL` — this machine's LAN address and port, e.g. `http://192.168.1.50:8080`
- `DB_PASS` and `DB_ROOT_PASS` — two different strong passwords
- The `SMTP_*` values and `FROM_EMAIL`, if you are sending email

Start it:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Compose refuses to start if `APP_ENCRYPTION_KEY`, `BASE_URL`, `DB_PASS` or
`DB_ROOT_PASS` are missing, and tells you which one. Check everything came up:

```bash
docker compose -f docker-compose.prod.yml ps
```

`web` and `db` should both read `healthy` within a minute.

## Complete the installer immediately

Motherboard's setup screen is not password-protected — it cannot be, since it
is what creates the first account. Until an Admin account exists, **anyone who
can reach the app can create it** and own your install.

The fastest approach is to bring the stack up and go straight to `BASE_URL` in
a browser, which redirects to the setup form. Do it now, not tomorrow.

If you would rather not have that window open on the network at all, set
`WEB_BIND=127.0.0.1` in `.env` before the first start, run the installer through
an SSH tunnel from your own machine:

```bash
ssh -L 8080:127.0.0.1:8080 you@shopbox
```

then open `http://localhost:8080`, finish setup, set `WEB_BIND=0.0.0.0`, and
run `docker compose -f docker-compose.prod.yml up -d` again.

**This can happen again later.** The app decides it needs installing by checking
whether a `users` table exists. If it ever points at a database that is
reachable but empty — a wiped volume, a restore that did not finish, a typo in
`DB_NAME` — the setup form reappears on a live system. If staff report that the
site is "asking to be set up", treat it as an incident and take it off the
network before investigating.

## LAN access and the firewall

`BASE_URL` must be exactly what staff type into the browser, including the port.
The app builds its links and its HTTPS redirect from `BASE_URL` rather than from
the request, so a mismatch produces broken links and redirect loops.

Publish only what you need. With the defaults that is 8080 (the app) and 8081
(phpMyAdmin). MySQL is deliberately not published at all.

If the machine runs a firewall, restrict those ports to your LAN:

```bash
sudo ufw allow from 192.168.1.0/24 to any port 8080 proto tcp
```

Be aware that Docker publishes ports by writing its own iptables rules, which
on many systems bypass `ufw` entirely — a `ufw deny` may not actually block a
published container port. The reliable control is `WEB_BIND` / `PMA_BIND` in
`.env`: a service bound to `127.0.0.1` is not on the network, regardless of the
firewall. If you need real firewalling of published ports, add rules to the
`DOCKER-USER` chain rather than relying on `ufw` defaults.

## phpMyAdmin

phpMyAdmin runs at port 8081 with its own login form. Sign in with your
`DB_USER` and `DB_PASS` for normal work, or `root` and `DB_ROOT_PASS` when you
need to create users or grant privileges.

It is published to the LAN by default, which is convenient and is also the
largest piece of attack surface in this stack — it is a full database console,
and the only thing in front of your data is that password. Two ways to reduce
it, both without editing the compose file:

- Set `PMA_BIND=127.0.0.1` in `.env` and reach it over an SSH tunnel
  (`ssh -L 8081:127.0.0.1:8081 you@shopbox`) when you actually need it.
- Leave it off entirely with
  `docker compose -f docker-compose.prod.yml up -d --scale phpmyadmin=0`.

You can always reach the database without phpMyAdmin:

```bash
docker compose -f docker-compose.prod.yml exec db mysql -u motherboard -p motherboard
```

## Backups

Three things must be backed up **together**, or a restore will not work:

1. The database.
2. `public_html/attachments` — photos and files staff attached to work orders.
3. `APP_ENCRYPTION_KEY` from `.env`.

The key is the one people forget. It encrypts customer device passwords, so
restoring a database without the key that was in use when it was written leaves
those passwords permanently unreadable. Store the key somewhere separate from
the backups themselves.

```bash
docker compose -f docker-compose.prod.yml exec -T db \
  mysqldump --single-transaction --routines --default-character-set=utf8mb4 \
  -u root -p"$DB_ROOT_PASS" motherboard | gzip > motherboard-$(date +%F).sql.gz

tar czf attachments-$(date +%F).tar.gz -C public_html attachments
```

A nightly cron entry, adjusting the path:

```
15 2 * * * cd /srv/motherboard-app && . ./.env && docker compose -f docker-compose.prod.yml exec -T db mysqldump --single-transaction --routines --default-character-set=utf8mb4 -u root -p"$DB_ROOT_PASS" "$DB_NAME" | gzip > /backups/motherboard-$(date +\%F).sql.gz
```

To restore:

```bash
gunzip -c motherboard-2026-09-09.sql.gz | docker compose -f docker-compose.prod.yml exec -T db \
  mysql -u root -p"$DB_ROOT_PASS" motherboard

tar xzf attachments-2026-09-09.tar.gz -C public_html
```

Test a restore once, onto a spare machine, before you need it. A backup you
have never restored is a guess.

## Updating

Back up first, then:

```bash
git pull
```

PHP changes take effect within a couple of seconds — the code is bind-mounted
and PHP re-checks file timestamps, so there is nothing to restart or rebuild.

Only if `Dockerfile` or anything under `docker/` changed:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Running that command anyway is harmless, so when in doubt, run it.

Do not rename or copy `docker-compose.prod.yml` over `docker-compose.yml`.
`docker-compose.yml` is tracked by git, and overwriting it makes `git pull`
refuse to update with a "local changes would be overwritten" error. If typing
`-f docker-compose.prod.yml` every time is tiresome, uncomment
`COMPOSE_FILE=docker-compose.prod.yml` in `.env` — plain `docker compose` will
then use the production file, and `.env` is not tracked, so it survives pulls.

## HTTPS and reverse proxies

On a closed LAN, plain HTTP is a defensible choice. If you want a hostname and
a real certificate, put a reverse proxy (Caddy, nginx, Traefik) in front:

1. Set `WEB_BIND=127.0.0.1` so only the proxy can reach the app.
2. Point the proxy at `127.0.0.1:8080`.
3. Set `BASE_URL=https://workorders.yourshop.example`.
4. **Set `FORCE_HTTPS=true`. This is not optional.**

Step 4 matters more than it looks. The app decides whether to mark the session
cookie `Secure` from `FORCE_HTTPS`, the `HTTPS` server variable, and the server
port — it does **not** look at `X-Forwarded-Proto`. When a proxy terminates TLS,
the app itself still sees a plain HTTP request on port 80, so leaving
`FORCE_HTTPS=false` means session cookies are sent without the `Secure` flag.

Two further things to know:

- The HTTPS **redirect** does trust `X-Forwarded-Proto: https` from any source.
  Configure your proxy to overwrite that header on incoming requests rather than
  passing through whatever the client sent.
- `public_html/.htaccess` has a redirect rule gated on `%{ENV:FORCE_HTTPS} =1`.
  That rule never fires in the container, because a Compose `environment:` entry
  sets the process environment, not Apache's rewrite environment. The PHP-level
  redirect is what does the work. Do not try to "fix" the `.htaccess` rule.
- No HSTS header is set anywhere in the app. Add one at the proxy if you want it.

For per-directory deny rules under nginx, see [using-nginx.md](using-nginx.md).

## Login rate limiting behind a proxy

Motherboard locks an account out after 5 failed logins in 15 minutes, keyed on
the client's IP address. It determines that address from `REMOTE_ADDR`, with a
single exception for Cloudflare's `CF-Connecting-IP`. `X-Forwarded-For` and
`X-Real-IP` are ignored.

Behind a self-hosted reverse proxy, every request therefore appears to come from
the proxy's address. The lockout becomes **global rather than per-person**: one
staff member fat-fingering their password five times can lock out the whole
shop, and an attacker gets no per-source throttling.

If you run a proxy, add rate limiting there (`limit_req` in nginx, `rate_limit`
in Caddy) and be aware of the shared-lockout behavior. On a LAN without a proxy,
none of this applies — the app sees real client addresses.

## Troubleshooting

**See what is happening:**

```bash
docker compose -f docker-compose.prod.yml logs -f web
```

**A blank page or a bare "500".** That is by design — `APP_DEBUG` is forced off
in production so errors are never shown to visitors. The details are in the logs
above. Do not turn `APP_DEBUG` on while the app is reachable from the network;
it prints stack traces and database credentials to anyone who triggers an error.

**"Unsupported PHP Version".** The bundled Dockerfile pins PHP 8.4, so this
should only appear if you changed the base image.

**Compose refuses to start**, naming a variable. That variable is missing from
`.env`. This is deliberate — it prevents a production stack from quietly booting
with the development password.

**config.php.** The container creates `public_html/config.php` from
`config.sample.php` on first start if it is missing. Every setting in it reads
from the environment, so you normally never edit it. If it gets into a bad
state, delete it and restart the container to regenerate it.

**Changing the database password.** Editing `DB_PASS` in `.env` after the first
start does not change the password inside MySQL — that value is only applied
when the data volume is first created. Change it in both places:

```bash
docker compose -f docker-compose.prod.yml exec db \
  mysql -u root -p -e "ALTER USER 'motherboard'@'%' IDENTIFIED BY 'new-password'; FLUSH PRIVILEGES;"
```

then update `DB_PASS` in `.env` and run `up -d` to recreate the app container.

**Starting over.** `docker compose -f docker-compose.prod.yml down -v` deletes
the database volume along with the containers. There is no confirmation prompt
and no undo. Take a backup first.
