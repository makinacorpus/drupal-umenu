<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;

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
    private $database;
    private $cache;
    private $perNodeTree = [];
    private $loadedTrees = [];

    /**
     * Default constructor, do not ommit it!
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * Allow tree cache
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
    abstract protected function loadTreeItems(int $menuId): array;

    /**
     * Load tree items
     *
     * @param int $nodeId
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return string[]
     *   List of menu identifiers
     */
    abstract protected function findAllMenuFor(int $nodeId, array $conditions = []): array;

    /**
     * Get database connection
     */
    final protected function getDatabase(): Connection
    {
        return $this->database;
    }

    /**
     * @inheritdoc
     */
    public function mayCloneTree(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function cloneTreeIn($menuId, Tree $tree): Tree
    {
        throw new \LogicException("This tree provider implementation cannot clone trees");
    }

    /**
     * {@inheritdoc}
     */
    public function findTreeForNode($nodeId, array $conditions = [])
    {
        // Not isset() here because result can null (no tree found)
        if (\array_key_exists($nodeId, $this->perNodeTree)) {
            return $this->perNodeTree[$nodeId];
        }

        if ($menuIdList = $this->findAllMenuFor($nodeId, $conditions)) {
            // Arbitrary take the first
            // @todo later give more control to this for users
            return $this->perNodeTree[$nodeId] = \reset($menuIdList);
        }

        $this->perNodeTree[$nodeId] = null;
    }

    /**
     * @inheritdoc
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false, $resetCache = false): Tree
    {
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
                $nodeMap[] = $item->getNodeId();
            }

            if (!empty($nodeMap)) {
                $allowed = $this
                    ->getDatabase()
                    ->select('node', 'n')
                    ->fields('n', ['nid', 'nid'])
                    ->condition('n.nid', $nodeMap, 'IN')
                    ->condition('n.status', 1)
                    ->addTag('node_access')
                    ->execute()
                    ->fetchAllKeyed()
                ;

                foreach ($items as $key => $item) {
                    if (!isset($allowed[$item->getNodeId()])) {
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
