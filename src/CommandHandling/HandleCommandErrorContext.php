<?php

declare(strict_types=1);

namespace BroadwayEnqueue\CommandHandling;

final class HandleCommandErrorContext
{
    /**
     * Create context for logger
     *
     * @param \Throwable $throwable
     * @param Command|null $command
     *
     * @return array
     */
    public static function fromExceptionAndCommand(\Throwable $throwable, ?Command $command): array
    {
        return [
            'command' => (null !== $command) ? serialize($command) : null,
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'trace' => $throwable->getTraceAsString()
        ];
    }
}
