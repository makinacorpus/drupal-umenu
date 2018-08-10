<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TreeBreadcrumbBuilder implements BreadcrumbBuilderInterface
{
    private $eventDispatcher;
    private $requestStack;
    private $treeManager;

    /**
     * Default constructor
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, RequestStack $requestStack, TreeManager $treeManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->treeManager = $treeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match)
    {
        $node = $route_match->getParameter('node');

        if (!$node instanceof NodeInterface) {
            return false;
        }
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return false;
        }

        $nodeId = $node->id();

        // Allow other modules to add arbitrary conditions to the query.
        $event = new MenuEnvEvent($nodeId);
        $this->eventDispatcher->dispatch(MenuEnvEvent::EVENT_FINDTREE, $event);

        // And go for it.
        $menuId = $this->treeManager->getTreeProvider()->findTreeForNode($nodeId, $event->getConditions());

        if ($menuId) {
            $request->attributes->set('_umenu_node_id', $node->id());
            $request->attributes->set('_umenu_menu_id', $menuId);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function build(RouteMatchInterface $route_match)
    {
        $ret = new Breadcrumb();
        $tags = [];
        $links = [Link::createFromRoute(new TranslatableMarkup('Home'), '<front>')];

        $request = $this->requestStack->getCurrentRequest();
        $nodeId = $request->attributes->get('_umenu_node_id');
        $menuId = $request->attributes->get('_umenu_menu_id');

        // In theory, this should not happen, but better be safe than sorry.
        if (!$nodeId || !$menuId) {
            $ret->addCacheContexts(['url.path']);

            \trigger_error(\sprintf("Cannot build breadcrumb, request does not carry necessary information"), E_USER_ERROR);
            return $ret;
        }

        if ($items = $this->treeManager->buildTree($menuId, true)->getMostRevelantTrailForNode($nodeId)) {
            foreach ($items as $item) {
                $itemNodeId = $item->getNodeId();
                if ($itemNodeId != $nodeId) {
                    $tags[] = 'node:'.$itemNodeId;
                    $links[] = Link::createFromRoute($item->getTitle(), 'entity.node.canonical', ['node' => $itemNodeId]);
                }
            }
        }

        $ret->setLinks($links);
        $ret->addCacheTags($tags);
        $ret->addCacheContexts(['url.path']);

        return $ret;
    }
}
