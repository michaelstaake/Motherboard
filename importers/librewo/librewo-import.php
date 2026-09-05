<?php
// LibreWO -> Motherboard import.
//
// ############################################################################
// #  DESTRUCTIVE. THIS SCRIPT DELETES EVERYTHING IN THE TARGET DATABASE.     #
// #                                                                          #
// #  Every user, customer, work order, attachment record, log entry and      #
// #  setting in the Motherboard database named below is deleted and          #
// #  replaced with the contents of the LibreWO dump. There is no undo.       #
// #  Take a backup of BOTH databases before you run this.                    #
// ############################################################################
//
// This file is self-contained: it loads nothing from Motherboard and needs no
// Composer packages. Fill in the CONFIGURATION block, copy the file and your
// LibreWO dump into the Motherboard web root, open the file in a browser, and
// DELETE IT AGAIN as soon as the import finishes. While it sits in the web
// root, anyone who can reach the URL can wipe the database.
//
// Motherboard must already be installed (its tables must exist) before this
// runs. See documentation/librewo-migration.md.

// ---------------------------------------------------------------------------
// CONFIGURATION - the only part you edit.
// ---------------------------------------------------------------------------

// Target Motherboard database. Copy these from the site's config.php.
const DB_HOST = 'localhost';
const DB_NAME = 'motherboard';
const DB_USER = '';
const DB_PASS = '';

// The LibreWO mysqldump to read. Absolute, or relative to this file.
const LIBREWO_DUMP = 'librewo.sql';

// Must match APP_ENCRYPTION_KEY in the site's config.php EXACTLY. Device
// passwords and captcha secrets are encrypted with it on the way in; a
// mismatch means Motherboard cannot read them back.
const APP_ENCRYPTION_KEY = '';

// Optional. When set, the page only answers requests carrying ?key=<value>.
// Worth setting if the site is reachable from the internet.
const ACCESS_KEY = '';

// ---------------------------------------------------------------------------
// Nothing below here needs editing.
// ---------------------------------------------------------------------------

@set_time_limit(0);
@ini_set('memory_limit', '512M');

const ENC_PREFIX = 'enc:v1:';
const CONFIRM_PHRASE = 'DELETE';
const BATCH_SIZE = 500;

// Tables emptied before the import. work_order_attachments, login_attempts and
// two_factor_codes have no LibreWO source but are still cleared: leaving stale
// rows behind would point them at users and work orders that no longer exist.
const TARGET_TABLES = [
    'work_order_attachments',
    'work_order_logs',
    'work_orders',
    'activity_logs',
    'user_logins',
    'login_attempts',
    'two_factor_codes',
    'customers',
    'users',
    'settings',
];

// Module tables, cleared when the module happens to be installed. Leaving them
// alone is not an option: work_order_products rows hang off work orders that
// are about to disappear, and foreign key checks are off during the import so
// nothing would cascade them away.
const OPTIONAL_TABLES = [
    'work_order_products',
    'inventory_products',
    'inventory_categories',
];

// Copied row-for-row, in dependency order.
const IMPORT_TABLES = [
    'users',
    'customers',
    'work_orders',
    'work_order_logs',
    'activity_logs',
    'user_logins',
];

// Deliberately not copied. Shown on the confirmation screen so the reason is
// visible before anyone commits to the import.
const SKIPPED_TABLES = [
    'two_factor_codes' => 'One-time codes; LibreWO stored them in a format Motherboard no longer accepts.',
    'login_attempts' => 'Transient rate-limiting state, rebuilt on the next login.',
];

// LibreWO settings keys that mean the same thing in Motherboard. Anything else
// in the dump is reported and left behind.
const SETTINGS_KEYS = [
    'company_name',
    'company_address',
    'company_phone',
    'company_email',
    'company_website',
    'company_logo',
    'company_logo_url',
    'work_order_disclaimer',
    'phone_number_format',
    'captcha_provider',
    'turnstile_site_key',
    'turnstile_secret_key',
    'recaptcha_site_key',
    'recaptcha_secret_key',
    'require_2fa',
    'session_timeout',
    'max_login_attempts',
];

// Settings Motherboard encrypts at rest (models/Settings.php SENSITIVE_KEYS).
const SENSITIVE_SETTINGS = [
    'turnstile_secret_key',
    'recaptcha_secret_key',
];

// Settings Motherboard creates for itself, applied after the import so a
// LibreWO database arrives with the newer keys already present.
const DEFAULT_SETTINGS = [
    'language' => 'en-us',
    'attachment_destination' => 'local',
    'attachment_max_size_mb' => '10',
    'attachment_allowed_extensions' => 'png,jpg,pdf,md,txt',
    'print_customer_signature' => '1',
    'print_technician_signature' => '1',
];

/**
 * Streaming reader for a mysqldump file.
 *
 * The dumps this has to swallow are tens of megabytes with multi-megabyte
 * extended INSERT statements, so nothing is ever held whole in memory: the file
 * is scanned a chunk at a time into complete statements, and each statement is
 * handed back one row at a time.
 */
final class LibreWoDump
{
    private string $path;

    /** @var array<string, string[]> table => column order, learned from CREATE TABLE */
    private array $columns = [];

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Column order for a table, as declared by its CREATE TABLE. Only populated
     * once that statement has been read, so callers iterate rows first.
     *
     * @return string[]
     */
    public function columnsFor(string $table): array
    {
        return $this->columns[$table] ?? [];
    }

    /**
     * Every INSERTed row in the file, as [table, row] pairs where row maps
     * column name to value. Values are PHP strings, or null for SQL NULL.
     *
     * @return Generator<array{0: string, 1: array<string, ?string>}>
     */
    public function rows(): Generator
    {
        foreach ($this->statements() as $statement) {
            if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?\s*\((.*)\)[^)]*$/is', $statement, $m)) {
                $this->columns[$m[1]] = $this->parseCreateColumns($m[2]);
                continue;
            }

            if (!preg_match('/INSERT\s+(?:LOW_PRIORITY\s+|DELAYED\s+|HIGH_PRIORITY\s+)?(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?\s*(?:\(([^)]*)\)\s*)?VALUES\s*/is', $statement, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $table = $m[1][0];
            $columns = isset($m[2]) && $m[2][1] !== -1
                ? array_map(fn($c) => trim($c, " \t\r\n`"), explode(',', $m[2][0]))
                : ($this->columns[$table] ?? []);

            if ($columns === []) {
                continue;
            }

            $offset = $m[0][1] + strlen($m[0][0]);
            foreach ($this->tuples($statement, $offset) as $tuple) {
                // A short tuple means the dump and the column list disagree;
                // pad rather than silently shifting every value one place left.
                if (count($tuple) !== count($columns)) {
                    $tuple = array_pad(array_slice($tuple, 0, count($columns)), count($columns), null);
                }
                yield [$table, array_combine($columns, $tuple)];
            }
        }
    }

    /**
     * Column names from the body of a CREATE TABLE, in declaration order. Key
     * and constraint clauses are not column definitions and are skipped.
     *
     * @return string[]
     */
    private function parseCreateColumns(string $body): array
    {
        $columns = [];
        $depth = 0;
        $line = '';
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $c = $body[$i];
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
            }

            if ($c === ',' && $depth === 0) {
                $columns[] = $line;
                $line = '';
                continue;
            }
            $line .= $c;
        }
        $columns[] = $line;

        $names = [];
        foreach ($columns as $definition) {
            $definition = ltrim($definition);
            if ($definition === '' || $definition[0] !== '`') {
                continue; // PRIMARY KEY / UNIQUE KEY / KEY / CONSTRAINT
            }
            $end = strpos($definition, '`', 1);
            if ($end !== false) {
                $names[] = substr($definition, 1, $end - 1);
            }
        }

        return $names;
    }

    /**
     * Split the file into statements. Semicolons only terminate a statement
     * outside string literals, backtick identifiers and comments, so this
     * tracks which of those it is inside. strcspn/strpos do the skipping, which
     * keeps a multi-megabyte statement from turning into a per-character loop.
     *
     * @return Generator<string>
     */
    private function statements(): Generator
    {
        $handle = @fopen($this->path, 'rb');
        if (!$handle) {
            throw new RuntimeException('Cannot read the dump file.');
        }

        $buffer = '';
        $pos = 0;
        $eof = false;
        $state = 'sql'; // sql | single | double | tick | line_comment | block_comment

        try {
            while (true) {
                if (!$eof && strlen($buffer) - $pos < 65536) {
                    $chunk = fread($handle, 1048576);
                    if ($chunk === false || $chunk === '') {
                        $eof = true;
                    } else {
                        $buffer .= $chunk;
                    }
                }

                $length = strlen($buffer);
                if ($pos >= $length) {
                    if ($eof) {
                        break;
                    }
                    continue;
                }

                if ($state === 'sql') {
                    $pos += strcspn($buffer, "'\"`;-/", $pos);
                    if ($pos >= $length) {
                        if ($eof) {
                            break;
                        }
                        continue;
                    }

                    $c = $buffer[$pos];
                    if ($c === "'") {
                        $state = 'single';
                        $pos++;
                    } elseif ($c === '"') {
                        $state = 'double';
                        $pos++;
                    } elseif ($c === '`') {
                        $state = 'tick';
                        $pos++;
                    } elseif ($c === ';') {
                        $statement = substr($buffer, 0, $pos + 1);
                        $buffer = substr($buffer, $pos + 1);
                        $pos = 0;
                        yield $statement;
                    } elseif ($c === '-') {
                        if ($pos + 2 > $length - 1 && !$eof) {
                            continue;
                        }
                        // "--" only opens a comment when whitespace follows it.
                        $next = $buffer[$pos + 1] ?? '';
                        $after = $buffer[$pos + 2] ?? "\n";
                        if ($next === '-' && strpos(" \t\r\n", $after) !== false) {
                            $state = 'line_comment';
                            $pos += 2;
                        } else {
                            $pos++;
                        }
                    } else { // '/'
                        if ($pos + 1 > $length - 1 && !$eof) {
                            continue;
                        }
                        if (($buffer[$pos + 1] ?? '') === '*') {
                            $state = 'block_comment';
                            $pos += 2;
                        } else {
                            $pos++;
                        }
                    }
                    continue;
                }

                if ($state === 'single' || $state === 'double') {
                    $quote = $state === 'single' ? "'" : '"';
                    $pos += strcspn($buffer, $quote . '\\', $pos);
                    if ($pos >= $length) {
                        if ($eof) {
                            break;
                        }
                        continue;
                    }
                    if ($buffer[$pos] === '\\') {
                        if ($pos + 1 >= $length) {
                            if (!$eof) {
                                continue;
                            }
                            break;
                        }
                        $pos += 2;
                    } else {
                        $pos++;
                        $state = 'sql';
                    }
                    continue;
                }

                $needle = $state === 'tick' ? '`' : ($state === 'line_comment' ? "\n" : '*/');
                $found = strpos($buffer, $needle, $pos);
                if ($found === false) {
                    $pos = max($pos, $length - strlen($needle));
                    if ($eof) {
                        break;
                    }
                    continue;
                }
                $pos = $found + strlen($needle);
                $state = 'sql';
            }
        } finally {
            fclose($handle);
        }

        if (trim($buffer) !== '') {
            yield $buffer;
        }
    }

    /**
     * Walk the "(...),(...)" value list of an INSERT.
     *
     * @return Generator<array<int, ?string>>
     */
    private function tuples(string $sql, int $offset): Generator
    {
        $length = strlen($sql);
        $i = $offset;

        while ($i < $length) {
            $i += strspn($sql, " \t\r\n,", $i);
            if ($i >= $length || $sql[$i] !== '(') {
                return;
            }
            $i++;

            $row = [];
            while ($i < $length) {
                $i += strspn($sql, " \t\r\n", $i);
                if ($i >= $length) {
                    return;
                }

                $c = $sql[$i];
                if ($c === ')') {
                    $i++;
                    break;
                }
                if ($c === ',') {
                    $i++;
                    continue;
                }

                if ($c === "'") {
                    $value = '';
                    $j = $i + 1;
                    while ($j < $length) {
                        $run = strcspn($sql, "'\\", $j);
                        $value .= substr($sql, $j, $run);
                        $j += $run;
                        if ($j >= $length) {
                            break;
                        }
                        if ($sql[$j] === '\\') {
                            $value .= self::unescape($sql[$j + 1] ?? '');
                            $j += 2;
                            continue;
                        }
                        // '' inside a literal is an escaped quote.
                        if (($sql[$j + 1] ?? '') === "'") {
                            $value .= "'";
                            $j += 2;
                            continue;
                        }
                        $j++;
                        break;
                    }
                    $i = $j;
                    $row[] = $value;
                    continue;
                }

                // Bare token: a number, NULL, or an unquoted keyword.
                $run = strcspn($sql, ",)", $i);
                $token = rtrim(substr($sql, $i, $run));
                $i += $run;
                $row[] = strcasecmp($token, 'NULL') === 0 ? null : $token;
            }

            yield $row;
        }
    }

    private static function unescape(string $c): string
    {
        return match ($c) {
            '0' => "\0",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'b' => "\x08",
            'Z' => "\x1a",
            // MySQL keeps the backslash on \% and \_ so LIKE patterns survive.
            '%' => '\\%',
            '_' => '\\_',
            default => $c,
        };
    }
}

/**
 * Port of Motherboard's Crypto::encrypt() (public_html/core/Crypto.php). The
 * format has to match byte for byte or the app cannot read what we write:
 * aes-256-gcm, 12-byte nonce, 16-byte tag, prefix doubling as the AAD, all of
 * it base64'd behind "enc:v1:".
 */
function mb_encrypt(?string $value): ?string
{
    if ($value === null || $value === '' || str_starts_with($value, ENC_PREFIX)) {
        return $value;
    }

    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
        $value,
        'aes-256-gcm',
        hash('sha256', APP_ENCRYPTION_KEY, true),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        ENC_PREFIX,
        16
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Unable to encrypt sensitive data.');
    }

    return ENC_PREFIX . base64_encode($nonce . $tag . $ciphertext);
}

/** Absolute path to the configured dump, relative paths resolved next to this file. */
function mb_dump_path(): string
{
    $configured = trim(LIBREWO_DUMP);
    if ($configured === '') {
        return '';
    }
    if ($configured[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $configured)) {
        return $configured;
    }

    return __DIR__ . '/' . $configured;
}

function mb_connect(): PDO
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

/**
 * Every table the import empties: the core set, plus whichever module tables
 * this install actually has.
 *
 * @return string[]
 */
function mb_tables_to_clear(PDO $pdo): array
{
    $tables = TARGET_TABLES;
    foreach (OPTIONAL_TABLES as $table) {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        if ($stmt && $stmt->rowCount() > 0) {
            $tables[] = $table;
        }
    }

    return $tables;
}

/** @return string[] */
function mb_table_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
    return array_column($stmt->fetchAll(), 'Field');
}

/**
 * Everything that has to be true before the import can be offered. Each entry
 * is [passed, label, detail]; a single failure hides the confirm button.
 *
 * @return array<int, array{ok: bool, label: string, detail: string}>
 */
function mb_preflight(?PDO &$pdo): array
{
    $checks = [];
    $add = function (bool $ok, string $label, string $detail) use (&$checks): void {
        $checks[] = ['ok' => $ok, 'label' => $label, 'detail' => $detail];
    };

    $add(
        extension_loaded('pdo_mysql'),
        'PHP pdo_mysql extension',
        extension_loaded('pdo_mysql') ? 'Loaded.' : 'Not loaded. Motherboard needs it too, so this is a server problem.'
    );
    $add(
        extension_loaded('openssl'),
        'PHP openssl extension',
        extension_loaded('openssl') ? 'Loaded.' : 'Not loaded. Device passwords cannot be encrypted without it.'
    );

    $key = trim(APP_ENCRYPTION_KEY);
    $keyOk = strlen($key) >= 32 && !str_starts_with($key, 'replace-');
    $add(
        $keyOk,
        'APP_ENCRYPTION_KEY',
        $keyOk
            ? 'Set. It must be the same value as the site config.php, character for character.'
            : 'Missing, too short, or still the sample placeholder. Copy it from the site config.php.'
    );

    $dump = mb_dump_path();
    $dumpOk = $dump !== '' && is_file($dump) && is_readable($dump);
    $add(
        $dumpOk,
        'LibreWO dump file',
        $dumpOk
            ? $dump . ' (' . number_format(filesize($dump) / 1048576, 1) . ' MB)'
            : ($dump === '' ? 'No file configured in LIBREWO_DUMP.' : 'Cannot read ' . $dump)
    );

    $connected = false;
    try {
        $pdo = mb_connect();
        $connected = true;
        $add(true, 'Motherboard database', 'Connected to ' . DB_NAME . ' on ' . DB_HOST . ' as ' . DB_USER . '.');
    } catch (Throwable $e) {
        $pdo = null;
        $add(false, 'Motherboard database', 'Cannot connect: ' . $e->getMessage());
    }

    if ($connected) {
        $missing = [];
        foreach (TARGET_TABLES as $table) {
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
            if (!$stmt || $stmt->rowCount() === 0) {
                $missing[] = $table;
            }
        }
        $add(
            $missing === [],
            'Motherboard is installed',
            $missing === []
                ? 'All ' . count(TARGET_TABLES) . ' tables are present.'
                : 'Install Motherboard first (open the site and complete the installer), then run this again. Missing: ' . implode(', ', $missing)
        );

        if ($missing === []) {
            // Encrypting a device password makes it roughly twice as long, so
            // LibreWO's VARCHAR(100) column would truncate it into garbage.
            $type = $pdo->prepare(
                'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $type->execute(['work_orders', 'password']);
            $columnType = strtolower((string) $type->fetchColumn());
            $add(
                str_starts_with($columnType, 'text'),
                'work_orders.password is TEXT',
                str_starts_with($columnType, 'text')
                    ? 'Wide enough for encrypted values.'
                    : 'Found ' . ($columnType ?: 'nothing') . '. This looks like a LibreWO database rather than a Motherboard one. Load the Motherboard site once so it can upgrade the column, then run this again.'
            );
        }
    }

    return $checks;
}

/**
 * Row counts per table in the dump. This is a full parse, which is also the
 * cheapest honest way to tell the admin what they are about to trade away.
 *
 * @return array<string, int>
 */
function mb_dump_counts(string $path): array
{
    $counts = [];
    $dump = new LibreWoDump($path);
    foreach ($dump->rows() as [$table, $row]) {
        $counts[$table] = ($counts[$table] ?? 0) + 1;
    }
    ksort($counts);

    return $counts;
}

/** @return array<string, int> */
function mb_live_counts(PDO $pdo): array
{
    $counts = [];
    foreach (mb_tables_to_clear($pdo) as $table) {
        $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }
    ksort($counts);

    return $counts;
}

/**
 * Empty the target database and refill it from the dump.
 *
 * Everything runs inside one transaction with foreign key checks off, so the
 * tables can be filled in any order and a failure anywhere leaves the database
 * exactly as it was found.
 *
 * @param callable(string): void $progress
 * @return array{imported: array<string, int>, settings: array<string, int>, unknown_settings: string[], modules: string[]}
 */
function mb_import(PDO $pdo, string $dumpPath, callable $progress): array
{
    $liveColumns = [];
    foreach (IMPORT_TABLES as $table) {
        $liveColumns[$table] = mb_table_columns($pdo, $table);
    }

    $cleared = mb_tables_to_clear($pdo);
    $imported = array_fill_keys(IMPORT_TABLES, 0);
    $settingRows = [];
    $unknownSettings = [];
    $batches = array_fill_keys(IMPORT_TABLES, []);
    $statements = [];

    $flush = function (string $table, bool $force) use (&$batches, &$statements, &$imported, $pdo, $liveColumns, $progress): void {
        $rows = $batches[$table];
        if ($rows === [] || (!$force && count($rows) < BATCH_SIZE)) {
            return;
        }

        $columns = array_values(array_intersect(array_keys($rows[0]), $liveColumns[$table]));
        $key = $table . ':' . count($rows) . ':' . implode(',', $columns);

        if (!isset($statements[$key])) {
            $tuple = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $statements[$key] = $pdo->prepare(
                'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES '
                . implode(',', array_fill(0, count($rows), $tuple))
            );
        }

        $values = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $values[] = $row[$column] ?? null;
            }
        }

        $statements[$key]->execute($values);
        $imported[$table] += count($rows);
        $batches[$table] = [];
        $progress($table . ': ' . number_format($imported[$table]) . ' rows');
    };

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->beginTransaction();

    try {
        foreach ($cleared as $table) {
            $deleted = $pdo->exec('DELETE FROM `' . $table . '`');
            $progress('Cleared ' . $table . ' (' . number_format((int) $deleted) . ' rows deleted)');
        }

        $dump = new LibreWoDump($dumpPath);
        foreach ($dump->rows() as [$table, $row]) {
            if ($table === 'settings') {
                $key = (string) ($row['setting_key'] ?? '');
                if ($key === '') {
                    continue;
                }
                if (!in_array($key, SETTINGS_KEYS, true)) {
                    $unknownSettings[] = $key;
                    continue;
                }
                $settingRows[$key] = $row;
                continue;
            }

            if (!isset($batches[$table])) {
                continue; // a table we deliberately do not carry across
            }

            if ($table === 'work_orders' && isset($row['password'])) {
                // LibreWO kept device passwords in the clear; Motherboard does not.
                $row['password'] = mb_encrypt($row['password']);
            }

            $batches[$table][] = $row;
            $flush($table, false);
        }

        foreach (IMPORT_TABLES as $table) {
            $flush($table, true);
        }

        $settingsResult = mb_import_settings($pdo, $settingRows);
        $progress('settings: ' . number_format($settingsResult['imported']) . ' imported, '
            . number_format($settingsResult['defaults']) . ' Motherboard defaults added');

        $modules = mb_enable_modules($pdo, $settingRows);
        $progress($modules === [] ? 'No modules to enable' : 'Enabled modules: ' . implode(', ', $modules));

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        throw $e;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // ALTER is DDL and commits implicitly, so it waits until after the import.
    // Tables we cleared but never refilled would otherwise keep counting from
    // wherever the old install left off.
    foreach (array_diff($cleared, IMPORT_TABLES, ['settings']) as $table) {
        $pdo->exec('ALTER TABLE `' . $table . '` AUTO_INCREMENT = 1');
    }

    return [
        'imported' => $imported,
        'settings' => $settingsResult,
        'unknown_settings' => array_values(array_unique($unknownSettings)),
        'modules' => $modules,
    ];
}

/**
 * @param array<string, array<string, ?string>> $settingRows
 * @return array{imported: int, defaults: int}
 */
function mb_import_settings(PDO $pdo, array $settingRows): array
{
    $now = date('Y-m-d H:i:s');
    $insert = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?)'
    );

    $count = 0;
    foreach ($settingRows as $key => $row) {
        $value = $row['setting_value'] ?? null;
        if (in_array($key, SENSITIVE_SETTINGS, true)) {
            $value = mb_encrypt($value === null ? null : (string) $value);
        }
        $insert->execute([
            $key,
            $value,
            $row['created_at'] ?? $now,
            $row['updated_at'] ?? $now,
        ]);
        $count++;
    }

    $defaults = 0;
    foreach (DEFAULT_SETTINGS as $key => $value) {
        if (isset($settingRows[$key])) {
            continue;
        }
        $insert->execute([$key, $value, $now, $now]);
        $defaults++;
    }

    return ['imported' => $count, 'defaults' => $defaults];
}

/**
 * LibreWO kept captcha and 2FA configuration in core settings. In Motherboard
 * those features are modules, so a straight settings copy would leave a shop
 * with its Turnstile keys present but Turnstile switched off. Turn on whatever
 * the old install was actually using.
 *
 * @param array<string, array<string, ?string>> $settingRows
 * @return string[]
 */
function mb_enable_modules(PDO $pdo, array $settingRows): array
{
    $value = static fn(string $key): string => (string) ($settingRows[$key]['setting_value'] ?? '');

    $modules = [];
    $provider = strtolower(trim($value('captcha_provider')));
    if ($provider === 'turnstile' && $value('turnstile_site_key') !== '') {
        $modules[] = 'cloudflare-turnstile';
    } elseif ($provider === 'recaptcha' && $value('recaptcha_site_key') !== '') {
        $modules[] = 'google-recaptcha';
    }
    if ($value('require_2fa') === '1') {
        $modules[] = 'email-2fa';
    }

    if ($modules === []) {
        return [];
    }

    $now = date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?)')
        ->execute(['enabled_modules', json_encode($modules), $now, $now]);

    return $modules;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mb_page_header(string $subtitle): void
{
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>LibreWO import</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 2rem 1rem; background: #f3f4f6; color: #111827;
         font: 15px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
  .wrap { max-width: 780px; margin: 0 auto; }
  .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.25rem; }
  h1 { margin: 0 0 .25rem; font-size: 1.5rem; }
  h2 { margin: 0 0 .75rem; font-size: 1.05rem; }
  .sub { margin: 0 0 1.5rem; color: #6b7280; }
  .danger { background: #fef2f2; border: 2px solid #dc2626; }
  .danger h2 { color: #991b1b; }
  .danger ul { margin: .5rem 0 0; padding-left: 1.25rem; color: #7f1d1d; }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; padding: .45rem .5rem; border-bottom: 1px solid #f3f4f6; }
  th { color: #6b7280; font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
  td.num { text-align: right; font-variant-numeric: tabular-nums; }
  .gone { color: #b91c1c; }
  .check { display: flex; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid #f3f4f6; }
  .check:last-child { border-bottom: 0; }
  .mark { flex: none; width: 1.25rem; font-weight: 700; }
  .ok .mark { color: #15803d; }
  .bad .mark { color: #b91c1c; }
  .check .detail { color: #6b7280; font-size: .875rem; }
  code { background: #f3f4f6; padding: .1rem .3rem; border-radius: 4px; font-size: .875em; }
  label { display: block; font-weight: 600; margin-bottom: .4rem; }
  input[type=text] { width: 100%; max-width: 18rem; padding: .6rem .7rem; font-size: 1rem;
                     border: 2px solid #d1d5db; border-radius: 6px; }
  button { margin-top: 1rem; padding: .7rem 1.4rem; font-size: 1rem; font-weight: 600; color: #fff;
           background: #dc2626; border: 0; border-radius: 6px; cursor: pointer; }
  button[disabled] { background: #d1d5db; cursor: not-allowed; }
  .log { background: #111827; color: #d1d5db; border-radius: 8px; padding: 1rem;
         font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem;
         white-space: pre-wrap; max-height: 22rem; overflow-y: auto; }
  .note { color: #6b7280; font-size: .875rem; }
</style>
</head>
<body>
<div class="wrap">
<h1>LibreWO &rarr; Motherboard import</h1>
<p class="sub"><?= e($subtitle) ?></p>
<?php
}

function mb_page_footer(): void
{
    echo "</div>\n</body>\n</html>\n";
}

// ---------------------------------------------------------------------------
// Request handling
// ---------------------------------------------------------------------------

if (ACCESS_KEY !== '' && !hash_equals(ACCESS_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit("Not found\n");
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$pdo = null;
$checks = mb_preflight($pdo);
$ready = !in_array(false, array_column($checks, 'ok'), true);
$confirmed = $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['token'], $_SESSION['librewo_import_token'])
    && hash_equals((string) $_SESSION['librewo_import_token'], (string) $_POST['token'])
    && strtoupper(trim((string) ($_POST['confirm'] ?? ''))) === CONFIRM_PHRASE;

if ($ready && $confirmed) {
    unset($_SESSION['librewo_import_token']);

    mb_page_header('Importing. Do not close this page or reload it.');
    echo '<div class="card"><h2>Progress</h2><div class="log">';
    @ob_implicit_flush(true);
    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    $progress = function (string $line): void {
        echo e($line), "\n";
        flush();
    };

    try {
        $started = microtime(true);
        $result = mb_import($pdo, mb_dump_path(), $progress);
        $progress('Done in ' . number_format(microtime(true) - $started, 1) . 's.');
        echo '</div></div>';

        echo '<div class="card"><h2>Imported</h2><table><tr><th>Table</th><th class="num">Rows</th></tr>';
        foreach ($result['imported'] as $table => $count) {
            echo '<tr><td><code>' . e($table) . '</code></td><td class="num">' . number_format($count) . '</td></tr>';
        }
        echo '<tr><td><code>settings</code></td><td class="num">' . number_format($result['settings']['imported']) . '</td></tr>';
        echo '</table>';
        echo '<p class="note">' . number_format($result['settings']['defaults'])
            . ' Motherboard-only settings were added with their default values.';
        if ($result['modules'] !== []) {
            echo ' Modules enabled to match the old configuration: <code>'
                . implode('</code>, <code>', array_map('e', $result['modules'])) . '</code>.';
        }
        if ($result['unknown_settings'] !== []) {
            echo ' Settings Motherboard has no home for were left behind: <code>'
                . implode('</code>, <code>', array_map('e', $result['unknown_settings'])) . '</code>.';
        }
        echo '</p></div>';

        $captcha = array_intersect($result['modules'], ['cloudflare-turnstile', 'google-recaptcha']);
        if ($captcha !== []) {
            echo '<div class="card danger"><h2>Check the captcha before you close this page</h2>'
                . '<p>The sign-in page now carries the captcha the old install used. If those keys '
                . 'were issued for a different hostname than this site, nobody can sign in. Open '
                . 'the login page in another tab and make sure the widget appears and clears.</p>'
                . '<p>If it does not, turn the captcha off from the database:</p>'
                . '<p><code>UPDATE settings SET setting_value = \'[]\' WHERE setting_key = \'enabled_modules\';</code></p></div>';
        }

        echo '<div class="card danger"><h2>Delete this file now</h2><p>Remove <code>'
            . e(basename(__FILE__)) . '</code> and <code>' . e(basename(mb_dump_path()))
            . '</code> from the web root. Anyone who can reach this URL can wipe the database with it.</p></div>';

        echo '<div class="card"><h2>Next</h2><p>Open the Motherboard site and sign in. '
            . 'LibreWO password hashes carry over unchanged, so existing passwords still work; '
            . 'anyone who has forgotten theirs can use <em>Forgot password</em>.</p></div>';
    } catch (Throwable $e) {
        echo e('FAILED: ' . $e->getMessage()), '</div></div>';
        echo '<div class="card danger"><h2>Nothing was changed</h2><p>The import runs in a single '
            . 'transaction and it was rolled back. The database is exactly as it was before you '
            . 'started. Fix the problem above and run this again.</p></div>';
    }

    mb_page_footer();
    exit;
}

$token = bin2hex(random_bytes(16));
$_SESSION['librewo_import_token'] = $token;

$dumpCounts = [];
$liveCounts = [];
$scanError = '';
if ($ready) {
    try {
        $dumpCounts = mb_dump_counts(mb_dump_path());
        $liveCounts = mb_live_counts($pdo);
    } catch (Throwable $e) {
        $ready = false;
        $scanError = $e->getMessage();
    }
}

mb_page_header($ready
    ? 'Read this page before you do anything else.'
    : 'Something needs fixing before this can run.');
?>

<div class="card danger">
  <h2>This will destroy the current database</h2>
  <p>Importing <strong>deletes everything</strong> in the Motherboard database
     <code><?= e(DB_NAME) ?></code> on <code><?= e(DB_HOST) ?></code> and replaces it with the
     contents of the LibreWO dump. There is no undo.</p>
  <ul>
    <li>Every user account, including the one you installed with</li>
    <li>Every customer and work order</li>
    <li>Every attachment record (files on disk are left orphaned)</li>
    <li>Every activity log, work order log and login record</li>
    <li>Every setting, including company details and module configuration</li>
  </ul>
  <p><strong>Back up both databases before continuing.</strong></p>
</div>

<div class="card">
  <h2>Checks</h2>
  <?php foreach ($checks as $check): ?>
    <div class="check <?= $check['ok'] ? 'ok' : 'bad' ?>">
      <span class="mark"><?= $check['ok'] ? '&check;' : '&times;' ?></span>
      <span><?= e($check['label']) ?><br><span class="detail"><?= e($check['detail']) ?></span></span>
    </div>
  <?php endforeach; ?>
  <?php if ($scanError !== ''): ?>
    <div class="check bad">
      <span class="mark">&times;</span>
      <span>Reading the dump<br><span class="detail"><?= e($scanError) ?></span></span>
    </div>
  <?php endif; ?>
</div>

<?php if ($ready): ?>
<div class="card">
  <h2>What changes</h2>
  <table>
    <tr><th>Table</th><th class="num">Deleted</th><th class="num">Imported</th></tr>
    <?php foreach ($liveCounts as $table => $live):
        $incoming = $dumpCounts[$table] ?? 0;
        $skipped = array_key_exists($table, SKIPPED_TABLES);
    ?>
    <tr>
      <td><code><?= e($table) ?></code><?php if ($skipped): ?><br><span class="detail note"><?= e(SKIPPED_TABLES[$table]) ?></span><?php endif; ?></td>
      <td class="num <?= $live > 0 ? 'gone' : '' ?>"><?= number_format($live) ?></td>
      <td class="num"><?= $skipped ? '&mdash;' : number_format($incoming) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="note">Original record IDs and work order numbers are preserved. Device passwords and
     captcha secrets are encrypted with <code>APP_ENCRYPTION_KEY</code> as they are written.</p>
</div>

<form method="post" class="card danger">
  <h2>Confirm</h2>
  <p>Type <strong><?= CONFIRM_PHRASE ?></strong> to confirm you have a backup and want to erase
     the current database.</p>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <label for="confirm">Type <?= CONFIRM_PHRASE ?></label>
  <input type="text" id="confirm" name="confirm" autocomplete="off" autocapitalize="off"
         spellcheck="false" oninput="document.getElementById('go').disabled =
         this.value.trim().toUpperCase() !== '<?= CONFIRM_PHRASE ?>';">
  <button type="submit" id="go" disabled>Erase and import</button>
  <p class="note">The button stays disabled until the word matches. The check is repeated on the
     server, so it also holds with JavaScript off.</p>
</form>
<?php endif; ?>

<?php
mb_page_footer();
