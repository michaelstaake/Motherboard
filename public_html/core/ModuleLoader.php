<?php
class ModuleLoader {
    private static ?ModuleLoader $instance = null;
    private string $appVersion;
    private string $phpVersion;
    private array $discovered = [];
    private array $loaded = [];
    private array $skipped = [];
    private array $enabledSlugs = [];

    public function __construct(string $appVersion) {
        $this->appVersion = $appVersion;
        $this->phpVersion = PHP_VERSION;
        self::$instance = $this;
    }

    public static function instance(): ?ModuleLoader {
        return self::$instance;
    }

    public function loadAll(?Settings $settings = null): void {
        $this->discover();
        if ($settings) {
            $this->enabledSlugs = $this->resolveEnabled($settings);
        }

        foreach ($this->discovered as $definition) {
            if (!in_array($definition['slug'], $this->enabledSlugs, true)) {
                continue;
            }
            $this->bootModule($definition);
        }

        Hooks::doAction('modules.loaded', $this->loaded, $this->skipped);
    }

    public function getLoaded(): array {
        return $this->loaded;
    }

    public function getSkipped(): array {
        return $this->skipped;
    }

    public function getDiscovered(): array {
        return $this->discovered;
    }

    public function getModule(string $slug): ?array {
        return $this->discovered[$slug] ?? null;
    }

    public function isEnabled(string $slug): bool {
        return in_array($slug, $this->enabledSlugs, true);
    }

    public function getEnabledSlugs(): array {
        return $this->enabledSlugs;
    }

    public function setEnabled(Settings $settings, string $slug, bool $enabled): void {
        $module = $this->getModule($slug);
        if (!$module) {
            return;
        }

        $slugs = $this->enabledSlugs;
        if ($enabled) {
            if (!in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
            foreach ((array) ($module['conflicts'] ?? []) as $conflict) {
                $slugs = array_values(array_filter($slugs, fn($s) => $s !== $conflict));
            }
        } else {
            $slugs = array_values(array_filter($slugs, fn($s) => $s !== $slug));
        }

        $this->enabledSlugs = $slugs;
        $settings->setSetting('enabled_modules', json_encode(array_values($slugs)));
    }

    public function reloadLanguages(): void {
        foreach ($this->loaded as $definition) {
            $this->loadModuleLanguage($definition);
        }
    }

    public function loadModuleLanguage(array $definition): void {
        $path = $definition['path'];
        $locale = I18n::getInstance()->getLocale();
        foreach (array_unique(['en-us', $locale]) as $code) {
            $file = $path . '/lang/' . $code . '.php';
            if (!is_file($file)) {
                continue;
            }
            $strings = include $file;
            if (is_array($strings)) {
                I18n::getInstance()->merge($strings);
            }
        }
    }

    private function discover(): void {
        $this->discovered = [];
        $this->skipped = [];
        $this->loaded = [];

        if (!is_dir(MODULES_PATH)) {
            return;
        }

        $dirs = glob(MODULES_PATH . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($dirs as $dir) {
            $this->parseModule($dir);
        }
    }

    private function resolveEnabled(Settings $settings): array {
        $raw = $settings->getSetting('enabled_modules', null);
        if ($raw !== null && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
        }

        $enabled = [];
        foreach ($this->discovered as $slug => $definition) {
            if (!empty($definition['default_enabled'])) {
                $enabled[] = $slug;
            }
        }

        $captcha = $settings->getSetting('captcha_provider', 'off');
        if ($captcha === 'turnstile' && isset($this->discovered['cloudflare-turnstile'])) {
            $enabled[] = 'cloudflare-turnstile';
            $enabled = array_values(array_filter($enabled, fn($s) => $s !== 'google-recaptcha'));
        } elseif ($captcha === 'recaptcha' && isset($this->discovered['google-recaptcha'])) {
            $enabled[] = 'google-recaptcha';
            $enabled = array_values(array_filter($enabled, fn($s) => $s !== 'cloudflare-turnstile'));
        }

        $enabled = array_values(array_unique($enabled));
        $settings->setSetting('enabled_modules', json_encode($enabled));
        return $enabled;
    }

    private function parseModule(string $dir): void {
        $slug = basename($dir);
        $index = $dir . '/index.php';
        if (!is_file($index)) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.missing_index')];
            return;
        }

        $definition = include $index;
        if (!is_array($definition)) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.invalid_index')];
            return;
        }

        $minApp = $definition['min_motherboard_version'] ?? null;
        $maxApp = $definition['max_motherboard_version'] ?? null;
        $minPhp = $definition['min_php_version'] ?? null;
        $maxPhp = $definition['max_php_version'] ?? null;

        if ($minApp === null || $minApp === '' || $minPhp === null || $minPhp === '') {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.versions_required')];
            return;
        }

        if (version_compare($this->appVersion, (string) $minApp, '<')) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.app_min', ['version' => $minApp])];
            return;
        }
        if ($maxApp !== null && $maxApp !== '' && version_compare($this->appVersion, (string) $maxApp, '>')) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.app_max', ['version' => $maxApp])];
            return;
        }
        if (version_compare($this->phpVersion, (string) $minPhp, '<')) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.php_min', ['version' => $minPhp])];
            return;
        }
        if ($maxPhp !== null && $maxPhp !== '' && version_compare($this->phpVersion, (string) $maxPhp, '>')) {
            $this->skipped[] = ['slug' => $slug, 'reason' => t('modules.skip.php_max', ['version' => $maxPhp])];
            return;
        }

        $definition['slug'] = $definition['slug'] ?? $slug;
        $definition['path'] = $dir;
        $this->discovered[$definition['slug']] = $definition;
    }

    private function bootModule(array $definition): void {
        $this->loadModuleLanguage($definition);

        if (isset($definition['boot']) && is_callable($definition['boot'])) {
            $definition['boot']($definition);
        }

        $this->loaded[] = $definition;
        Hooks::doAction('module.loaded', $definition);
    }
}
