<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class MenuParamConverter implements ParamConverterInterface
{
    private $menuStorage;

    /**
     * Default constructor
     */
    public function __construct(MenuStorageInterface $menuStorage)
    {
        $this->menuStorage = $menuStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, $definition, $name, array $defaults)
    {
        try {
            return $this->menuStorage->load($value);
        } catch (\InvalidArgumentException $e) {}

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function applies($definition, $name, Route $route)
    {
        return ($definition['type'] ?? null) === 'umenu_menu';
    }
}
