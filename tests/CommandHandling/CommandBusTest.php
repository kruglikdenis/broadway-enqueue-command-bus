<?php

declare(strict_types=1);

namespace BroadwayEnqueue\Tests\CommandHandling;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Exception\CommandNotAnObjectException;
use BroadwayEnqueue\CommandHandling\Command;
use BroadwayEnqueue\CommandHandling\CommandBus;
use Enqueue\Null\NullContext;
use Interop\Queue\Context;
use PHPUnit\Framework\TestCase;

final class CommandBusTest extends TestCase
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $context = new NullContext();

        $this->commandBus = new CommandBus($context, 'test');
    }

    public function testCanNotDispatchString(): void
    {
        $this->expectException(CommandNotAnObjectException::class);

        $this->commandBus->dispatch('command');
    }

    public function testCanNotDispatchArray(): void
    {
        $this->expectException(CommandNotAnObjectException::class);
        $this->commandBus->dispatch([ 'command' ]);
    }

    public function testCanNotDispatchObject(): void
    {
        $this->expectException(CommandNotAnObjectException::class);
        $this->commandBus->dispatch(new \stdClass());
    }

    public function testDispatchCommandToSubscribedHandlers(): void
    {
        $command = $this->createMock(Command::class);

        $firstCommandHandler = $this->createMock(CommandHandler::class);
        $firstCommandHandler->expects($this->once())
            ->method('handle')
            ->with($command);

        $secondCommandHandler = $this->createMock(CommandHandler::class);
        $secondCommandHandler->expects($this->once())
            ->method('handle')
            ->with($command);

        $this->commandBus->subscribe($firstCommandHandler);
        $this->commandBus->subscribe($secondCommandHandler);
        $this->commandBus->dispatch($command);
    }

    public function testCanAsyncDispatchCommand(): void
    {
        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('createProducer');
        $context->expects($this->once())
            ->method('createMessage');
        $context->expects($this->once())
            ->method('createQueue');

        $commandBus = new CommandBus($context, 'test');

        $command = $this->createMock(Command::class);
        $commandHandler = $this->createMock(CommandHandler::class);
        $commandHandler->expects($this->never())
            ->method('handle')
            ->with($command);

        $commandBus->subscribe($commandHandler);
        $commandBus->asyncDispatch($command);
    }
}
