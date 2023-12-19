<?php

namespace App\Logging;

use Monolog\Logger;

class DatabaseLogger
{
    public function __invoke(array $config)
    {
        return new Logger('database', [new DatabaseLogHandler()]);
    }
}
