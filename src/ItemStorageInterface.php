<?php

namespace MakinaCorpus\Umenu;

/**
 * Item storage;
 */
interface ItemStorageInterface
{
    /**
     * Get menu identifier for item
     *
     * @param int $itemId
     *
     * @return int
     */
    public function getMenuIdFor($itemId);

    /**
     * Append new item within menu
     *
     * @param int $menuId
     * @param ?int $nodeId
     * @param string $title
     * @param ?string $description
     * @param ?string $url
     *
     * @return int
     */
    public function insert($menuId, $nodeId, $title, $description = null, $url = null);

    /**
     * Append new item as child of the selected item
     *
     * @param int $otherItemId
     * @param ?int $nodeId
     * @param string $title
     * @param ?string $description
     * @param ?string $url
     *
     * @return int
     */
    public function insertAsChild($otherItemId, $nodeId, $title, $description = null, $url = null);

    /**
     * Insert item after another
     *
     * @param int $otherItemId
     * @param ?int $nodeId
     * @param string $title
     * @param ?string $description
     * @param ?string $url
     *
     * @return int
     */
    public function insertAfter($otherItemId, $nodeId, $title, $description = null, $url = null);

    /**
     * Insert item before another
     *
     * @param int $otherItemId
     * @param ?int $nodeId
     * @param string $title
     * @param ?string $description
     * @param ?string $url
     *
     * @return int
     */
    public function insertBefore($otherItemId, $nodeId, $title, $description = null, $url = null);

    /**
     * Update item
     *
     * @param int $itemId
     * @param ?int $nodeId
     * @param string $title
     * @param ?string $description
     * @param ?string $url
     */
    public function update($itemId, $nodeId = null, $title = null, $description = null, $url = null);

    /**
     * Reparent item
     *
     * @param int $itemId
     * @param int $otherItemId
     */
    public function moveAsChild($itemId, $otherItemId);

    /**
     * Orphan item
     *
     * @param int $itemId
     */
    public function moveToRoot($itemId);

    /**
     * Insert item after another
     *
     * @param int $otherItemId
     * @param int $nodeId
     *
     * @return int
     */
    public function moveAfter($itemId, $otherItemId);

    /**
     * Insert item before another
     *
     * @param int $otherItemId
     * @param int $nodeId
     *
     * @return int
     */
    public function moveBefore($itemId, $otherItemId);

    /**
     * Delete item
     *
     * @param int $itemId
     */
    public function delete($itemId);
}
