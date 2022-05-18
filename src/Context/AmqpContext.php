<?php

declare(strict_types=1);

namespace TwentytwoLabs\BehatAmqp\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Swarrot\Processor\ProcessorInterface;
use Symfony\Component\Yaml\Yaml;
use TwentytwoLabs\BehatAmqp\Factory\SwarrotFactory;
use TwentytwoLabs\BehatAmqp\Processor\MessageProcessor;
use TwentytwoLabs\BehatAmqp\Publisher\MessagePublisher;

class AmqpContext implements Context
{
    protected SwarrotFactory $factory;
    protected ProcessorInterface $processor;
    protected MessagePublisher $publisher;

    public function __construct(
        string $host = 'localhost',
        int $port = 5672,
        string $vhost = '/',
        string $login = 'guest',
        string $password = 'guest'
    ) {
        $this->factory = new SwarrotFactory($host, $port, $vhost, $login, $password);
        $this->processor = new MessageProcessor();
        $this->publisher = new MessagePublisher($this->factory->getExchange());
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
     * @Given I wait :sleep second(s)
     */
    public function iWait(int $sleep): void
    {
        sleep($sleep);
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

    /**
     * @throws \Exception
     */
    protected function assertArrayHasKey(string $key, array $array): void
    {
        if (array_key_exists($key, $array)) {
            return;
        }

        throw new \Exception("$key not found");
    }

    /**
     * @throws \Exception
     */
    protected function assertEquals(string $expected, string $actual): void
    {
        if ($expected === $actual) {
            return;
        }

        throw new \Exception("$actual does not match expected \"$expected\"");
    }

    /**
     * @throws \Exception
     */
    protected function assertContains(string $item, string $content): void
    {
        if (preg_match("/$item/", $content)) {
            return;
        }

        throw new \Exception("$item not found in \"$content\"");
    }

    /**
     * @throws \Exception
     */
    protected function assertValuesOfJson(array $expectedItem, array $item): void
    {
        foreach ($expectedItem as $key => $expected) {
            if ('<int>' === $expected) {
                $this->assertTrue(\is_int($item[$key]));
            } elseif ('<string>' === $expected) {
                $this->assertTrue(\is_string($item[$key]) && !empty($item[$key]));
            } elseif ('<uuid>' === $expected) {
                $this->assertTrue(!empty($item[$key]));
            } elseif ('<dateTime>' === $expected) {
                $this->assertRegex('#[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}\+[0-9]{2}:[0-9]{2}#', $item[$key]);
            } elseif ('<array>' === $expected) {
                $this->assertTrue(\is_array($item[$key]) && !empty($item[$key]));
            } elseif (\is_array($expected)) {
                $this->assertKeysOfJson(array_keys($expected), array_keys($item[$key]), $key);
                $this->assertValuesOfJson($expected, $item[$key]);
            } elseif ('<date>' === $expected) {
                $this->assertRegex('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $item[$key]);
            } else {
                $this->assertSame($expected, $item[$key]);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function assertKeysOfJson(array $expectedKeys, array $columns, ?string $parent = null): void
    {
        $keys = array_diff($expectedKeys, $columns);
        $keysMissing = array_diff($columns, $expectedKeys);

        $message = null;
        $messageParent = null === $parent ? '' : sprintf(' in parent %s', $parent);

        if (!empty($keys)) {
            $message = sprintf('Keys [%s] must not be present %s', implode(', ', $keys), $messageParent);
        }

        if (!empty($keysMissing)) {
            $message = sprintf('%sKeys [%s] are missing %s', null !== $message ? $message.' and ' : '', implode(', ', $keysMissing), $messageParent);
        }

        $this->assertTrue(null === $message, $message);
    }

    /**
     * @throws \Exception
     */
    protected function assertTrue($value, $message = 'The value is false'): void
    {
        $this->assert($value, $message);
    }

    /**
     * @throws \Exception
     */
    protected function assertSame($expected, $actual, $message = null): void
    {
        $this->assert($expected === $actual, $message ?: "The element '$actual' is not equal to '$expected'");
    }

    protected function assert($test, $message): void
    {
        if (false === $test) {
            throw new \Exception($message);
        }
    }

    private function assertRegex(string $regex, string $actual): void
    {
        if (0 === preg_match($regex, $actual)) {
            throw new \Exception(sprintf("The node value is '%s'", json_encode($actual)));
        }
    }
}
