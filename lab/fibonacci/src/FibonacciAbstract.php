<?php

abstract class FibonacciAbstract implements FibonacciInterface
{
    public static FibonacciInterface $instance;

    public static function getInstance(): FibonacciInterface
    {
        if (! isset(self::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    abstract public function getNumByIteration(int $start, int $times): int;

    abstract public function getNumByRecursion(int $first, int $second, int $times): int;
}
