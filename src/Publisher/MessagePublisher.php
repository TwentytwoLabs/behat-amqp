<?php

namespace TwentytwoLabs\BehatAmqp\Publisher;

use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class MessagePublisher
{
    private array $messageProperties = [];
    private ?string $messageBody;
    private MessagePublisherInterface $publisher;

    public function __construct(\AMQPExchange $exchange)
    {
        $this->publisher = new PeclPackageMessagePublisher($exchange);
    }

    public function setMessageProperties(array $messageProperties)
    {
        $this->messageProperties = $messageProperties;
    }

    public function setMessageBody(?string $messageBody = null)
    {
        $this->messageBody = $messageBody;
    }

    public function publish(string $routingKey)
    {
        $this->publisher->publish(new Message($this->messageBody, $this->messageProperties), $routingKey);
        $this->messageProperties = [];
        $this->messageBody = null;
    }
}
