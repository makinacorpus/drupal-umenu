<?php

namespace MakinaCorpus\Umenu;

/**
 * Loads trees.
 */
interface TreeProviderInterface
{
    /**
     * Can this implementation clone trees
     */
    public function mayCloneTree(): bool;

    /**
     * Clone full tree in given menu
     *
     * @param int $menuId
     *   Menu identifier
     * @param Tree $tree
     *   Tree to clone
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneTreeIn($menuId, Tree $tree): Tree;

    /**
     * Find most revelant tree for node
     *
     * Equivalent of Drupal's menu_link_get_preferred().
     *
     * This method must always prefer menus with the 'is_main' property set
     * before others, to get a consitent behavior.
     *
     * @param int $nodeId
     *   Conditions that applies to the menu storage
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return int
     *   Menu identifier, that you then may load using the buildTree() method
     */
    public function findTreeForNode($nodeId, array $conditions = []);

    /**
     * Build tree
     *
     * @param int $menuId
     *   Menu identifier
     * @param boolean $withAccess
     *   Should this check access when loading tree
     * @param int $userId
     *   User account identifier for access checks
     * @param boolean $relocateOrphans
     *   When a parent is not visible nor accessible, should this tree
     *   relocate children to the menu root
     *
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false, $resetCache = false): Tree;
}
