<?php

namespace MakinaCorpus\Umenu;

use Symfony\Component\EventDispatcher\GenericEvent;

class MenuEnvEvent extends GenericEvent
{
    const EVENT_FINDTREE = 'umenu:findtree';

    private $conditions = [];
    private $nodeId;

    public function __construct(int $nodeId, array $conditions = [])
    {
        $this->nodeId = $nodeId;
        $this->conditions = $conditions;
    }

    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function removeCondition(string $name)
    {
        unset($this->conditions[$name]);
    }

    public function addCondition(string $name, $value)
    {
        // @todo override or append ?
        $this->conditions[$name] = $value;
    }
}
