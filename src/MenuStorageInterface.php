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
     * @param int|string $name
     *   If int is provided, it's a menu identifier, else it's a menu name
     *
     * @return Menu
     *   Table row and data, if schema has been altered, there will be all data
     *   from added columns in there
     *
     * @throws \InvalidArgumentException
     *   If the menu does not exist
     */
    public function load($name): Menu;

    /**
     * Check that a menu definition exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name): bool;

    /**
     * Load multiple menu definitions
     *
     * @param string[] $nameList
     *
     * @return Menu[]
     *   Same as load() but an array of it
     */
    public function loadMultiple(array $nameList): array;

    /**
     * Find one or more instances by condition
     *
     * @param mixed[] $conditions
     *   Keys are field names, values are single values or array of values
     *   (case in which an IN query will be done)
     * @param bool $mainFirst
     *   If false, there will be no order on the query
     *
     * @return Menu[]
     *   Same as load() but an array of it
     */
    public function loadWithConditions(array $conditions, bool $mainFirst = true): array;

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
     * From these values, the main menu status will be ignored.
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
     * Set menu role
     *
     * @param int|string $name
     *   Menu identifier or name
     * @param string $role
     *   Arbitrary new role (could be anything this API does not care) may be null
     */
    public function toggleRole($name, string $role);

    /**
     * Set main menu status of the given menu
     *
     * All other menus within the same site will be updated to be unset as
     * main menus, if the $toggle boolean is true.
     *
     * @param string $name
     * @param boolean $toggle
     *
     * @throws \InvalidArgumentException
     *   If the menu does not exists
     * @throws \LogicException
     *   If the menu is not attached to a site
     */
    public function toggleMainStatus($name, bool $toggle = true);

    /**
     * Insert a single menu definition
     *
     * @param string $name
     *
     * @param array $values
     *   Any values that will end up in the database, 'name' will be dropped.
     *
     * @return Menu
     *   The created instance
     *
     * @throws \InvalidArgumentException
     *   If the menu already does exist
     */
    public function create($name, array $values = []): Menu;

    /**
     * Drop all caches
     */
    public function resetCaches();
}
