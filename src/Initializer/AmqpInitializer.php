<?php

declare(strict_types=1);

namespace TwentytwoLabs\BehatAmqp\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use TwentytwoLabs\ArrayComparator\Comparator\ComparatorChain;
use TwentytwoLabs\BehatAmqpExtension\Context\AmqpContext;
use TwentytwoLabs\BehatAmqpExtension\Factory\SwarrotFactory;

/**
 * class AmqpInitializer.
 */
class AmqpInitializer implements ContextInitializer
{
    private ComparatorChain $comparatorChain;
    private SwarrotFactory $factory;

    public function __construct(ComparatorChain $comparatorChain, SwarrotFactory $factory)
    {
        $this->comparatorChain = $comparatorChain;
        $this->factory = $factory;
    }

    public function initializeContext(Context $context)
    {
        if ($context instanceof AmqpContext) {
            $context
                ->setFactory($this->factory)
                ->setComparatorChain($this->comparatorChain)
            ;
        }
    }
}
