<?php

declare(strict_types=1);

namespace App\Config;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

final class LoggerFactory
{
    public static function create(): Logger
    {
        $levelName = strtolower((string) ($_ENV['LOG_LEVEL'] ?? 'debug'));

        $level = match ($levelName) {
            'emergency' => Level::Emergency,
            'alert' => Level::Alert,
            'critical' => Level::Critical,
            'error' => Level::Error,
            'warning' => Level::Warning,
            'notice' => Level::Notice,
            'info' => Level::Info,
            default => Level::Debug,
        };

        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logger = new Logger('mapa-relacional');
        $logger->pushHandler(new StreamHandler($logDir . '/app.log', $level));

        return $logger;
    }
}
