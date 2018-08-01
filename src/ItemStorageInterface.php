<?php

namespace MakinaCorpus\Umenu;

/**
 * Item storage;
 */
interface ItemStorageInterface
{
    /**
     * Get menu identifier for item
     */
    public function getMenuIdFor(int $itemId): int;

    /**
     * Append new item within menu
     */
    public function insert(int $menuId, int $nodeId, string $title, $description = null): int;

    /**
     * Append new item as child of the selected item
     */
    public function insertAsChild(int $otherItemId, int $nodeId, string $title, $description = null): int;

    /**
     * Insert item after another
     */
    public function insertAfter(int $otherItemId, int $nodeId, string $title, $description = null): int;

    /**
     * Insert item before another
     */
    public function insertBefore(int $otherItemId, int $nodeId, string $title, $description = null): int;

    /**
     * Update item
     */
    public function update(int $itemId, $nodeId = null, $title = null, $description = null);

    /**
     * Reparent item
     */
    public function moveAsChild(int $itemId, int $otherItemId);

    /**
     * Orphan item
     */
    public function moveToRoot(int $itemId);

    /**
     * Insert item after another
     */
    public function moveAfter(int $itemId, int $otherItemId);

    /**
     * Insert item before another
     */
    public function moveBefore(int $itemId, int $otherItemId);

    /**
     * Delete item
     */
    public function delete(int $itemId);
}
