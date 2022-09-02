<?php
namespace RealChess;


class Cache{
    public function __construct() {
        if (!isset($GLOBALS['cache'])) {
            $GLOBALS['cache'] = [];
        }
    }

    /**
     * @throws \JsonException
     */
    public function load(): void {
        if (!file_exists('cache.json')) {
            $this->save();
        }
        $GLOBALS['cache'] = json_decode(file_get_contents("cache.json"), true, 512, JSON_THROW_ON_ERROR);
    }

    public function save(): void {
        file_put_contents("cache.json", json_encode($GLOBALS['cache'], JSON_THROW_ON_ERROR));
    }

    public function add($key, $value): void {
        if (!isset($GLOBALS['cache'][$key])) {
            $GLOBALS['cache'][$key] = $value;
        }
    }

    public function set($key, $value): void {
        $GLOBALS['cache'][$key] = $value;
    }

    public function remove($key): void {
        unset($GLOBALS['cache'][$key]);
    }

    public function isset($key): bool {
        return isset($GLOBALS['cache'][$key]);
    }

    public function get($key) {
        return $GLOBALS['cache'][$key];
    }
}
