<?php

namespace Drupal\Tests\umenu\Kernel;

use MakinaCorpus\Umenu\ItemStorage;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorage;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProvider;
use MakinaCorpus\Umenu\TreeProviderInterface;

/**
 * Tests cache with default storage implementation.
 *
 * @group umenu
 */
class DefaultItemCacheTest extends AbstractCacheTest
{
    protected function getItemStorage(): ItemStorageInterface
    {
        return new ItemStorage($this->getDatabaseConnection());
    }

    protected function getMenuStorage(): MenuStorageInterface
    {
        return new MenuStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider(): TreeProviderInterface
    {
        $treeProvider = new TreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}
