<?php

declare(strict_types=1);

namespace BroadwayEnqueue\CommandBus\CommandHandling;

use Broadway\CommandHandling\CommandBus;

interface AsyncCommandBus extends CommandBus
{
    /**
     * Async dispatch command
     *
     * @param mixed $command
     */
    public function asyncDispatch($command): void;
}
