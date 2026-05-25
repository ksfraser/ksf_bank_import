<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Support;

final class ExceptionDisplayNotifier
{
    /**
     * Display exception details in legacy FA UI and fall back to error_log.
     */
    public static function notify(\Throwable $exception, string $file, int $line, string $context = ''): void
    {
        $prefix = $context !== '' ? ($context . ' ') : '';
        $message = $prefix . $file . '::' . $line . '::' . print_r($exception, true);

        if (function_exists('display_notification')) {
            display_notification($message);
            return;
        }

        error_log($message);
    }
}
