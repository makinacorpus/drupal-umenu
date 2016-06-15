<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Session\AccountInterface;

class TreeManager
{
    private $storage;
    private $itemStorage;
    private $provider;
    private $currentUser;
    private $cache = [];

    public function __construct(
        MenuStorageInterface $storage,
        ItemStorageInterface $itemStorage,
        AbstractTreeProvider $provider,
        AccountInterface $currentUser
    ) {
        $this->storage = $storage;
        $this->itemStorage = $itemStorage;
        $this->provider = $provider;
        $this->currentUser = $currentUser;
    }

    /**
     * @return AbstractTreeProvider
     */
    public function getTreeProvider()
    {
        return $this->provider;
    }

    /**
     * @return MenuStorageInterface
     */
    public function getMenuStorage()
    {
        return $this->storage;
    }

    /**
     * @return ItemStorageInterface
     */
    public function getItemStorage()
    {
        return $this->itemStorage;
    }

    /**
     * Internal recursion for clone tree
     *
     * @param int $menuId
     * @param TreeBase $item
     * @param TreeBase $parent
     */
    private function cloneTreeRecursion($menuId, TreeBase $item, $parentId = null)
    {
        if ($item->hasChildren()) {

            $previous = null;

            foreach ($item->getChildren() as $child) {

                if ($previous) {
                    $previous = $this->itemStorage->insertAfter($previous, $child->getNodeId(), $child->getTitle(), $child->getDescription());
                } else if ($parentId) {
                    $previous = $this->itemStorage->insertAsChild($parentId, $child->getNodeId(), $child->getTitle(), 0, $child->getDescription());
                } else {
                    $previous = $this->itemStorage->insert($menuId, $child->getNodeId(), $child->getTitle(), 0, $child->getDescription());
                }

                $this->cloneTreeRecursion($menuId, $child, $previous);
            }
        }
    }

    /**
     * Clone full tree in given menu
     *
     * This is the default implementation, but the TreeProvider might implement
     * it in a custom and more efficient way if possible.
     *
     * @param int $menuId
     * @param Tree $tree
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneTreeIn($menuId, Tree $tree)
    {
        if ($this->provider->mayCloneTree()) {
            return $this->provider->cloneTreeIn($menuId, $tree);
        }

        $this->cloneTreeRecursion($menuId, $tree);

        return $this->provider->buildTree($menuId, false);
    }

    /**
     * Alias of AbstractTreeProvider::buildTree()
     *
     * @param int|string $menuId
     *   Menu name or menu identifier
     * @param boolean $withAccess
     *   If set to true, menu will only container visible items for current user
     *
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false)
    {
        if (!is_numeric($menuId)) {
            $menuId = $this->storage->load($menuId)['id'];
        }

        if (isset($this->cache[$menuId][(int)$withAccess])) {
            return $this->cache[$menuId][(int)$withAccess];
        }

        return $this->cache[$menuId][(int)$withAccess] = $this
            ->getTreeProvider()
            ->buildTree($menuId, $withAccess, $this->currentUser->id())
        ;
    }
}
