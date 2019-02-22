<?php

namespace MakinaCorpus\Umenu;

/**
 * This class is actually just a pure hack that allows the tree and tree items
 * classes to use the properties from one another, for better encapsulation.
 */
class TreeBase
{
    /**
     * @var TreeItem[]
     */
    protected $children = [];

    /**
     * Get children
     *
     * @return TreeItem[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get ordered node identifiers that matches the children, in the same order
     *
     * @return int[]
     */
    public function getChildrenNodeIdList()
    {
        return \array_filter(
            \array_map(function ($child) {
                return $child->getNodeId();
            }, $this->children)
        );
    }

    /**
     * Has this object children
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * Count the number of children
     *
     * @return int
     */
    public function getChildCount()
    {
        return count($this->children);
    }
}
