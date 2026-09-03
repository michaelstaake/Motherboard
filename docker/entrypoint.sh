#!/bin/sh
set -e

# public_html is bind-mounted from the host, so the attachments directory
# keeps the host user's ownership/permissions instead of www-data's. Make
# sure Apache (running as www-data) can write to it before it starts.
ATTACHMENTS_DIR="/var/www/html/attachments"
mkdir -p "$ATTACHMENTS_DIR/pending"
chmod -R o+rwX "$ATTACHMENTS_DIR"

# Session volume is created root-owned by Docker; let Apache (www-data) write to it.
SESSIONS_DIR="/var/lib/php/sessions"
mkdir -p "$SESSIONS_DIR"
chown -R www-data:www-data "$SESSIONS_DIR"

exec "$@"
