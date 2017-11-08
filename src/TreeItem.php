<?php

namespace MakinaCorpus\Umenu;

/**
 * Represents a single tree item.
 */
final class TreeItem extends TreeBase
{
    private $id;
    private $menu_id;
    private $site_id;
    private $node_id;
    private $parent_id;
    private $weight;
    private $title;
    private $description;
    private $depth;

    public function getId()
    {
        return $this->id;
    }

    public function getMenuId()
    {
        return $this->menu_id;
    }

    public function getSiteId()
    {
        return $this->site_id;
    }

    public function getNodeId()
    {
        return $this->node_id;
    }

    public function getParentId()
    {
        return $this->parent_id;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getRoute()
    {
        return 'node/' . $this->node_id;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function isInTrailOf($nodeId)
    {
        if ($nodeId === $this->node_id) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->isInTrailOf($nodeId)) {
                return true;
            }
        }
    }
}
