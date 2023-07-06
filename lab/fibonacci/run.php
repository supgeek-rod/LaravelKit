<?php

/**
 * 自动加载
 */
include_once __DIR__ . '/src/autoload.php';

/**
 * Fibonacci
 */
class Fibonacci extends FibonacciAbstract
{
    public function getNumByIteration(int $start, int $times): int
    {
        $result = 0;
        $previous = $start;

        while ($times) {
            $result += $previous;
            $previous = ($result - $previous) ?: $previous;
            $times--;
        }

        return $result;
    }

    public function getNumByRecursion(int $first, int $second, int $times): int
    {
        if ($times > 1) {
            return $this->recursion($second, ($first + $second), --$times);
        } else {
            return $second;
        }
    }
}

/*  数列
// 1:   1   2   3   5   8
// 3:   3   6   9   15  24
// 9:   9   18  27  45  72
// 10:  10  20  30  50  80
 */

echo Fibonacci::getInstance()->getNumByIteration(1, 5) . ':' . Fibonacci::getInstance()->getNumByIteration(1, 5) . PHP_EOL;         // print => 8:8
echo Fibonacci::getInstance()->getNumByIteration(3, 5) . ':' . Fibonacci::getInstance()->getNumByIteration(3, 5) . PHP_EOL;         // print => 24:24
echo Fibonacci::getInstance()->getNumByIteration(9, 5) . ':' . Fibonacci::getInstance()->getNumByIteration(9, 5) . PHP_EOL;         // print => 72:72
echo Fibonacci::getInstance()->getNumByIteration(10, 5) . ':' . Fibonacci::getInstance()->getNumByIteration(10, 5) . PHP_EOL;       // print => 80:80

