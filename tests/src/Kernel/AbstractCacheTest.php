<?php

namespace Drupal\Tests\umenu\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use MakinaCorpus\Umenu\CachedItemStorageProxy;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;

/**
 * Cache and cache invalidation unit testing.
 *
 * @group umenu_abstract
 */
abstract class AbstractCacheTest extends KernelTestBase
{
    public static $modules = ['system', 'user', 'node', 'umenu'];

    abstract protected function getItemStorage(): ItemStorageInterface;

    abstract protected function getMenuStorage(): MenuStorageInterface;

    abstract protected function getTreeProvider(): TreeProviderInterface;

    protected function createDrupalNode(string $title): NodeInterface
    {
        /** @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
        $entityTypeManager = $this->container->get('entity_type.manager');
        $storage = $entityTypeManager->getStorage('node');

        $node = $storage->create(['title' => $title, 'type' => 'page']);
        $storage->save($node);

        return $node;
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->container->get('database');
    }

    protected function getCacheBackend(): CacheBackendInterface
    {
        return $this->container->get('cache.default');
    }

    protected function getCacheAwareItemStorage(): ItemStorageInterface
    {
        return new CachedItemStorageProxy($this->getItemStorage(), $this->getCacheBackend());
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->installEntitySchema('user');
        $this->installEntitySchema('node');
        $this->installSchema('system', ['sequences']);
        $this->installSchema('umenu', ['umenu', 'umenu_item']);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Invalidation cache is working
     */
    public function testCache()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $site = null; //$this->createDrupalSite();
        $menu = $menuStorage->create(\uniqid('test_item_storage'));
        $menuId = $menu->getId();
        // This one is empty
        $tree = $provider->buildTree($menuId, false);

        // Do anything over the menu
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insert($menuId, $nodeA->id(), 'b');

        // Sorry for doing this, but the ucms_seo module make this test
        // fail since it will wipe out the cache without asking.
        /*
        if (!$this->moduleExists('ucms_seo')) {
            // Reload it, it should have remain cached
            $tree = $provider->buildTree($menuId, false);
            $this->assertFalse($tree->hasNodeItems($nodeA->id()));
            $this->assertTrue($tree->isEmpty());
        }
         */
    }

    /**
     * Invalidation testing
     */
    public function testInvalidation()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getCacheAwareItemStorage();

        $site = null; // $this->createDrupalSite();
        $menu = $menuStorage->create(\uniqid('test_item_storage'));
        $menuId = $menu->getId();

        // INSERT TOP LEVEL
        $nodeB = $this->createDrupalNode('test', $site);
        $itemB = $itemStorage->insert($menuId, $nodeB->id(), 'b');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeB->id()));

        // INSERT AFTER NO PARENT PUSH OTHERS
        $nodeC = $this->createDrupalNode('test', $site);
        $itemC = $itemStorage->insertAfter($itemB, $nodeC->id(), 'c');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeC->id()));

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insertBefore($itemB, $nodeA->id(), 'a');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA->id()));

        // INSERT CHILD
        $nodeA2 = $this->createDrupalNode('test', $site);
        $itemA2 = $itemStorage->insertAsChild($itemA, $nodeA2->id(), 'a2');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA2->id()));

        // INSERT CHILD BEFORE PUSH OTHERS
        $nodeA1 = $this->createDrupalNode('test', $site);
        $itemA1 = $itemStorage->insertBefore($itemA2, $nodeA1->id(), 'a1');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA1->id()));

        // INSERT CHILD AFTER PUSH OTHERS
        $nodeA3 = $this->createDrupalNode('test', $site);
        $itemA3 = $itemStorage->insertAfter($itemA2, $nodeA3->id(), 'a3');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA3->id()));

        // DELETE
        $itemStorage->delete($itemA1);
        $tree = $provider->buildTree($menuId, false);
        $this->assertFalse($tree->hasNodeItems($nodeA1->id()));

        // UPDATE
        $itemStorage->update($itemA, $nodeA->id(), 'new title');
        $tree = $provider->buildTree($menuId, false);
        $updatedItemA = $tree->getItemById($itemA);
        $this->assertSame('new title', $updatedItemA->getTitle());

        // Reparent 'b' under 'a', should be last
        $itemStorage->moveAsChild($itemB, $itemA);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'c' after 'a/2'
        $itemStorage->moveAfter($itemC, $itemA2);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a3' to root
        $itemStorage->moveToRoot($itemA3);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a2' before 'a'
        $itemStorage->moveBefore($itemA2, $itemA);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'c' under 'a3'
        $itemStorage->moveAsChild($itemC, $itemA3);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a' before 'c'
        $itemStorage->moveBefore($itemA, $itemC);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning
    }
}
