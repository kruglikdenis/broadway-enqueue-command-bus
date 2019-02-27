<?php

declare(strict_types=1);

namespace BroadwayEnqueue\Logger;

interface Context
{
    /**
     * Convert context to array
     *
     * @return array
     */
    public function toArray(): array;
}
