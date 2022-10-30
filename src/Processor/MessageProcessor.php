<?php

namespace TwentytwoLabs\BehatAmqpExtension\Processor;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class MessageProcessor implements ProcessorInterface
{
    private Message $message;

    public function process(Message $message, array $options): bool
    {
        $this->message = $message;

        return true;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getMessageBody(): ?string
    {
        return $this->message->getBody();
    }

    public function getDecodedMessageBody(): array
    {
        return \json_decode($this->message->getBody(), true);
    }
}
