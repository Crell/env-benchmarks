<?php

declare(strict_types=1);

namespace Crell\EnvBench;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Formatter\ArrayFormatter;
use Crell\Serde\SerdeCommon;
use PHPUnit\Framework\TestCase;

class EnvBenchTest extends TestCase
{

    protected readonly SerdeCommon $serde;
    protected readonly ManualMap $manualMapper;

    public function setUp(): void
    {
        $analyzer = new MemoryCacheAnalyzer(new Analyzer());
        $this->serde = new SerdeCommon(
            analyzer: $analyzer,
            formatters: [new ArrayFormatter()]
        );

        $this->manualMapper = new ManualMap();
    }

    public function test_serde_using_field_rename(): void
    {
        /** @var Environment $env */
        $env = $this->serde->deserialize($_ENV, from: 'array', to: Environment::class);

        self::assertNotNull($env->php_version);
        self::assertNotNull($env->xdebug_mode);
        self::assertNotNull($env->path);
        self::assertNotNull($env->hostname);
        self::assertEquals('default', $env->missing);
    }

    public function test_serde_using_manual_rename(): void
    {
        $envArray = array_combine(
            array_map(strtolower(...), array_keys($_ENV)),
            $_ENV,
        );

        /** @var EnvironmentNoFolding $env */
        $env = $this->serde->deserialize($envArray, from: 'array', to: EnvironmentNoFolding::class);

        self::assertNotNull($env->php_version);
        self::assertNotNull($env->xdebug_mode);
        self::assertNotNull($env->path);
        self::assertNotNull($env->hostname);
        self::assertEquals('default', $env->missing);
    }

    public function test_manual_map_using_manual_rename(): void
    {
        $envArray = array_combine(
            array_map(strtolower(...), array_keys($_ENV)),
            $_ENV,
        );

        /** @var Environment $env */
        $env = $this->manualMapper->map($envArray, Environment::class);

        self::assertNotNull($env->php_version);
        self::assertNotNull($env->xdebug_mode);
        self::assertNotNull($env->path);
        self::assertNotNull($env->hostname);
        self::assertEquals('default', $env->missing);
    }

    public function test_manual_map_using_auto_rename(): void
    {
        /** @var EnvironmentPhpNames $env */
        $env = $this->manualMapper->mapDynamicCaseFolding($_ENV, EnvironmentPhpNames::class);

        self::assertNotNull($env->phpVersion);
        self::assertNotNull($env->xdebug_mode);
        self::assertNotNull($env->path);
        self::assertNotNull($env->hostname);
        self::assertEquals('default', $env->missing);
    }

    public function test_manual_map_using_auto_rename_optimized(): void
    {
        /** @var EnvironmentPhpNames $env */
        $env = $this->manualMapper->mapDynamicCaseFoldingOptimized($_ENV, EnvironmentPhpNames::class);

        self::assertNotNull($env->phpVersion);
        self::assertNotNull($env->xdebug_mode);
        self::assertNotNull($env->path);
        self::assertNotNull($env->hostname);
        self::assertEquals('default', $env->missing);
    }
}
