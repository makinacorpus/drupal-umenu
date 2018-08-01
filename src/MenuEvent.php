<?php

namespace MakinaCorpus\Umenu;

use Symfony\Component\EventDispatcher\GenericEvent;

class MenuEvent extends GenericEvent
{
    const EVENT_CREATE = 'umenu:create';
    const EVENT_DELETE = 'umenu:delete';
    const EVENT_TOGGLE_MAIN = 'umenu:toggle-main';
    const EVENT_TOGGLE_ROLE = 'umenu:toggle-role';
    const EVENT_UPDATE = 'umenu:update';

    /**
     * Get menu
     */
    public function getMenu(): Menu
    {
        return $this->subject;
    }
}
