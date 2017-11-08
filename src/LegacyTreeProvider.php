<?php

namespace MakinaCorpus\Umenu;

/**
 * Uses Drupal core menu_links table to load menu items.
 */
class LegacyTreeProvider extends AbstractTreeProvider
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
                    SELECT
                        l.mlid
                            AS id,
                        :id1
                            AS menu_id,
                        m.site_id
                            AS site_id,
                        SUBSTRING(l.link_path FROM 6)
                            AS node_id,
                        l.plid
                            AS parent_id,
                        l.weight
                            AS weight,
                        l.link_title
                            AS title,
                        l.depth
                            AS depth,
                        NULL
                            AS description
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE
                        m.id = :id2
                        AND l.link_path LIKE 'node/%'
                    ORDER BY
                        l.weight ASC,
                        l.plid ASC,
                        l.mlid ASC
                ",
                [
                    ':id1' => $menuId,
                    ':id2' => $menuId,
                ]
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
            ->select('menu_links', 'l')
            ->condition('l.link_path', 'node/' . $nodeId)
        ;

        $query->join('umenu', 'm', "m.name = l.menu_name");
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
