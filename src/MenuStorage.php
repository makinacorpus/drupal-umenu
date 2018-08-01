<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuStorage implements MenuStorageInterface
{
    private $database;
    private $dispatcher;

    /**
     * Default constructor
     */
    public function __construct(Connection $database, EventDispatcherInterface $dispatcher = null)
    {
        $this->database = $database;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function load($id): Menu
    {
        if (is_numeric($id)) {
            $list = $this->loadWithConditions(['id' => $id]);
        } else {
            $list = $this->loadMultiple([$id]);
        }

        if (!$list) {
            throw new \InvalidArgumentException(sprintf("%s: menu does not exist", $id));
        }

        return reset($list);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($id): bool
    {
        $list = $this->loadMultiple([$id]);

        return (bool)$list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultiple($nameList): array
    {
        return $this->loadWithConditions(['name' => $nameList]);
        // @todo Sort ?
    }

    /**
     * {@inheritdoc}
     */
    public function loadWithConditions(array $conditions, bool $mainFirst = true): array
    {
        $q = $this->database->select('umenu', 'um')->fields('um');

        if ($mainFirst) {
            $q->orderBy('um.is_main', 'desc');
            $q->orderBy('um.id', 'asc');
        }

        foreach ($conditions as $key => $value) {
            if (\is_array($value)) {
                $q->condition('um.' . $key, $value, 'IN');
            } else {
                $q->condition('um.' . $key, $value);
            }
        }
        $r = $q->execute();
        $r->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, Menu::class);

        $ret = [];

        while ($menu = $r->fetch()) {
            $ret[$menu->getName()] = $menu;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        try {
            $existing = $this->load($id);

            $this->database->delete('umenu')->condition('id', $existing->getId())->execute();

            if ($this->dispatcher && $existing) {
                $this->dispatcher->dispatch(MenuEvent::EVENT_DELETE, new MenuEvent($existing));
            }
        } catch (\InvalidArgumentException $e) {
            // Menu does not exists, return silently
            // @todo specialize the exception
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $values)
    {
        unset($values['name'], $values['id'], $values['status']);

        $existing = $this->load($id);

        if (empty($values)) { // Nothing to update
            return;
        }

        $this
            ->database
            ->update('umenu')
            ->fields($values)
            ->condition('id', $existing->getId())
            ->execute()
        ;

        if ($this->dispatcher) {
            // Since we updated it, we need to relaod it.
            $this->dispatcher->dispatch(MenuEvent::EVENT_UPDATE, new MenuEvent($this->load($id)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toggleRole($id, string $role)
    {
        $existing = $this->load($id);

        if (!$existing) {
            throw new \InvalidArgumentException(sprintf("%s: cannot change main status, menu does not exist", $name));
        }

        if (!$role) {
            $role = null; // ensure NULL in database and not empty string
        }
        if ($role !== $existing->getRole()) {
            // Nothing to do
            return;
        }

        if ($role) {
            $this
                ->database
                ->query(
                    "UPDATE {umenu} SET role = :role WHERE id = :id",
                    [':role' => $role, ':id' => $existing->getId()]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu} SET role = NULL WHERE id = :id",
                    [':id' => $existing->getId()]
                )
            ;
        }

        if ($this->dispatcher && $existing) {
            $this->dispatcher->dispatch(MenuEvent::EVENT_TOGGLE_ROLE, new MenuEvent($existing, ['role' => $role]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toggleMainStatus($id, $toggle = true)
    {
        $existing = $this->load($id);

        if (!$existing) {
            throw new \InvalidArgumentException(sprintf("%s: cannot change main status, menu does not exist", $id));
        }
        if (!$existing->getSiteId()) {
            throw new \LogicException(sprintf("%s: cannot change main status, menu does not belong to a site", $id));
        }

        $status = (bool)$toggle;
        if ($status === $existing->isSiteMain()) {
            // Nothing to do
            return;
        }

        if ($status) {
            // Drop main menu status for all others within the same site
            if ($existing->getSiteId()) {
                $this->database->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE site_id = :site AND id <> :id",
                    [':site' => $existing->getSiteId(), ':id' => $existing->getId()]
                );
            } else {
                $this->database->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE site_id IS NULL OR site_id = 0 AND id <> :id",
                    [':site' => $existing->getSiteId(), ':id' => $existing->getId()]
                );
            }

            // And change menu, yeah.
            $this->database->query("UPDATE {umenu} SET is_main = 1 WHERE id = :id",[':id' => $existing->getId()]);

        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE id = :id",
                    [':name' => $existing->getId()]
                )
            ;
        }

        if ($this->dispatcher && $existing) {
            $this->dispatcher->dispatch(MenuEvent::EVENT_TOGGLE_MAIN, new MenuEvent($existing, ['is_main' => $toggle]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($name, array $values = []): Menu
    {
        $exists = (bool)$this->database->query("SELECT 1 FROM {umenu} WHERE name = ?", [$name])->fetchField();

        if ($exists) {
            throw new \InvalidArgumentException(sprintf("%s: cannot create menu, already exists", $name));
        }

        $values['name'] = $name;
        if (empty($values['title'])) {
            $values['title'] = $name;
        }

        unset($values['id']);

        $this
            ->database
            ->insert('umenu')
            ->fields($values)
            ->execute()
        ;

        $menu = $this->load($name);

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(MenuEvent::EVENT_CREATE, new MenuEvent($menu));
        }

        return $menu;
    }

    /**
     * {@inheritdoc}
     */
    public function resetCaches()
    {
    }
}
