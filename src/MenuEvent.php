<?php

namespace MakinaCorpus\Umenu;

use Symfony\Component\EventDispatcher\GenericEvent;

class MenuEvent extends GenericEvent
{
    const EVENT_CREATE = 'menu:create';
    const EVENT_DELETE = 'menu:delete';
    const EVENT_TOGGLE_MAIN = 'menu:toggle-main';
    const EVENT_TOGGLE_ROLE = 'menu:toggle-role';
    const EVENT_UPDATE = 'menu:update';

    /**
     * Get menu
     */
    public function getMenu(): Menu
    {
        return $this->subject;
    }
}
