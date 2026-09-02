<?php
class Hooks {
    private static array $actions = [];
    private static array $filters = [];

    public static function addAction(string $hook, callable $callback, int $priority = 10): void {
        self::$actions[$hook][$priority][] = $callback;
        ksort(self::$actions[$hook]);
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void {
        self::$filters[$hook][$priority][] = $callback;
        ksort(self::$filters[$hook]);
    }

    public static function doAction(string $hook, ...$args): void {
        if (empty(self::$actions[$hook])) {
            return;
        }
        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public static function applyFilters(string $hook, $value, ...$args) {
        if (empty(self::$filters[$hook])) {
            return $value;
        }
        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }
        return $value;
    }

    public static function hasAction(string $hook): bool {
        return !empty(self::$actions[$hook]);
    }

    public static function hasFilter(string $hook): bool {
        return !empty(self::$filters[$hook]);
    }
}
