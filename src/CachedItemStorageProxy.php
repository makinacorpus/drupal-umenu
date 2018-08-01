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
     */
    private function getCacheId(int $menuId): string
    {
        return 'umenu:tree:' . $menuId;
    }

    /**
     * Get menu cache identifier from item identifier
     */
    private function getCacheIdFrom(int $itemId): string
    {
        return 'umenu:tree:' . $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuIdFor(int $itemId): int
    {
        return $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(int $menuId, int $nodeId, string $title, $description = null): int
    {
        $ret = $this->nested->insert($menuId, $nodeId, $title, $description);
        $this->cache->delete($this->getCacheId($menuId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild(int $otherItemId, int $nodeId, string $title, $description = null): int
    {
        $ret = $this->nested->insertAsChild($otherItemId, $nodeId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter(int $otherItemId, int $nodeId, string $title, $description = null): int
    {
        $ret = $this->nested->insertAfter($otherItemId, $nodeId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore(int $otherItemId, int $nodeId, string $title, $description = null): int
    {
        $ret = $this->nested->insertBefore($otherItemId, $nodeId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $itemId, $nodeId = null, $title = null, $description = null)
    {
        $ret = $this->nested->update($itemId, $nodeId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveAsChild($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot(int $itemId)
    {
        $ret = $this->nested->moveToRoot($itemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveAfter($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveBefore($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $itemId)
    {
        // For deletion, fetch cache identifier first
        $cacheId = $this->getCacheIdFrom($itemId);
        $ret = $this->nested->delete($itemId);
        $this->cache->delete($cacheId);

        return $ret;
    }
}
