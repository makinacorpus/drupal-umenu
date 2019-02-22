<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Loads trees.
 *
 * @todo
 *   - This seriously need to be fixed performance-wise
 *   - wipe out cache
 *   - implement lru for cache (max 10 or more? items)
 *   - implement per site all trees preload
 *   - implement per site / role trees preload
 */
abstract class AbstractTreeProvider implements TreeProviderInterface
{
    private $db;
    private $cache;
    private $perNodeTree = [];
    private $loadedTrees = [];

    /**
     * Default constructor, do not ommit it!
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Allow tree cache
     *
     * @param CacheBackendInterface $cache
     */
    public function setCacheBackend(CacheBackendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Load tree items
     *
     * @param int $menuId
     *
     * @return TreeItem[]
     */
    abstract protected function loadTreeItems($menuId);

    /**
     * Load tree items
     *
     * @param int $nodeId
     *   Conditions that applies to the menu storage
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return string[]
     *   List of menu identifiers
     */
    abstract protected function findAllMenuFor($nodeId, array $conditions = []);

    /**
     * @return \DatabaseConnection
     */
    final protected function getDatabase()
    {
        return $this->db;
    }

    /**
     * @inheritdoc
     */
    public function mayCloneTree()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function cloneTreeIn($menuId, Tree $tree)
    {
        throw new \LogicException("This tree provider implementation cannot clone trees");
    }

    /**
     * {@inheritdoc}
     */
    public function findTreeForNode($nodeId, array $conditions = [])
    {
        // Not isset() here because result can null (no tree found)
        if (array_key_exists($nodeId, $this->perNodeTree)) {
            return $this->perNodeTree[$nodeId];
        }

        $menuIdList = $this->findAllMenuFor($nodeId, $conditions);

        if ($menuIdList) {
            // Arbitrary take the first
            // @todo later give more control to this for users
            return $this->perNodeTree[$nodeId] = reset($menuIdList);
        }

        $this->perNodeTree[$nodeId] = null;
    }

    /**
     * @inheritdoc
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false)
    {
        if ($withAccess && null === $userId) {
            throw new \InvalidArgumentException("loading menu with access checks needs the user identifier");
        }

        $doCache = false;
        $cacheId = null;

        if (!$withAccess) {
            if ($this->cache) {
                $cacheId = 'umenu:tree:' . $menuId;
                $cached = $this->cache->get($cacheId);

                if ($cached && $cached->data instanceof Tree) {
                    return $cached->data;
                }

                $doCache = true;
            }
        }

        $items = $this->loadTreeItems($menuId);

        if ($withAccess) {
            $nodeMap = [];

            foreach ($items as $item) {
                if ($nodeId = $item->getNodeId()) {
                    $nodeMap[] = $nodeId;
                }
            }

            if (!empty($nodeMap)) {
                $allowed = $this
                    ->getDatabase()
                    ->select('node', 'n')
                    ->fields('n', ['nid', 'nid'])
                    ->condition('n.nid', $nodeMap)
                    ->condition('n.status', 1)
                    ->addTag('node_access')
                    ->execute()
                    ->fetchAllKeyed()
                ;

                foreach ($items as $key => $item) {
                    if (($nodeId = $item->getNodeId()) && !isset($allowed[$nodeId])) {
                        unset($items[$key]);
                    }
                }
            }
        }

        $tree = new Tree($items, $menuId, $relocateOrphans);

        if ($doCache) {
            $this->cache->set($cacheId, $tree);
        }

        return $tree;
    }
}
