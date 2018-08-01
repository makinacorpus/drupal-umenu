<?php

namespace Drupal\Tests\umenu\Kernel;

use MakinaCorpus\Umenu\ItemStorage;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorage;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProvider;
use MakinaCorpus\Umenu\TreeProviderInterface;

/**
 * Test default item storage implementation.
 *
 * @group umenu
 */
class DefaultItemStorageTest extends AbstractItemStorageTest
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
        return new TreeProvider($this->getDatabaseConnection());
    }
}
