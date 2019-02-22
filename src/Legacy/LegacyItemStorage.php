<?php

namespace MakinaCorpus\Umenu\Legacy;

use MakinaCorpus\Umenu\ItemStorageInterface;

/**
 * Et là, on fait rentrer des ronds dans des carrés.
 */
class LegacyItemStorage implements ItemStorageInterface
{
    private $db;

    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    protected function validateMenu($menuId, $title, $nodeId, $url)
    {
        if (empty($menuId)) {
            throw new \InvalidArgumentException("Menu identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($nodeId) && empty($url)) {
            throw new \InvalidArgumentException("Node identifier and URL cannot be both empty cannot be empty");
        }

        $menu = $this->db->query("SELECT * FROM {umenu} WHERE id = ?", [$menuId])->fetchAssoc();

        if (!$menu) {
            throw new \InvalidArgumentException(sprintf("Menu %d does not exist", $menuId));
        }

        return $menu;
    }

    protected function validateItem($otherItemId, $title, $nodeId, $url)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Relative item identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($nodeId) && empty($url)) {
            throw new \InvalidArgumentException("Node identifier and URL cannot be both empty cannot be empty");
        }

        // Find parent identifier
        $data = $this
            ->db
            ->query("
                    SELECT m.id, m.name, l.plid, l.weight
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
                [$otherItemId]
            )
            ->fetchAssoc()
        ;

        if (!$data) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $otherItemId));
        }

        return array_values($data);
    }

    protected function validateMove($itemId, $otherItemId)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Relative item identifier cannot be empty");
        }
        if (empty($itemId)) {
            throw new \InvalidArgumentException("Item identifier cannot be empty");
        }

        $exists = (bool)$this->db->query("SELECT 1 FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        // Find parent identifier
        $data = $this
            ->db
            ->query("
                    SELECT m.id, m.name, l.plid, l.weight
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
                [$otherItemId]
            )
            ->fetchAssoc()
        ;

        if (!$data) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $otherItemId));
        }

        return array_values($data);
    }

    /**
     * Get menu identifier for item
     *
     * @param int $itemId
     *
     * @return int
     */
    public function getMenuIdFor($itemId)
    {
        // Find parent identifier
        $menuId = (int)$this
            ->db
            ->query("
                    SELECT m.id
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
                [$itemId]
            )
            ->fetchField()
        ;

        if (!$menuId) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        return $menuId;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($menuId, $nodeId, $title, $description = null, $url = null)
    {
        $menu = $this->validateMenu($menuId, $title, $nodeId, $url);

        $weight = (int)$this->db->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE menu_name = ? AND plid = 0", [$menu['name']])->fetchField();

        $link = [
            'menu_name'  => $menu['name'],
            'link_path'  => 'node/' . $nodeId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        list(, $menuName) = $this->validateItem($otherItemId, $title, $nodeId, $url);

        $weight = (int)$this->db->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = ?", [$otherItemId])->fetchField();

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight,
            'plid'       => $otherItemId
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $nodeId, $url);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} SET weight = weight + 2 WHERE plid = :plid AND mlid <> :mlid AND weight >= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight + 1,
            'plid'       => $parentId,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore($otherItemId, $nodeId, $title, $description = null, $url = null)
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $nodeId, $url);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} SET weight = weight - 2 WHERE plid = :plid AND mlid <> :mlid AND weight <= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight - 1,
            'plid'       => $parentId,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemId, $nodeId = null, $title = null, $description = null, $url = null)
    {
        $exists = (bool)$this->db->query("SELECT 1 FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $item = menu_link_load($itemId);

        if (null !== $nodeId) {
            $item['link_path'] = 'node/' . $nodeId;
        }
        if (null !== $title) {
            $item['link_title'] = $title;
        }
        if (null !== $description) {
            $item['description'] = $description;
        }

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild($itemId, $otherItemId)
    {
        $this->validateMove($itemId, $otherItemId);

        $weight = (int)$this->db->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = ?", [$otherItemId])->fetchField();

        $item = menu_link_load($itemId);
        $item['weight'] = $weight;
        $item['plid'] = $otherItemId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot($itemId)
    {
        $menuName = (string)$this->db->query("SELECT menu_name FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$menuName) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $weight = (int)$this->db->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = 0 AND menu_name = ?", [$menuName])->fetchField();

        $item = menu_link_load($itemId);
        $item['weight'] = $weight;
        $item['plid'] = 0;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter($itemId, $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} SET weight = weight + 2 WHERE plid = :plid AND mlid <> :mlid AND weight >= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $item = menu_link_load($itemId);
        $item['weight'] = $weight + 1;
        $item['plid'] = $parentId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore($itemId, $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} SET weight = weight - 2 WHERE plid = :plid AND mlid <> :mlid AND weight <= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $item = menu_link_load($itemId);
        $item['weight'] = $weight - 1;
        $item['plid'] = $parentId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($itemId)
    {
        menu_link_delete($itemId);
    }
}
