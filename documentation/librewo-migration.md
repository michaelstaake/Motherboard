# Migrating from LibreWO to Motherboard

Motherboard is a fork of LibreWO, so a LibreWO database is very close to a Motherboard one.
`importers/librewo/librewo-import.php` reads a LibreWO `mysqldump` and writes it into a
Motherboard database, keeping every record ID and work order number.

The importer is a single self-contained PHP file. It loads nothing from Motherboard and needs
no Composer packages, so it can be dropped into any install.

---

## Before you start

**The import destroys the target database.** Every user, customer, work order, attachment
record, log entry and setting in the Motherboard database is deleted and replaced. So are the
inventory module's tables, if that module is installed. There is no undo, and no partial mode:
if the LibreWO dump is missing something, the Motherboard side ends up missing it too.

Take a `mysqldump` of **both** databases first — the LibreWO one you are migrating from, and
the Motherboard one you are about to overwrite.

The importer will not run until Motherboard is installed. Install it the normal way first
(copy `public_html`, create `config.php`, open the site, complete the installer). The admin
account you create during that install is deleted by the import along with everything else;
it exists only to get the tables created.

---

## What moves and what does not

| LibreWO table | Result |
|---|---|
| `users` | Imported. Password hashes carry over unchanged, so existing passwords keep working. |
| `customers` | Imported. |
| `work_orders` | Imported. Device passwords are **encrypted** on the way in (see below). |
| `work_order_logs` | Imported. |
| `activity_logs` | Imported. |
| `user_logins` | Imported. |
| `settings` | Imported, key by key. Keys Motherboard does not recognise are reported and left behind. |
| `two_factor_codes` | **Dropped.** These are one-time codes with a ten-minute life, and LibreWO stored them in a format Motherboard no longer accepts. |
| `login_attempts` | **Dropped.** Transient rate-limiting state, rebuilt on the next login. |

Motherboard-only tables (`work_order_attachments`, and the inventory module's tables) are
emptied rather than filled: LibreWO has no attachments or inventory to move, and leaving old
rows behind would point them at records that no longer exist.

Settings Motherboard has that LibreWO did not — `language`, the attachment settings, the
printout signature toggles — are added with their default values.

### Device passwords and captcha secrets

LibreWO stored work order device passwords in the clear. Motherboard encrypts them with
`APP_ENCRYPTION_KEY`, and does the same for `turnstile_secret_key` and `recaptcha_secret_key`.
The importer encrypts them as it writes, which is why it needs that key.

**The key in the importer must match `config.php` exactly.** Get it wrong and the import still
succeeds, but Motherboard cannot read any device password back afterwards. There is no way to
recover from that except re-running the import with the right key.

### Captcha and 2FA become modules

LibreWO kept captcha and two-factor configuration in core settings. In Motherboard those are
modules, so copying the settings alone would leave a shop with its Turnstile keys present and
Turnstile switched off. The importer enables the matching module for whatever the old install
was actually using: `cloudflare-turnstile`, `google-recaptcha`, or `email-2fa`.

---

## Steps

1. Dump the LibreWO database:

   ```bash
   mysqldump -u USER -p librewo > librewo.sql
   ```

2. Install Motherboard normally and complete the installer. Create a throwaway admin account —
   the import deletes it.

3. Back up the Motherboard database you just created.

4. Copy `importers/librewo/librewo-import.php` and `librewo.sql` into the Motherboard web root
   (the directory holding `index.php`).

5. Open `librewo-import.php` in an editor and fill in the configuration block at the top:

   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'motherboard';
   const DB_USER = 'motherboard';
   const DB_PASS = 'secret';
   const LIBREWO_DUMP = 'librewo.sql';
   const APP_ENCRYPTION_KEY = '...';   // copy from config.php, character for character
   const ACCESS_KEY = '';              // see "Keep it private" below
   ```

6. Open `https://your-site/librewo-import.php`. The page runs its checks, shows how many rows
   it will delete against how many it will import, and asks you to type `DELETE`.

7. **Delete `librewo-import.php` and `librewo.sql` from the web root.** Do this immediately.

8. Sign in. LibreWO passwords still work. Anyone who has forgotten theirs can use
   *Forgot password*, which needs working SMTP settings in `config.php`.

### Keep it private

While the importer sits in the web root it is not behind a login, and anyone who reaches the
URL can erase the database. That is the same exposure the Motherboard installer has, and the
same answer applies: delete it when you are done.

If the site is reachable from the internet, set `ACCESS_KEY` to a random string first. The page
then returns a 404 to anything that does not carry it:

```
https://your-site/librewo-import.php?key=YOUR_RANDOM_STRING
```

---

## After the import

- **Try the login page before you close the importer.** If the old install used a captcha, it
  is now switched on with the old keys. Keys issued for a different hostname will not clear,
  and nobody can sign in. Recovery is a database update:

  ```sql
  UPDATE settings SET setting_value = '[]' WHERE setting_key = 'enabled_modules';
  ```

- Check **Settings → Modules**. Modules the importer cannot infer — inventory, S3 storage —
  are off and can be enabled by hand.
- Check **Settings → Localization**. The import sets the language to `en-us`; LibreWO had no
  language setting to carry over.
- Re-enter captcha secrets if the LibreWO values were stale.

---

## Why not just restore the LibreWO dump over the Motherboard database?

It almost works. `Schema::ensure()` runs on every request and would patch the schema for you:
widen `work_orders.password`, add `imei` and `remarks`, create the attachments table, encrypt
the device passwords in place.

What it does not do is encrypt the captcha secrets until something writes them, or turn on the
modules that replaced LibreWO's captcha and 2FA settings — and it leaves every device password
sitting in the clear until the next page load happens to arrive. The importer does all of that
before the data lands.

---

## Troubleshooting

**"Install Motherboard first"** — the importer found missing tables. Open the Motherboard site
and complete the installer, then run the importer again.

**"This looks like a LibreWO database rather than a Motherboard one"** — `work_orders.password`
is still `VARCHAR(100)`. That happens when the target is a LibreWO database that Motherboard has
never served. Load any Motherboard page once; it widens the column on its own, then run the
importer again.

**Device passwords come back as errors** — `APP_ENCRYPTION_KEY` in the importer did not match
`config.php`. Restore the backup and run the import again with the right key.

**The page dies partway through** — nothing was changed. The whole import runs in one
transaction and rolls back on any error. Very large dumps can hit PHP's memory limit or MySQL's
`max_allowed_packet`; raise `max_allowed_packet` on the server and try again.

**Duplicate entry for key `work_order_number`** — the dump contains two work orders with the
same number, which LibreWO should not have allowed. Fix it in the dump and re-run.
