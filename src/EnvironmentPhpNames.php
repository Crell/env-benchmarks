<?php

declare(strict_types=1);

namespace Crell\EnvBench;

class EnvironmentPhpNames
{
    public function __construct(
        public readonly string $phpVersion,
        public readonly string $xdebug_mode,
        public readonly string $path,
        public readonly string $hostname,
        public readonly int $shlvl,
        public readonly string $missing = 'default',
    ) {}
}
