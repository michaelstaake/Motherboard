<?php
class I18n {
    private static ?I18n $instance = null;
    private array $strings = [];
    private string $locale = 'en-us';
    private string $fallbackLocale = 'en-us';

    public static function getInstance(): I18n {
        if (self::$instance === null) {
            self::$instance = new I18n();
        }
        return self::$instance;
    }

    public function load(string $locale, bool $required = false): void {
        $locale = strtolower($locale);
        $path = LANG_PATH . '/' . $locale . '.php';

        if (!file_exists($path)) {
            if ($required) {
                throw new Exception('Required language file is missing: ' . $locale . '.php');
            }
            return;
        }

        $loaded = include $path;
        if (!is_array($loaded)) {
            if ($required) {
                throw new Exception('Language file must return an array: ' . $locale . '.php');
            }
            return;
        }

        if ($locale === $this->fallbackLocale) {
            $this->strings = $loaded;
        } else {
            $this->strings = array_replace_recursive($this->strings, $loaded);
        }
        $this->locale = $locale;
    }

    public function loadLocale(string $locale): void {
        $locale = strtolower($locale);
        $this->load($this->fallbackLocale, true);
        if ($locale !== $this->fallbackLocale) {
            $this->load($locale);
        }
    }

    public function merge(array $strings): void {
        $this->strings = array_replace($this->strings, $strings);
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function translate(string $key, array $replace = []): string {
        $value = $this->lookup($this->strings, $key);
        if ($value === null) {
            $value = $key;
        }
        foreach ($replace as $search => $replacement) {
            $value = str_replace('{' . $search . '}', (string) $replacement, $value);
        }
        return $value;
    }

    public function availableLocales(): array {
        $locales = [];
        foreach (glob(LANG_PATH . '/*.php') ?: [] as $file) {
            $code = strtolower(basename($file, '.php'));
            $data = include $file;
            $locales[$code] = is_array($data) ? ($data['meta']['name'] ?? $code) : $code;
        }
        ksort($locales);
        return $locales;
    }

    private function lookup(array $strings, string $key) {
        if (isset($strings[$key]) && is_string($strings[$key])) {
            return $strings[$key];
        }
        $parts = explode('.', $key);
        $current = $strings;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return is_string($current) ? $current : null;
    }
}

function t(string $key, array $replace = []): string {
    return I18n::getInstance()->translate($key, $replace);
}

function tlabel(string $prefix, string $value): string {
    $key = $prefix . '.' . $value;
    $translated = t($key);
    return $translated === $key ? $value : $translated;
}

function ldate($datetime, string $format): string {
    $timestamp = is_int($datetime) ? $datetime : strtotime((string) $datetime);
    if ($timestamp === false) {
        return '';
    }
    $month = (int) date('n', $timestamp);
    $workingFormat = str_replace(['M', 'F'], ["\x01", "\x02"], $format);
    $result = date($workingFormat, $timestamp);
    $result = str_replace("\x01", t('date.month_short.' . $month), $result);
    $result = str_replace("\x02", t('date.month_long.' . $month), $result);
    return $result;
}

function applyPrintLanguage(?Settings $settings = null): void {
    $settings = $settings ?? new Settings();
    $available = I18n::getInstance()->availableLocales();
    $interface = strtolower((string) $settings->getSetting('language', 'en-us'));
    if (!isset($available[$interface])) {
        $interface = 'en-us';
    }
    $print = strtolower((string) $settings->getSetting('print_language', $interface));
    if (!isset($available[$print])) {
        $print = $interface;
    }
    I18n::getInstance()->loadLocale($print);
    $loader = ModuleLoader::instance();
    if ($loader) {
        $loader->reloadLanguages();
    }
}
