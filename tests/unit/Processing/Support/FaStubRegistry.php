<?php

declare(strict_types=1);

/**
 * Registry controlling the FrontAccounting function doubles used by the
 * transaction-processing tests.
 *
 * The doubles themselves live in the namespace of the code under test
 * (see handler_fa_stubs.php) so that the unqualified calls made by the
 * handlers resolve to them first, exactly as PHP resolves an unqualified
 * function call inside a namespace.
 *
 * This registry lets a test decide what a given FrontAccounting function
 * returns, arm it to throw, and afterwards assert on how it was called.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Mutable registry shared by the FrontAccounting function doubles.
 */
final class FaStubRegistry
{
    /**
     * Configured return values, keyed by function name.
     *
     * @var array<string, mixed>
     */
    private static $returns = [];

    /**
     * Recorded calls, keyed by function name.
     *
     * @var array<string, array<int, array<int, mixed>>>
     */
    private static $calls = [];

    /**
     * Exceptions armed for a function, keyed by function name.
     *
     * @var array<string, \Throwable>
     */
    private static $exceptions = [];

    /**
     * Reset every recorded value.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$returns = [];
        self::$calls = [];
        self::$exceptions = [];
    }

    /**
     * Configure the value a double should return.
     *
     * @param string $function Function name.
     * @param mixed  $value    Value to return.
     *
     * @return void
     */
    public static function setReturn(string $function, $value): void
    {
        self::$returns[$function] = $value;
    }

    /**
     * Read the configured return value.
     *
     * @param string $function Function name.
     * @param mixed  $default  Value used when nothing is configured.
     *
     * @return mixed
     */
    public static function getReturn(string $function, $default = null)
    {
        if (array_key_exists($function, self::$returns)) {
            return self::$returns[$function];
        }

        return $default;
    }

    /**
     * Arm (or disarm) an exception for a double.
     *
     * @param string           $function  Function name.
     * @param \Throwable|null  $exception Exception to throw, or null to disarm.
     *
     * @return void
     */
    public static function setException(string $function, ?\Throwable $exception): void
    {
        if ($exception === null) {
            unset(self::$exceptions[$function]);

            return;
        }

        self::$exceptions[$function] = $exception;
    }

    /**
     * Throw the armed exception, if any.
     *
     * @param string $function Function name.
     *
     * @return void
     * @throws \Throwable When an exception is armed for the function.
     */
    public static function throwIfArmed(string $function): void
    {
        if (isset(self::$exceptions[$function])) {
            throw self::$exceptions[$function];
        }
    }

    /**
     * Record a call to a double.
     *
     * @param string $function  Function name.
     * @param array  $arguments Call arguments.
     *
     * @return void
     */
    public static function record(string $function, array $arguments): void
    {
        self::$calls[$function][] = $arguments;
    }

    /**
     * All recorded calls for a function.
     *
     * @param string $function Function name.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function calls(string $function): array
    {
        return self::$calls[$function] ?? [];
    }

    /**
     * Number of recorded calls for a function.
     *
     * @param string $function Function name.
     *
     * @return int
     */
    public static function callCount(string $function): int
    {
        return count(self::$calls[$function] ?? []);
    }

    /**
     * The most recent call for a function.
     *
     * @param string $function Function name.
     *
     * @return array<int, mixed>|null Null when the function was never called.
     */
    public static function lastCall(string $function): ?array
    {
        $calls = self::$calls[$function] ?? [];

        if ($calls === []) {
            return null;
        }

        return $calls[count($calls) - 1];
    }
}
