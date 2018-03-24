<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Umenu\Legacy\LegacyItemStorage;
use MakinaCorpus\Umenu\Legacy\LegacyTreeProvider;
use MakinaCorpus\Umenu\MenuStorage;

class LegacyItemCacheTest extends AbstractCacheTest
{
    protected function getItemStorage()
    {
        return new LegacyItemStorage($this->getDatabaseConnection());
    }

    protected function getMenuStorage()
    {
        return new MenuStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider()
    {
        $treeProvider = new LegacyTreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}