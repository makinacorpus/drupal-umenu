<?php

namespace MakinaCorpus\Umenu;

/**
 * Menu storage;
 *
 * This exists as an interface for later use of a proxy pattern to be able to
 * query on Redis for normal runtime.
 */
interface MenuStorageInterface
{
    /**
     * Load a single menu definition
     *
     * @param string $name
     *
     * @return string[]
     *   Table row and data, if schema has been altered, there will be all data
     *   from added columns in there
     *
     * @throws \InvalidArgumentException
     *   If the menu does not exist
     */
    public function load($name);

    /**
     * Load multiple menu definitions
     *
     * @param string[] $nameList
     *
     * @return string[][]
     *   Same as load() but an array of it
     */
    public function loadMultiple($nameList);

    /**
     * Find one or more instances by condition
     *
     * @param mixed[] $conditions
     *   Keys are field names, values are single values or array of values
     *   (case in which an IN query will be done)
     *
     * @return string[][]
     *   Same as load() but an array of it 
     */
    public function loadWithConditions($conditions);

    /**
     * Delete a single menu definition
     *
     * This will remain silent if menu does not exist
     *
     * @param string $name
     */
    public function delete($name);

    /**
     * Update a single menu definition
     *
     * @param string $name
     *
     * @param array $values
     *   Any values that will end up in the database, 'name' will be dropped.
     *
     * @throws \InvalidArgumentException
     *   If the menu does not exist
     */
    public function update($name, array $values);

    /**
     * Insert a single menu definition
     *
     * @param string $name
     *
     * @param array $values
     *   Any values that will end up in the database, 'name' will be dropped.
     *
     * @return string[]
     *   The created instance
     *
     * @throws \InvalidArgumentException
     *   If the menu already does exist
     */
    public function create($name, array $values = []);

    /**
     * Drop all caches
     */
    public function resetCaches();
}
