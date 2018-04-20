<?php

namespace MakinaCorpus\Umenu;

/**
 * Default tree provider
 */
class TreeProvider extends AbstractTreeProvider
{
    /**
     * {@inheritdoc}
     */
    protected function loadTreeItems($menuId)
    {
        // We need a nice SQL query, that will fetch everything at once.
        $r = $this
            ->getDatabase()
            ->query(
                "
                    SELECT *
                    FROM {umenu_item}
                    WHERE menu_id = :id
                    ORDER BY
                        weight ASC,
                        parent_id IS NULL ASC,
                        parent_id ASC,
                        id ASC
                ",
                [':id' => $menuId]
            )
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, TreeItem::class);

        return $r->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function findAllMenuFor($nodeId, array $conditions = [])
    {

        $query = $this
            ->getDatabase()
            ->select('umenu_item', 'i')
            ->condition('i.node_id', $nodeId)
        ;

        $query->join('umenu', 'm', "m.id = i.menu_id");
        $query->fields('m', ['id']);
        $query->groupBy('m.id');

        // @todo this is not the right place to do this, but for performances
        //  at the current state, it's the best place to put it: this forces
        //  the findTreeForNode() awaited behavior and always order the menus
        //  using the 'is_main' property DESC to ensure that main menus are
        //  preferred to the others
        $query->orderBy('m.is_main', 'DESC');
        $query->orderBy('m.id', 'ASC');

        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $query->condition('m.' . $key, $value);
            }
        }

        return $query->execute()->fetchCol();
    }
}
