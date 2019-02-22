<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Item storage;
 */
class CachedItemStorageProxy implements ItemStorageInterface
{
    private $nested;
    private $cache;

    /**
     * Default constructor
     *
     * @param ItemStorageInterface $nested
     * @param CacheBackendInterface $cache
     */
    public function __construct(ItemStorageInterface $nested, CacheBackendInterface $cache)
    {
        $this->nested = $nested;
        $this->cache = $cache;
    }

    /**
     * Get menu cache identifier
     *
     * @param int $menuId
     *
     * @return string
     */
    private function getCacheId($menuId)
    {
        return 'umenu:tree:' . $menuId;
    }

    /**
     * Get menu cache identifier from item identifier
     *
     * @param int $itemId
     *
     * @return string
     */
    private function getCacheIdFrom($itemId)
    {
        return 'umenu:tree:' . $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuIdFor($itemId)
    {
        return $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($menuId, $nodeId, $title, $description = null, $url = null)
    {
        $ret = $this->nested->insert($menuId, $nodeId, $title, $description, $url);
        $this->cache->delete($this->getCacheId($menuId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        $ret = $this->nested->insertAsChild($otherItemId, $nodeId, $title, $description, $url);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        $ret = $this->nested->insertAfter($otherItemId, $nodeId, $title, $description, $url);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        $ret = $this->nested->insertBefore($otherItemId, $nodeId, $title, $description, $url);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemId, $nodeId = null, $title = null, $description = null, $url = null)
    {
        $ret = $this->nested->update($itemId, $nodeId, $title, $description, $url);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild($itemId, $otherItemId)
    {
        $ret = $this->nested->moveAsChild($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot($itemId)
    {
        $ret = $this->nested->moveToRoot($itemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter($itemId, $otherItemId)
    {
        $ret = $this->nested->moveAfter($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore($itemId, $otherItemId)
    {
        $ret = $this->nested->moveBefore($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($itemId)
    {
        // For deletion, fetch cache identifier first
        $cacheId = $this->getCacheIdFrom($itemId);
        $ret = $this->nested->delete($itemId);
        $this->cache->delete($cacheId);

        return $ret;
    }
}
