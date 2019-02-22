<?php

namespace MakinaCorpus\Umenu\Tests;

use Drupal\user\User;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeBase;
use MakinaCorpus\Umenu\TreeManager;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\TreeItem;

abstract class AbstractItemStorageTest extends AbstractDrupalTest
{
    use SiteTestTrait; // @todo

    private $menus;

    protected function tearDown()
    {
        if ($this->menus) {
            foreach ($this->menus as $name) {
                $this->getDatabaseConnection()->query("DELETE FROM {umenu} WHERE name = ?", [$name]);
            }
        }

        $this->getDatabaseConnection()->query("DELETE FROM {menu_links} WHERE menu_name NOT IN (SELECT name FROM {umenu})");

        $this->eraseAllData();

        parent::tearDown();
    }

    /**
     * @return ItemStorageInterface
     */
    abstract protected function getItemStorage();

    /**
     * @return MenuStorageInterface
     */
    abstract protected function getMenuStorage();

    /**
     * @return TreeProviderInterface
     */
    abstract protected function getTreeProvider();

    private function getIdPart(TreeItem $item): string
    {
        return (string)($item->getNodeId() ?? \md5($item->getUrl()));
    }

    protected function recursiveBuildArray(TreeBase $item)
    {
        $ret = [];

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $ret[$child->getTitle() . '.' . $this->getIdPart($child) . '.' . $child->getId()] = $this->recursiveBuildArray($child);
            }
        }

        return $ret;
    }

    protected function recursiveBuildArrayWithoutId(TreeBase $item)
    {
        $ret = [];

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $ret[$child->getTitle() . '.' . $this->getIdPart($child) . '.' . $child->getSiteId()] = $this->recursiveBuildArrayWithoutId($child);
            }
        }

        return $ret;
    }

    public function testAll()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $site = $this->createDrupalSite();
        $menu = $menuStorage->create($this->menus[] = uniqid('test_item_storage'));
        $menuId = $menu->getId();

        /*
         * Build:
         *   z
         *   a
         *   a/0
         *   a/1
         *   a/2
         *   a/3
         *   a/4
         *   b
         *   c
         *   d
         */

        // INSERT TOP LEVEL
        $nodeB = $this->createDrupalNode('test', $site);
        $itemB = $itemStorage->insert($menuId, $nodeB->id(), 'b');

        // INSERT AFTER NO PARENT
        $nodeD = $this->createDrupalNode('test', $site);
        $itemD = $itemStorage->insertAfter($itemB, $nodeD->id(), 'd');

        // INSERT BEFORE NO PARENT
        $nodeZ = $this->createDrupalNode('test', $site);
        $itemZ = $itemStorage->insertBefore($itemB, $nodeZ->id(), 'z');

        // INSERT AFTER NO PARENT PUSH OTHERS
        $nodeC = $this->createDrupalNode('test', $site);
        $itemC = $itemStorage->insertAfter($itemB, $nodeC->id(), 'c');

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insertBefore($itemB, $nodeA->id(), 'a');

        // INSERT CHILD
        $nodeA2 = $this->createDrupalNode('test', $site);
        $itemA2 = $itemStorage->insertAsChild($itemA, $nodeA2->id(), 'a2');

        // INSERT CHILD BEFORE
        $nodeA0 = $this->createDrupalNode('test', $site);
        $itemA0 = $itemStorage->insertBefore($itemA2, $nodeA0->id(), 'a0');

        // INSERT CHILD BEFORE PUSH OTHERS
        $nodeA1 = $this->createDrupalNode('test', $site);
        $itemA1 = $itemStorage->insertBefore($itemA2, $nodeA1->id(), 'a1');

        // INSERT CHILD AFTER
        $nodeA4 = $this->createDrupalNode('test', $site);
        $itemA4 = $itemStorage->insertAfter($itemA2, $nodeA4->id(), 'a4');

        // INSERT CHILD AFTER PUSH OTHERS
        $nodeA3 = $this->createDrupalNode('test', $site);
        $itemA3 = $itemStorage->insertAfter($itemA2, $nodeA3->id(), 'a3');

        // And now, test everything in the right order
        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'z.' . $nodeZ->id() . '.' . $itemZ => [],
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a0.' . $nodeA0->id() . '.' . $itemA0 => [],
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
                'a4.' . $nodeA4->id() . '.' . $itemA4 => [],
            ],
            'b.' . $nodeB->id() . '.' . $itemB => [],
            'c.' . $nodeC->id() . '.' . $itemC => [],
            'd.' . $nodeD->id() . '.' . $itemD => [],
        ];

        $this->assertSame($expected, $actual);

        /*
         * Go for clone test, now that we do have something
         */

        $newSite = $this->createDrupalSite();
        $otherMenu = $menuStorage->create($this->menus[] = uniqid('test_item_storage'), ['site_id' => $newSite->getId()]);
        $tree = $provider->buildTree($menu->getId());
        $newSiteId = $newSite->getId();
        $manager = new TreeManager($menuStorage, $itemStorage, $provider, new User());
        $newTree = $manager->cloneTreeIn($otherMenu->getId(), $tree);

        $actual = $this->recursiveBuildArrayWithoutId($newTree);
        $expected = [
            'z.' . $nodeZ->id() . '.' . $newSiteId => [],
            'a.' . $nodeA->id() . '.' . $newSiteId => [
                'a0.' . $nodeA0->id() . '.' . $newSiteId => [],
                'a1.' . $nodeA1->id() . '.' . $newSiteId => [],
                'a2.' . $nodeA2->id() . '.' . $newSiteId => [],
                'a3.' . $nodeA3->id() . '.' . $newSiteId => [],
                'a4.' . $nodeA4->id() . '.' . $newSiteId => [],
            ],
            'b.' . $nodeB->id() . '.' . $newSiteId => [],
            'c.' . $nodeC->id() . '.' . $newSiteId => [],
            'd.' . $nodeD->id() . '.' . $newSiteId => [],
        ];

        $this->assertSame($expected, $actual);

        /*
         * And another clone test
         */

        $otherSite = $this->createDrupalSite();
        $otherSiteId = $otherSite->getId();
        $otherTree = $manager->cloneMenu($menuId, $otherSite->getId(), $this->menus[] = uniqid('test_item_storage'));

        $actual = $this->recursiveBuildArrayWithoutId($otherTree);
        $expected = [
            'z.' . $nodeZ->id() . '.' . $otherSiteId => [],
            'a.' . $nodeA->id() . '.' . $otherSiteId => [
                'a0.' . $nodeA0->id() . '.' . $otherSiteId => [],
                'a1.' . $nodeA1->id() . '.' . $otherSiteId => [],
                'a2.' . $nodeA2->id() . '.' . $otherSiteId => [],
                'a3.' . $nodeA3->id() . '.' . $otherSiteId => [],
                'a4.' . $nodeA4->id() . '.' . $otherSiteId => [],
            ],
            'b.' . $nodeB->id() . '.' . $otherSiteId => [],
            'c.' . $nodeC->id() . '.' . $otherSiteId => [],
            'd.' . $nodeD->id() . '.' . $otherSiteId => [],
        ];

        $this->assertSame($expected, $actual);
    }

    public function testMove()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $site = $this->createDrupalSite();
        $menu = $menuStorage->create($this->menus[] = uniqid('test_item_storage'));
        $menuId = $menu->getId();

        /*
         * Build:
         *   a
         *   a/1
         *   a/2
         *   a/3
         *   b
         *   c
         */

        // INSERT TOP LEVEL
        $nodeB = $this->createDrupalNode('test', $site);
        $itemB = $itemStorage->insert($menuId, $nodeB->id(), 'b');

        // INSERT AFTER NO PARENT PUSH OTHERS
        $nodeC = $this->createDrupalNode('test', $site);
        $itemC = $itemStorage->insertAfter($itemB, $nodeC->id(), 'c');

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insertBefore($itemB, $nodeA->id(), 'a');

        // INSERT CHILD
        $nodeA2 = $this->createDrupalNode('test', $site);
        $itemA2 = $itemStorage->insertAsChild($itemA, $nodeA2->id(), 'a2');

        // INSERT CHILD BEFORE PUSH OTHERS
        $nodeA1 = $this->createDrupalNode('test', $site);
        $itemA1 = $itemStorage->insertBefore($itemA2, $nodeA1->id(), 'a1');

        // INSERT CHILD AFTER PUSH OTHERS
        $nodeA3 = $this->createDrupalNode('test', $site);
        $itemA3 = $itemStorage->insertAfter($itemA2, $nodeA3->id(), 'a3');

        // And now, test everything in the right order
        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
            ],
            'b.' . $nodeB->id() . '.' . $itemB => [],
            'c.' . $nodeC->id() . '.' . $itemC => [],
        ];
        $this->assertSame($expected, $actual);

        /*
         * Go for some moves:
         *   a
         *   a/1
         *   a/2
         *   a/3
         *   b
         *   c
         */

        // Reparent 'b' under 'a', should be last
        $itemStorage->moveAsChild($itemB, $itemA);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
                'b.'  . $nodeB->id()  . '.' . $itemB => [],
            ],
            'c.' . $nodeC->id() . '.' . $itemC => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'c' after 'a/2'
        $itemStorage->moveAfter($itemC, $itemA2);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'c.'  . $nodeC->id()  . '.' . $itemC => [],
                'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
                'b.'  . $nodeB->id()  . '.' . $itemB => [],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a3' to root
        $itemStorage->moveToRoot($itemA3);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'c.'  . $nodeC->id()  . '.' . $itemC => [],
                'b.'  . $nodeB->id()  . '.' . $itemB => [],
            ],
            'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a2' before 'a'
        $itemStorage->moveBefore($itemA2, $itemA);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
            'a.'  . $nodeA->id()  . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'c.'  . $nodeC->id()  . '.' . $itemC => [],
                'b.'  . $nodeB->id()  . '.' . $itemB => [],
            ],
            'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'c' under 'a3'
        $itemStorage->moveAsChild($itemC, $itemA3);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
            'a.'  . $nodeA->id()  . '.' . $itemA => [
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'b.'  . $nodeB->id()  . '.' . $itemB => [],
            ],
            'a3.' . $nodeA3->id() . '.' . $itemA3 => [
                'c.'  . $nodeC->id()  . '.' . $itemC => [],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a' before 'c'
        $itemStorage->moveBefore($itemA, $itemC);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
            'a3.' . $nodeA3->id() . '.' . $itemA3 => [
                'a.'  . $nodeA->id()  . '.' . $itemA => [
                    'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                    'b.'  . $nodeB->id()  . '.' . $itemB => [],
                ],
                'c.'  . $nodeC->id()  . '.' . $itemC => [],
            ],
        ];
        $this->assertSame($expected, $actual);
    }
}
