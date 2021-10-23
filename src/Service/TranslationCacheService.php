<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class TranslationCacheService
{
    private string $cacheItemPrefix ='phiil_googlesheets_translations';

    private FilesystemAdapter $cache;

    public function __construct()
    {
        $this->cache = $this->_getCacheInstance();
    }

    public function loadFromCache(string $itemName)
    {
        $cacheItem = $this->getCacheItem($itemName);

        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    public function saveCacheItemByName(string $name, $value)
    {
        $item = $this->getCacheItem($name);
        $this->saveCacheItem($item, $value);

        return $item;
    }

    public function saveCacheItem(ItemInterface $item, $value)
    {
        $item->set($value);
        $this->cache->save($item);
    }

    public function getCacheItem(string $name) :ItemInterface
    {
        return $this->cache->getItem($this->_getCacheItemName($name));
    }

    private function _getCacheItemName(string $name) :string
    {
        return $this->cacheItemPrefix . '.' . $name;
    }

    private function _getCacheInstance() :FilesystemAdapter
    {
        return new FilesystemAdapter();
    }
}
