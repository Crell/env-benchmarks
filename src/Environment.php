<?php

declare(strict_types=1);

namespace Crell\EnvBench;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Renaming\Cases;

class Environment
{
    public function __construct(
        #[Field(renameWith: Cases::UPPERCASE)]
        public readonly string $php_version,
        #[Field(renameWith: Cases::UPPERCASE)]
        public readonly string $xdebug_mode,
        #[Field(renameWith: Cases::UPPERCASE)]
        public readonly string $path,
        #[Field(renameWith: Cases::UPPERCASE)]
        public readonly string $hostname,
        #[Field(renameWith: Cases::UPPERCASE, strict: false)]
        public readonly int $shlvl,
        #[Field(renameWith: Cases::UPPERCASE)]
        public readonly string $missing = 'default',
    ) {}
}
