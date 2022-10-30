<?php

declare(strict_types=1);

namespace TwentytwoLabs\BehatAmqpExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Swarrot\Processor\ProcessorInterface;
use Symfony\Component\Yaml\Yaml;
use TwentytwoLabs\ArrayComparator\AsserterTrait as ArrayComparatorAssert;
use TwentytwoLabs\BehatAmqpExtension\AsserterTrait;
use TwentytwoLabs\BehatAmqpExtension\Factory\SwarrotFactory;
use TwentytwoLabs\BehatAmqpExtension\Processor\MessageProcessor;
use TwentytwoLabs\BehatAmqpExtension\Publisher\MessagePublisher;

class AmqpContext implements Context
{
    use ArrayComparatorAssert;
    use AsserterTrait;

    protected SwarrotFactory $factory;
    protected ProcessorInterface $processor;
    protected MessagePublisher $publisher;

    public function __construct()
    {
        $this->processor = new MessageProcessor();
    }

    /**
     * @Then I purge queue :queueName
     */
    public function iPurgeQueue(string $queueName): void
    {
        $purged = $this->factory->getQueue($queueName)->purge();

        if (false === $purged) {
            throw new \Exception("Could not purge queue $queueName");
        }
    }

    /**
     * @Then I set message properties:
     */
    public function iSetMessageProperties(PyStringNode $properties): void
    {
        $this->publisher->setMessageProperties(Yaml::parse($properties->getRaw(), Yaml::PARSE_CUSTOM_TAGS) ?? []);
    }

    /**
     * @Then I set message body:
     */
    public function iSetMessageBody(PyStringNode $body): void
    {
        $this->publisher->setMessageBody($body->getRaw());
    }

    /**
     * @Then I publish message with routing key :routingKey
     */
    public function iPublishMessageWithRoutingKey($routingKey): void
    {
        $this->publisher->publish($routingKey);
    }

    /**
     * @Given I wait :sleep second(s)
     */
    public function iWait(int $sleep): void
    {
        sleep($sleep);
    }

    /**
     * @Given I have :count message(s) in amqp :transport queue
     */
    public function iHaveCountMessagesInAmqpQueue(int $countExpected, string $transport): void
    {
        $count = $this->factory->getQueue($transport)->declareQueue();
        if ($count !== $countExpected) {
            throw new \Exception(sprintf('There is %d message(s) in the queue at this moment.', $count));
        }
    }

    /**
     * @Given I have messages in amqp :transport queue
     */
    public function iHaveMessagesInAmqpQueue(string $transport): void
    {
        $this->factory->getQueue($transport)->setFlags(AMQP_DURABLE);
        $count = $this->factory->getQueue($transport)->declareQueue();
        if (empty($count)) {
            throw new \Exception(sprintf('There is %d message(s) in the queue at this moment.', $count));
        }
    }

    /**
     * @Then I consume a message form queue :queueName
     */
    public function iConsumeAMessageFromQueue(string $queueName): void
    {
        $messageProvider = $this->factory->getMessageProvider($queueName);
        $stackedProcessor = $this->factory->createStackedProcessor($messageProvider, $this->processor);

        $consumer = $this->factory->createConsumer($messageProvider, $stackedProcessor);
        $consumer->consume(['max_messages' => 1, 'max_execution_time' => 3]);

        if (empty($this->processor->getMessage())) {
            throw new \Exception("Could not consume message from queue $queueName");
        }
    }

    /**
     * @Then the message should have property :property equal to :value
     *
     * @throws \Exception
     */
    public function theMessageShouldHavePropertyEqualTo(string $property, string $value): void
    {
        $this->assertArrayHasKey($property, $this->processor->getMessage()->getProperties());
        $this->assertEquals($value, $this->processor->getMessage()->getProperties()[$property]);
    }

    /**
     * @Then the message should have header :header equal to :value
     *
     * @throws \Exception
     */
    public function theMessageShouldHaveHeaderEqualTo(string $header, string $value): void
    {
        $this->assertArrayHasKey('headers', $this->processor->getMessage()->getProperties());
        $this->assertArrayHasKey($header, $this->processor->getMessage()->getProperties()['headers']);
        $this->assertEquals($value, $this->processor->getMessage()->getProperties()['headers'][$header]);
    }

    /**
     * @Then the message body should contain :body
     *
     * @throws \Exception
     */
    public function theMessageBodyShouldContain(string $body): void
    {
        $this->assertContains($body, $this->processor->getMessageBody());
    }

    /**
     * @Then the message body should be equal to :body
     *
     * @throws \Exception
     */
    public function theMessageBodyShouldBeEqualTo(string $body): void
    {
        $this->assertEquals($body, $this->processor->getMessageBody());
    }

    /**
     * @Then the message body should be match to :body
     *
     * @throws \Exception
     */
    public function theMessageBodyShouldBeMatchTo(string $body): void
    {
        $item = json_decode($this->processor->getMessageBody(), true);
        $body = json_decode($body, true);

        $this->assertKeysOfJson(array_keys($body), array_keys($item));
        $this->assertValuesOfJson($body, $item);
    }

    /**
     * @Then the message body should have JSON node :node equal to :value
     *
     * @throws \Exception
     */
    public function theMessageBodyShouldHaveJSONNodeEqualTo(string $node, string $value): void
    {
        $decodedBody = $this->processor->getDecodedMessageBody();
        $this->assertArrayHasKey($node, $decodedBody);
        $this->assertEquals($value, $decodedBody[$node]);
    }

    /**
     * @Then print the message body
     */
    public function printTheMessageBody(): void
    {
        print_r($this->processor->getMessageBody());
    }

    /**
     * @Then print the message properties
     */
    public function printTheMessageProperties(): void
    {
        print_r($this->processor->getMessage()->getProperties());
    }

    public function setFactory(SwarrotFactory $factory): self
    {
        $this->factory = $factory;
        $this->publisher = new MessagePublisher($this->factory->getExchange());

        return $this;
    }
}
