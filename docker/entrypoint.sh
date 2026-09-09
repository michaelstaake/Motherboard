#!/bin/sh
set -e

APP_DIR="/var/www/html"
CONFIG_FILE="$APP_DIR/config.php"
SAMPLE_FILE="$APP_DIR/config.sample.php"

# config.php is gitignored, so a fresh clone does not have one and index.php's
# unconditional `require_once 'config.php'` fails. config.sample.php reads every
# setting from getenv(), so a verbatim copy is a working configuration for both
# the dev and production compose files.
if [ ! -f "$CONFIG_FILE" ]; then
    if [ ! -f "$SAMPLE_FILE" ]; then
        echo "entrypoint: no config.php and no config.sample.php in $APP_DIR." >&2
        echo "entrypoint: is ./public_html bind-mounted at $APP_DIR?" >&2
        exit 1
    fi
    cp "$SAMPLE_FILE" "$CONFIG_FILE" || {
        echo "entrypoint: could not write $CONFIG_FILE (read-only mount?)" >&2
        exit 1
    }
    # The copy lands on the host through the bind mount owned by root. Match the
    # ownership of the tree so the host user can still read and edit it.
    chown "$(stat -c '%u:%g' "$SAMPLE_FILE")" "$CONFIG_FILE" 2>/dev/null || true
    # No literal secrets in the generated file; every value comes from getenv().
    # If you hand-edit real secrets in, tighten the mode yourself. .htaccess
    # already denies config.php over HTTP either way.
    chmod 0644 "$CONFIG_FILE"
    echo "entrypoint: created config.php from config.sample.php"
fi

ATTACHMENTS_DIR="$APP_DIR/attachments"
mkdir -p "$ATTACHMENTS_DIR/pending"

# The bind mount keeps the host user's ownership, so www-data reaches this tree
# as "other". Directories need o+rwx (uploads and deletes write the directory,
# not the file); files only need o+r. Ownership must stay with the host user —
# attachments/.htaccess and index.php are tracked, and chowning them to www-data
# would make a later `git pull` fail.
chmod o+rwx "$ATTACHMENTS_DIR" "$ATTACHMENTS_DIR/pending"

# One-time pass to fix trees created before this mode existed or restored from
# backup. The marker keeps later starts O(1) as the tree grows; it sits inside
# attachments/, already covered by .gitignore's public_html/attachments/**.
if [ ! -f "$ATTACHMENTS_DIR/.perms-ok" ]; then
    find "$ATTACHMENTS_DIR" -type d -exec chmod o+rwx {} + 2>/dev/null || true
    find "$ATTACHMENTS_DIR" -type f -exec chmod o-wx {} + 2>/dev/null || true
    : > "$ATTACHMENTS_DIR/.perms-ok" 2>/dev/null || true
fi

# Session volume is created root-owned by Docker; let Apache (www-data) write to it.
SESSIONS_DIR="/var/lib/php/sessions"
mkdir -p "$SESSIONS_DIR"
chown -R www-data:www-data "$SESSIONS_DIR"

exec "$@"
