# twentytwo-labs/behat-amqp

This project is an extension built for Behat that allows you to test
AMQP messages.

It is based on Swarrot library and PECL AMQP php extension.

## Setup

Simply add the
- AmqpContext to your behat.yml config:
```yaml
default:
    suites:
        your_suite:
            ...
            contexts:
                - ...
                - 'TwentytwoLabs\BehatAmqpExtension\Context\AmqpContext'
    
``` 

- BehatAmqpExtension to your behat.yml config:
```yaml
default:
   extension:
    ...
    TwentytwoLabs\BehatAmqpExtension: ~
```

By default the BehatAmqpExtension uses the default connection to RabbitMQ:
``` yaml
host: localhost
port: 5672
vhost: /
login: guest
password: guest
```

But you can override this configuration with your own values when you add the AmqpContext to
your behat.yml file:
``` yaml
default:
   extension:
    ...
    TwentytwoLabs\BehatAmqpExtension:
        host: your_custom_host
        port: 5672
        vhost: /
        login: your_custom_login
        password: your_custom_password
``` 

## How to use

In your Behat test scenarios you can use these steps to test your AMQP Messages:

- `Then I set message properties:` (with properties described as YAML in a Gherkin PyStringNode)
- `Then I set message body:` (with body as a Gherkin PyStringNode)
- `Then I publish message with routing key :routingKey` (this will publish a message to RabbitMQ with previously set properties and/or body)
- `Then I purge queue :queue_name` (will purge all messages in that queue)
- `Given I have :count message(s) in amqp :transport queue`
- `Given I have messages in amqp :transport queue`
- `Given I wait :sleep second(s)`
- `Then I consume a message from queue :queue_name`
- `Then the message should have property :property equal to :value`
- `Then the message should have header :header equal to :value`
- `Then the message body should contain :body`
- `Then the message body should be equal to :body`
- `Then the message body should be match to :body`
- `Then the message body should have JSON node :node equal to :value`
- `Then print the message body` (to display the content of your message in console)
- `Then print the message properties` (to display the message properties in console)

For a fully functional example see our Behat feature file: `features/context.feature`

## Licence

MIT
