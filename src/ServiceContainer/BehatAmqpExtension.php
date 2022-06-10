<?php

declare(strict_types=1);

namespace TwentytwoLabs\BehatAmqpExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use JsonSchema\Validator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Encoder\ChainDecoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use TwentytwoLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use TwentytwoLabs\Api\Validator\MessageValidator;
use TwentytwoLabs\ArrayComparator\Comparator\ComparatorChain;
use TwentytwoLabs\ArrayComparator\Comparator\DateComparator;
use TwentytwoLabs\ArrayComparator\Comparator\DateTimeComparator;
use TwentytwoLabs\ArrayComparator\Comparator\IntegerComparator;
use TwentytwoLabs\ArrayComparator\Comparator\SameComparator;
use TwentytwoLabs\ArrayComparator\Comparator\StringComparator;
use TwentytwoLabs\ArrayComparator\Comparator\UuidComparator;
use TwentytwoLabs\BehatAmqp\Initializer\AmqpInitializer;
use TwentytwoLabs\BehatAmqpExtension\Factory\SwarrotFactory;
use TwentytwoLabs\BehatOpenApiExtension\Initializer\JsonInitializer;
use TwentytwoLabs\BehatOpenApiExtension\Initializer\OpenApiInitializer;

/**
 * class BehatAmqpExtension.
 */
class BehatAmqpExtension implements ExtensionInterface
{
    public function getConfigKey(): string
    {
        return 'amqp_extension';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('host')->cannotBeEmpty()->defaultValue('localhost')->end()
                ->scalarNode('port')->cannotBeEmpty()->defaultValue('5672')->end()
                ->scalarNode('vhost')->cannotBeEmpty()->defaultValue('/')->end()
                ->scalarNode('login')->cannotBeEmpty()->defaultValue('guest')->end()
                ->scalarNode('password')->cannotBeEmpty()->defaultValue('guest')->end()
            ->end()
        ;
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $comparatorChainDefinition = new Definition(ComparatorChain::class);
        $comparatorChainDefinition
            ->addMethodCall('addComparators', [new Definition(IntegerComparator::class)])
            ->addMethodCall('addComparators', [new Definition(StringComparator::class)])
            ->addMethodCall('addComparators', [new Definition(DateTimeComparator::class)])
            ->addMethodCall('addComparators', [new Definition(DateComparator::class)])
            ->addMethodCall('addComparators', [new Definition(UuidComparator::class)])
            ->addMethodCall('addComparators', [new Definition(SameComparator::class)])
        ;

        $factoryDefinition = new Definition(
            SwarrotFactory::class,
            [
                '$host' => $config['host'],
                '$port' => $config['port'],
                '$vhost' => $config['vhost'],
                '$login' => $config['login'],
                '$password' => $config['password'],
            ]
        );

        $amqpInitializerDefinition = new Definition(
            AmqpInitializer::class,
            ['$comparatorChain' => $comparatorChainDefinition, '$factory' => $factoryDefinition]
        );
        $amqpInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);

        $container->setDefinition('amqp.context_initializer', $amqpInitializerDefinition);
    }

    public function process(ContainerBuilder $container)
    {
    }
}
