<?php

use JetBrains\PhpStorm\Pure;

/**
 * 斐波那契数列
 */
class Fibonacci
{
    protected static Fibonacci $instance;

    public static function getInstance(): Fibonacci
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 使用迭代法
     *
     * @param int $start
     * @param int $times
     * @return int
     */
    public function iteration(int $start, int $times): int
    {
        if ($start <= 0) throw new \http\Exception\InvalidArgumentException('$start must be greater than 0.');
        if ($times <= 0) throw new \http\Exception\InvalidArgumentException('$times must be greater than 0.');

        $result = 0;
        $previous = $start;

        while ($times) {
            $result += $previous;
            $previous = ($result - $previous) ?: $previous;
            $times--;
        }

        return (string) $result;
    }

    /**
     * 使用递归法
     *
     * @param $start
     * @param $times
     */
    public function recursion($start, $times)
    {
    }
}


set_exception_handler(function (Throwable $throwable) {
    echo '[EXCEPTION] ' . $throwable->getMessage();
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    echo '[ERROR]' . $errfile . '@' . $errline .  ': ' . $errstr . PHP_EOL;
});

// 1    1   2   3   5   8
// 2    2   4   6   10  16

echo Fibonacci::getInstance()->iteration($argv[1], $argv[2]) . PHP_EOL;
