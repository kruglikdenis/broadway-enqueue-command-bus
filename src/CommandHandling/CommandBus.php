<?php

declare(strict_types=1);

namespace BroadwayEnqueue\CommandHandling;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Exception\CommandNotAnObjectException;
use Interop\Queue\Context;

final class CommandBus implements AsyncCommandBus
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var CommandHandler[]
     */
    private $commandHandlers = [];

    /**
     * @var Command[]
     */
    private $queue;

    /**
     * @var bool
     */
    private $isDispatching;

    public function __construct(Context $context, string $queueName)
    {
        $this->context = $context;
        $this->queueName = $queueName;

        $this->commandHandlers = [];
        $this->queue = [];
        $this->isDispatching = false;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(CommandHandler $handler): void
    {
        $this->commandHandlers[] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function asyncDispatch($command): void
    {
        $this->pushIntoQueue($command);

        if (!$this->isDispatching) {
            $this->produceCommandsFromQueue();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($command): void
    {
        $this->pushIntoQueue($command);

        if (!$this->isDispatching) {
            $this->dispatchCommandsFromQueue();
        }
    }

    /**
     * Dispatch all commands from queue
     */
    private function dispatchCommandsFromQueue(): void
    {
        $this->isDispatching = true;
        try {
            while ($command = array_shift($this->queue)) {
                $this->handleCommand($command);
            }
        } finally {
            $this->isDispatching = false;
        }
    }

    /**
     * Produce commands from queue
     */
    private function produceCommandsFromQueue(): void
    {
        $this->isDispatching = true;
        try {
            while ($command = array_shift($this->queue)) {
                $this->produceCommand($command);
            }
        } catch (\Interop\Queue\Exception $exception) {

        } finally {
            $this->isDispatching = false;
        }
    }

    /**
     * @param Command $command
     *
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\Exception\InvalidDestinationException
     * @throws \Interop\Queue\Exception\InvalidMessageException
     */
    private function produceCommand(Command $command): void
    {
        $this->context->createProducer()->send(
            $this->context->createQueue($this->queueName),
            $this->context->createMessage(serialize($command))
        );
    }

    /**
     * @param Command $command
     */
    private function handleCommand(Command $command): void
    {
        foreach ($this->commandHandlers as $handler) {
            $handler->handle($command);
        }
    }

    /**
     * Push command into queue
     *
     * @param mixed $command
     */
    private function pushIntoQueue($command): void
    {
        if (!$command instanceof Command) {
            throw new CommandNotAnObjectException();
        }

        $this->queue[] = $command;
    }
}
