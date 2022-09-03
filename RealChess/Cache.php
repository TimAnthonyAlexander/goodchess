<?php
namespace RealChess;


use JsonException;

class Cache{
    public function __construct() {
        if (!isset($GLOBALS['cacheDataStorage']) || !is_array($GLOBALS['cacheDataStorage'])) {
            $GLOBALS['cacheDataStorage'] = [];
        }
    }

    /**
     * @throws JsonException
     */
    public function load(): void {
        if (!file_exists(__DIR__.'/cache.json')) {
            $this->save();
        }
        $GLOBALS['cacheDataStorage'] = json_decode(file_get_contents(__DIR__."/cache.json"), true, 512, JSON_THROW_ON_ERROR);
    }

    public function delete(): void {
        $GLOBALS['cacheDataStorage'] = [];
        $this->save();
    }

    /**
     * @throws JsonException
     */
    public function save(): void {
        file_put_contents(__DIR__."/cache.json", json_encode($GLOBALS['cacheDataStorage'], JSON_THROW_ON_ERROR));
    }

    public function add($key, $value): void {
        if (!isset($GLOBALS['cacheDataStorage'][$key])) {
            $GLOBALS['cacheDataStorage'][$key] = $value;
        }
    }

    public function set($key, $value): void {
        $GLOBALS['cacheDataStorage'][$key] = $value;
    }

    public function remove($key): void {
        unset($GLOBALS['cacheDataStorage'][$key]);
    }

    public function isset($key): bool {
        return isset($GLOBALS['cacheDataStorage'][$key]);
    }

    public function get($key) {
        return $GLOBALS['cacheDataStorage'][$key];
    }
}
