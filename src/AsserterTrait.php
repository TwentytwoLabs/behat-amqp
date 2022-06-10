<?php

namespace TwentytwoLabs\BehatAmqpExtension;

trait AsserterTrait
{
    protected function assertArrayHasKey(string $key, array $array): void
    {
        if (array_key_exists($key, $array)) {
            return;
        }

        throw new \Exception("$key not found");
    }

    protected function assertEquals(string $expected, string $actual): void
    {
        if ($expected === $actual) {
            return;
        }

        throw new \Exception("$actual does not match expected \"$expected\"");
    }

    protected function assertContains(string $item, string $content): void
    {
        if (preg_match("/$item/", $content)) {
            return;
        }

        throw new \Exception("$item not found in \"$content\"");
    }

    protected function assertTrue($value, $message = 'The value is false'): void
    {
        $this->assert($value, $message);
    }

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
}
