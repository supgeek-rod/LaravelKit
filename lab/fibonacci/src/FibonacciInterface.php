<?php

interface FibonacciInterface
{
    public static function getInstance(): FibonacciInterface;

    public function getNumByIteration(int $start, int $times): int;

    public function getNumByRecursion(int $first, int $second, int $times): int;
}
