<?php

namespace TwentytwoLabs\BehatAmqpExtension\Factory;

use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Consumer;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\Stack\Builder;
use Swarrot\Processor\Stack\StackedProcessor;

class SwarrotFactory
{
    protected \AMQPConnection $connection;
    protected ?\AMQPExchange $exchange = null;
    protected array $queues;
    protected array $messageProviders;

    public function __construct(string $host = 'localhost', int $port = 5672, string $vhost = '/', string $login = 'guest', string $password = 'guest')
    {
        $this->connection = new \AMQPConnection([
            'host' => $host,
            'port' => $port,
            'vhost' => $vhost,
            'login' => $login,
            'password' => $password,
        ]);
    }

    public function getChannel()
    {
        $this->connection->connect();

        return new \AMQPChannel($this->connection);
    }

    public function getExchange(): \AMQPExchange
    {
        if (null === $this->exchange) {
            $this->exchange = new \AMQPExchange($this->getChannel());
        }

        return $this->exchange;
    }

    public function getQueue(string $queueName): \AMQPQueue
    {
        if (empty($this->queues[$queueName])) {
            $queue = new \AMQPQueue($this->getChannel());
            $queue->setName($queueName);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $this->queues[$queueName] = $queue;
        }

        return $this->queues[$queueName];
    }

    public function getMessageProvider(string $queueName): MessageProviderInterface
    {
        if (empty($this->messageProviders[$queueName])) {
            $this->messageProviders[$queueName] = new PeclPackageMessageProvider($this->getQueue($queueName));
        }

        return $this->messageProviders[$queueName];
    }

    public function createStackedProcessor(MessageProviderInterface $messageProvider, ProcessorInterface $processor): StackedProcessor
    {
        $stack = (new Builder())
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor')
            ->push('Swarrot\Processor\MaxMessages\MaxMessagesProcessor')
            ->push('Swarrot\Processor\Ack\AckProcessor', $messageProvider)
        ;

        return $stack->resolve($processor);
    }

    public function createConsumer(MessageProviderInterface $messageProvider, ProcessorInterface $processor): Consumer
    {
        return new Consumer($messageProvider, $processor);
    }
}
