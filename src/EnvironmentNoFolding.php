<?php

declare(strict_types=1);

namespace Crell\EnvBench;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Renaming\Cases;

class EnvironmentNoFolding
{
    public function __construct(
        public readonly string $php_version,
        public readonly string $xdebug_mode,
        public readonly string $path,
        public readonly string $hostname,
        #[Field(strict: false)]
        public readonly int $shlvl,
        public readonly string $missing = 'default',
    ) {}
}
