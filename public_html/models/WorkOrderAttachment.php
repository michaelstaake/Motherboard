<?php
require_once 'core/Model.php';
require_once 'models/Settings.php';

class WorkOrderAttachment extends Model {
    protected $table = 'work_order_attachments';

    /**
     * Extensions that are never accepted, regardless of the configured allow list.
     * These are either executable by a web server or able to alter its configuration,
     * so allowing them would turn the attachment store into a code execution path.
     */
    private const DENIED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phpt',
        'pht', 'phtm', 'phtml', 'phar', 'inc',
        'htaccess', 'htpasswd', 'user', 'ini', 'conf',
        'htm', 'html', 'xhtml', 'shtml', 'shtm', 'svg', 'svgz', 'xml', 'xsl',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'exe', 'dll', 'so',
        'jsp', 'jspx', 'asp', 'aspx', 'ashx', 'asmx', 'cfm', 'hta',
    ];

    /**
     * Acceptable detected MIME types per extension. An extension listed here must have
     * content matching one of its types; an extension absent from the map is not
     * content-checked, so uncommon but legitimate file types still upload.
     */
    private const EXTENSION_MIME_MAP = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'bmp'  => ['image/bmp', 'image/x-ms-bmp'],
        'tif'  => ['image/tiff'],
        'tiff' => ['image/tiff'],
        'heic' => ['image/heic', 'image/heif'],
        'pdf'  => ['application/pdf'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/csv', 'text/plain'],
        'log'  => ['text/plain'],
        'zip'  => ['application/zip'],
        'gz'   => ['application/gzip', 'application/x-gzip'],
        'doc'  => ['application/msword', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/x-ole-storage'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    public static function isDeniedExtension(string $ext): bool {
        return in_array(strtolower(ltrim($ext, '.')), self::DENIED_EXTENSIONS, true);
    }

    public static function storagePath(): string {
        return ROOT_PATH . '/attachments';
    }

    public function getByWorkOrder($workOrderId) {
        return $this->findWhere('work_order_id = ? ORDER BY created_at ASC', [$workOrderId]);
    }

    public function getAttachmentById($id) {
        return $this->findById($id);
    }

    public function getSettings(): array {
        $settings = new Settings();
        $maxMb = (int) $settings->getSetting('attachment_max_size_mb', 10);
        if ($maxMb < 1) {
            $maxMb = 1;
        }

        return [
            'destination' => $settings->getSetting('attachment_destination', 'local') ?: 'local',
            'max_size_mb' => $maxMb,
            'max_size_bytes' => $maxMb * 1024 * 1024,
            'allowed_extensions' => $settings->getSetting('attachment_allowed_extensions', 'png,jpg,pdf,md,txt'),
        ];
    }

    public function availableDestinations(): array {
        return Hooks::applyFilters('attachment.destinations', [
            'local' => t('settings.attachment_destination_local'),
        ]);
    }

    public function currentDestination(): string {
        $destination = $this->getSettings()['destination'] ?: 'local';
        $available = array_keys($this->availableDestinations());
        return in_array($destination, $available, true) ? $destination : 'local';
    }

    public function destinationOf(array $attachment): string {
        $destination = $attachment['storage_destination'] ?? 'local';
        return $destination !== '' ? $destination : 'local';
    }

    public function locationLabel(array $attachment): string {
        $destination = $this->destinationOf($attachment);
        $labels = Hooks::applyFilters('attachment.location_labels', [
            'local' => t('wo.attachment_location.local'),
            's3' => t('wo.attachment_location.s3'),
        ]);
        if (!empty($labels[$destination]) && is_string($labels[$destination])) {
            return $labels[$destination];
        }
        return t('wo.attachment_location.module', ['name' => $destination]);
    }

    public function allowedExtensions(): array {
        $raw = $this->getSettings()['allowed_extensions'] ?? 'png,jpg,pdf,md,txt';
        $parts = array_map('trim', explode(',', strtolower((string) $raw)));
        $parts = array_values(array_filter($parts, function ($part) {
            return $part !== '';
        }));
        return $parts ?: ['png', 'jpg', 'pdf', 'md', 'txt'];
    }

    public function allowsAllTypes(): bool {
        return in_array('%', $this->allowedExtensions(), true);
    }

    public function allowedExtensionsLabel(): string {
        if ($this->allowsAllTypes()) {
            return t('settings.attachment_all_types');
        }
        return implode(', ', $this->allowedExtensions());
    }

    public function maxSizeBytes(): int {
        $configured = $this->getSettings()['max_size_bytes'];
        $phpLimit = self::usablePhpUploadLimitBytes();
        if ($phpLimit > 0 && $phpLimit < $configured) {
            return $phpLimit;
        }
        return $configured;
    }

    public static function iniBytes($value): int {
        $value = strtolower(trim((string) $value));
        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }
        $unit = substr($value, -1);
        $number = (float) $value;
        $multipliers = [
            'k' => 1024,
            'm' => 1048576,
            'g' => 1073741824,
        ];
        if (isset($multipliers[$unit])) {
            return (int) ($number * $multipliers[$unit]);
        }
        return (int) $number;
    }

    public static function phpUploadLimitBytes(): int {
        $limits = [];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes(ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }
        return $limits ? min($limits) : 0;
    }

    public static function usablePhpUploadLimitBytes(): int {
        $limit = self::phpUploadLimitBytes();
        if ($limit < 1) {
            return 0;
        }
        $postMax = self::iniBytes(ini_get('post_max_size'));
        if ($postMax > 0 && $postMax <= $limit) {
            $limit = max(1024, $postMax - 65536);
        }
        return $limit;
    }

    public static function requestExceededPostLimit(): bool {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return false;
        }
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length < 1) {
            return false;
        }
        $postMax = self::iniBytes(ini_get('post_max_size'));
        if ($postMax > 0 && $length > $postMax) {
            return true;
        }

        // PHP only auto-populates $_POST/$_FILES for these two content types, so an empty
        // $_POST is the truncation signal only for them; for anything else (e.g. a JSON API
        // request) an empty $_POST is normal and doesn't mean the body was dropped.
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $isFormBody = str_starts_with($contentType, 'application/x-www-form-urlencoded')
            || str_starts_with($contentType, 'multipart/form-data');
        if (!$isFormBody) {
            return false;
        }

        return empty($_POST) && empty($_FILES);
    }

    public function formatSize($bytes): string {
        $bytes = (int) $bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isDisplayableImage(array $attachment): bool {
        $mime = $attachment['mime_type'] ?? '';
        return in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true);
    }

    /**
     * Which lightbox preview (if any) an attachment supports: 'image', 'pdf', 'text', or ''
     * for types with no in-browser preview. Text is extension-gated rather than mime-gated
     * because finfo reports .md and .txt alike as text/plain (or occasionally
     * application/octet-stream for unusual encodings), so mime alone can't tell them apart
     * from other text/plain uploads we don't want to preview (e.g. .csv, .log).
     */
    public function previewType(array $attachment): string {
        if ($this->isDisplayableImage($attachment)) {
            return 'image';
        }
        $mime = $attachment['mime_type'] ?? '';
        if ($mime === 'application/pdf') {
            return 'pdf';
        }
        $ext = strtolower(pathinfo($attachment['original_filename'] ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'md'], true) && ($mime === 'text/plain' || $mime === 'application/octet-stream')) {
            return 'text';
        }
        return '';
    }

    public function absolutePath(array $attachment): string {
        return self::storagePath() . '/' . self::safeRelativeKey($attachment['stored_path'] ?? '');
    }

    /**
     * Normalises a stored_path into a relative key that cannot escape the storage root.
     *
     * stored_path is server-generated today, so this is not currently reachable — but it is
     * the only thing between a buggy attachment.storage.* module and arbitrary file
     * read/delete through downloadAttachment()/deleteAttachment().
     */
    private static function safeRelativeKey(string $storedPath): string {
        $path = ltrim(str_replace('\\', '/', $storedPath), '/');

        $safe = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $safe[] = $segment;
        }

        return implode('/', $safe);
    }

    public function localReadablePath(array $attachment): ?string {
        $destination = $this->destinationOf($attachment);
        $key = ltrim(str_replace('\\', '/', $attachment['stored_path'] ?? ''), '/');
        if ($key === '') {
            return null;
        }

        if ($destination === 'local') {
            $path = $this->absolutePath($attachment);
            if (!is_file($path)) {
                return null;
            }
            // Final containment assertion: resolve symlinks and confirm the file really
            // sits under the storage root before handing the path to a reader.
            $real = realpath($path);
            $root = realpath(self::storagePath());
            if ($real === false || $root === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
                return null;
            }
            return $real;
        }

        $result = Hooks::applyFilters('attachment.storage.fetch', [
            'handled' => false,
            'ok' => false,
            'path' => '',
            'error' => '',
        ], $destination, $key, $attachment);

        if (!empty($result['handled']) && !empty($result['ok']) && !empty($result['path']) && is_file($result['path'])) {
            return $result['path'];
        }

        return null;
    }

    public function storePendingUpload(array $file, string $description): array {
        $this->validateUpload($file);
        $this->ensureStorage();

        $sessionKey = $this->sessionStorageKey();
        $dir = self::storagePath() . '/pending/' . $sessionKey;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        $original = $this->sanitizeOriginalName($file['name'] ?? '');
        $stored = $this->uniqueStoredName($original);
        $dest = $dir . '/' . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        return [
            'token' => bin2hex(random_bytes(8)),
            'original_filename' => $original,
            'stored_filename' => $stored,
            'stored_path' => 'pending/' . $sessionKey . '/' . $stored,
            'description' => $description,
            'mime_type' => $this->detectMime($dest),
            'file_size' => filesize($dest) ?: 0,
            'storage_destination' => 'local',
        ];
    }

    public function removePendingUpload(array $pending): void {
        if (empty($pending['stored_path'])) {
            return;
        }
        $path = self::storagePath() . '/' . ltrim($pending['stored_path'], '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function finalizePending(int $workOrderId, array $pendingList, $userId = null): void {
        if (empty($pendingList)) {
            return;
        }

        $this->ensureStorage();
        $destination = $this->currentDestination();
        if ($destination === 'local') {
            $targetDir = self::storagePath() . '/' . $workOrderId;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new Exception(t('wo.attachment_upload_fail'));
            }
        }

        foreach ($pendingList as $item) {
            $relative = ltrim($item['stored_path'] ?? '', '/');
            $source = self::storagePath() . '/' . $relative;
            if (!is_file($source)) {
                continue;
            }

            $stored = $item['stored_filename'] ?? basename($source);
            $destRelative = $workOrderId . '/' . $stored;
            $mime = $item['mime_type'] ?? $this->detectMime($source);
            $size = $item['file_size'] ?? (filesize($source) ?: 0);

            $this->persistFile($destination, $destRelative, $source, [
                'mime_type' => $mime,
                'original_filename' => $item['original_filename'] ?? $stored,
                'file_size' => $size,
            ]);

            $this->create([
                'work_order_id' => $workOrderId,
                'original_filename' => $item['original_filename'] ?? $stored,
                'stored_path' => $destRelative,
                'storage_destination' => $destination,
                'description' => $item['description'] ?? '',
                'mime_type' => $mime,
                'file_size' => $size,
                'uploaded_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $pendingDir = self::storagePath() . '/pending/' . $this->sessionStorageKey();
        if (is_dir($pendingDir)) {
            @rmdir($pendingDir);
        }
    }

    public function createFromUpload(int $workOrderId, array $file, string $description, $userId = null): int {
        $this->validateUpload($file);
        $this->ensureStorage();

        $destination = $this->currentDestination();
        $original = $this->sanitizeOriginalName($file['name'] ?? '');
        $stored = $this->uniqueStoredName($original);
        $relative = $workOrderId . '/' . $stored;
        $source = $file['tmp_name'];
        $mime = $this->detectMime($source);
        $size = filesize($source) ?: 0;

        if ($destination === 'local') {
            $targetDir = self::storagePath() . '/' . $workOrderId;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            $dest = self::storagePath() . '/' . $relative;
            if (!move_uploaded_file($source, $dest)) {
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            $mime = $this->detectMime($dest);
            $size = filesize($dest) ?: 0;
        } else {
            $this->persistFile($destination, $relative, $source, [
                'mime_type' => $mime,
                'original_filename' => $original,
                'file_size' => $size,
            ]);
        }

        return (int) $this->create([
            'work_order_id' => $workOrderId,
            'original_filename' => $original,
            'stored_path' => $relative,
            'storage_destination' => $destination,
            'description' => $description,
            'mime_type' => $mime,
            'file_size' => $size,
            'uploaded_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateDescription($id, string $description): bool {
        return $this->update($id, ['description' => $description]);
    }

    public function deleteAttachment($id): bool {
        $attachment = $this->findById($id);
        if (!$attachment) {
            return false;
        }

        $this->deleteStoredAttachment($attachment);

        $result = $this->delete($id);

        if ($this->destinationOf($attachment) === 'local') {
            $dir = dirname($this->absolutePath($attachment));
            if (is_dir($dir) && $this->isEmptyDir($dir)) {
                @rmdir($dir);
            }
        }

        return $result;
    }

    public function deleteForWorkOrder($workOrderId): void {
        $attachments = $this->getByWorkOrder($workOrderId);
        $stmt = $this->db->prepare('DELETE FROM work_order_attachments WHERE work_order_id = ?');
        $stmt->execute([$workOrderId]);
        $this->removeStoredFiles($workOrderId, $attachments);
    }

    public function removeStoredFiles($workOrderId, array $attachments): void {
        foreach ($attachments as $attachment) {
            $this->deleteStoredAttachment($attachment);
        }

        $dir = self::storagePath() . '/' . intval($workOrderId);
        if (is_dir($dir)) {
            $this->removeDirectory($dir);
        }
    }

    public function validateUpload(array $file): void {
        if (self::requestExceededPostLimit()) {
            throw new Exception(t('wo.attachment_too_large', ['size' => $this->formatSize($this->maxSizeBytes())]));
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            throw new Exception(t('wo.attachment_required'));
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception(t('wo.attachment_too_large', ['size' => $this->formatSize($this->maxSizeBytes())]));
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size < 1) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        if ($size > $this->maxSizeBytes()) {
            throw new Exception(t('wo.attachment_too_large', ['size' => $this->formatSize($this->maxSizeBytes())]));
        }

        $ext = $this->extensionOf($file['name'] ?? '');
        if (!$this->isExtensionAllowed($ext)) {
            throw new Exception(t('wo.attachment_type_denied', ['types' => $this->allowedExtensionsLabel()]));
        }

        if (!$this->isMimeConsistent($ext, $file['tmp_name'])) {
            throw new Exception(t('wo.attachment_content_mismatch', ['ext' => $ext]));
        }
    }

    public static function normalizeExtensions(string $raw): string {
        $parts = array_map('trim', explode(',', strtolower($raw)));
        $normalized = [];

        foreach ($parts as $part) {
            $part = ltrim($part, '.');
            if ($part === '') {
                continue;
            }
            if ($part === '%') {
                if (!in_array('%', $normalized, true)) {
                    $normalized[] = '%';
                }
                continue;
            }
            if (!preg_match('/^[a-z0-9]{1,16}$/', $part)) {
                continue;
            }
            if (self::isDeniedExtension($part)) {
                continue;
            }
            if (!in_array($part, $normalized, true)) {
                $normalized[] = $part;
            }
        }

        return implode(',', $normalized);
    }

    private function persistFile(string $destination, string $key, string $sourcePath, array $meta): void {
        if ($destination === 'local') {
            $dest = self::storagePath() . '/' . ltrim($key, '/');
            $dir = dirname($dest);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            if ($sourcePath !== $dest && !@rename($sourcePath, $dest) && !@copy($sourcePath, $dest)) {
                throw new Exception(t('wo.attachment_upload_fail'));
            }
            if ($sourcePath !== $dest && is_file($sourcePath)) {
                @unlink($sourcePath);
            }
            return;
        }

        $result = Hooks::applyFilters('attachment.storage.put', [
            'handled' => false,
            'ok' => false,
            'error' => '',
        ], $destination, $key, $sourcePath, $meta);

        if (empty($result['handled'])) {
            throw new Exception(t('wo.attachment_storage_unavailable'));
        }
        if (empty($result['ok'])) {
            throw new Exception(!empty($result['error']) ? $result['error'] : t('wo.attachment_upload_fail'));
        }

        if (is_file($sourcePath) && str_starts_with(str_replace('\\', '/', $sourcePath), str_replace('\\', '/', self::storagePath() . '/'))) {
            @unlink($sourcePath);
        }
    }

    private function deleteStoredAttachment(array $attachment): void {
        $destination = $this->destinationOf($attachment);
        $key = ltrim(str_replace('\\', '/', $attachment['stored_path'] ?? ''), '/');
        if ($key === '') {
            return;
        }

        if ($destination === 'local') {
            $path = $this->absolutePath($attachment);
            if (is_file($path)) {
                @unlink($path);
            }
            return;
        }

        $result = Hooks::applyFilters('attachment.storage.delete', [
            'handled' => false,
            'ok' => false,
            'error' => '',
        ], $destination, $key, $attachment);

        if (empty($result['handled']) || empty($result['ok'])) {
            error_log('Attachment storage delete failed: ' . ($result['error'] ?? 'unhandled destination ' . $destination));
        }
    }

    private function isExtensionAllowed(string $ext): bool {
        if ($ext === '') {
            return false;
        }
        // Checked before the allow-all shortcut so "%" can never admit an executable type.
        if (self::isDeniedExtension($ext)) {
            return false;
        }
        if ($this->allowsAllTypes()) {
            return true;
        }
        return in_array(strtolower($ext), $this->allowedExtensions(), true);
    }

    /**
     * Rejects files whose contents disagree with the extension they claim. Only
     * extensions present in EXTENSION_MIME_MAP are checked; anything else is left alone.
     */
    private function isMimeConsistent(string $ext, string $path): bool {
        $ext = strtolower($ext);
        if (!isset(self::EXTENSION_MIME_MAP[$ext])) {
            return true;
        }
        $mime = $this->detectMime($path);
        if ($mime === 'application/octet-stream') {
            return true;
        }
        return in_array($mime, self::EXTENSION_MIME_MAP[$ext], true);
    }

    private function extensionOf(string $filename): string {
        $filename = strtolower($this->sanitizeOriginalName($filename));
        if ($filename === '' || strpos($filename, '.') === false) {
            return '';
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return is_string($ext) ? strtolower($ext) : '';
    }

    private function sanitizeOriginalName(string $name): string {
        $name = str_replace(["\0", '/', '\\'], '', $name);
        $name = basename($name);
        $name = trim($name);
        return $name !== '' ? $name : 'attachment';
    }

    private function uniqueStoredName(string $original): string {
        $ext = $this->extensionOf($original);
        $name = bin2hex(random_bytes(16));
        return $ext !== '' ? $name . '.' . $ext : $name;
    }

    private function detectMime(string $path): string {
        if (is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        return 'application/octet-stream';
    }

    private function sessionStorageKey(): string {
        $key = preg_replace('/[^a-zA-Z0-9,-]/', '', (string) session_id());
        return $key !== '' ? $key : 'session';
    }

    private function ensureStorage(): void {
        $path = self::storagePath();
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new Exception(t('wo.attachment_upload_fail'));
        }

        $htaccess = $path . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n");
        }

        $index = $path . '/index.php';
        if (!is_file($index)) {
            file_put_contents($index, "<?php\nhttp_response_code(403);\nexit;\n");
        }
    }

    private function isEmptyDir(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        return empty($items);
    }

    private function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
