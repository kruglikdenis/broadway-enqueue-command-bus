<?php

declare(strict_types=1);

namespace BroadwayEnqueue\Logger;

use BroadwayEnqueue\CommandHandling\Command;

final class HandleCommandError implements Context
{
    /**
     * @var Command|null
     */
    private $command;

    /**
     * @var \Throwable
     */
    private $throwable;

    private function __construct(\Throwable $throwable, ?Command $command)
    {
        $this->command = $command;
        $this->throwable = $throwable;
    }

    public static function create(\Throwable $throwable, ?Command $command): self
    {
        return new self($throwable, $command);
    }

    public function toArray(): array
    {
        return [
            'command' => (null !== $this->command) ? serialize($this->command) : null,
            'message' => $this->throwable->getMessage(),
            'code' => $this->throwable->getCode(),
            'trace' => $this->throwable->getTraceAsString()
        ];
    }
}
