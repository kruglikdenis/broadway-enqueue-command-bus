<?php

declare(strict_types=1);

namespace BroadwayEnqueue\Tests\CommandHandling;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Exception\CommandNotAnObjectException;
use BroadwayEnqueue\CommandHandling\Command;
use BroadwayEnqueue\CommandHandling\CommandBus;
use Enqueue\Null\NullContext;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidMessageException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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

    public function testShouldLogErrorWhenDispatch(): void
    {
        $command = $this->createMock(Command::class);

        $commandHandler = $this->createMock(CommandHandler::class);
        $commandHandler
            ->expects($this->once())
            ->method('handle')
            ->with($command)
            ->will($this->throwException(new \Exception('I failed.')));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $this->commandBus->setLogger($logger);

        $this->commandBus->subscribe($commandHandler);
        $this->commandBus->dispatch($command);
    }

    public function testShouldLogErrorWhenProduce(): void
    {
        $context = $this->createMock(Context::class);
        $context->method('createMessage')
            ->will($this->throwException(new InvalidMessageException()));

        $commandBus = new CommandBus($context, 'test');

        $command = $this->createMock(Command::class);

        $commandHandler = $this->createMock(CommandHandler::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $commandBus->setLogger($logger);

        $commandBus->subscribe($commandHandler);
        $commandBus->asyncDispatch($command);
    }

    public function testStillHandleCommandsAfterException(): void
    {
        $firstCommmand = $this->createMock(Command::class);
        $secondCommand = $this->createMock(Command::class);

        $commandHandler = $this->createMock(CommandHandler::class);
        $simpleHandler = $this->createMock(CommandHandler::class);

        $commandHandler
            ->expects($this->at(0))
            ->method('handle')
            ->with($firstCommmand)
            ->will($this->throwException(new \Exception('I failed.')));

        $commandHandler
            ->expects($this->at(1))
            ->method('handle')
            ->with($secondCommand);

        $simpleHandler
            ->expects($this->once())
            ->method('handle')
            ->with($secondCommand);

        $this->commandBus->subscribe($commandHandler);
        $this->commandBus->subscribe($simpleHandler);

        $this->commandBus->dispatch($firstCommmand);
        $this->commandBus->dispatch($secondCommand);
        $this->commandBus->asyncDispatch($secondCommand);
    }
}
