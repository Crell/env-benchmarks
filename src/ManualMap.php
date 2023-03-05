<?php

declare(strict_types=1);

namespace Crell\EnvBench;

use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\compose;
use function Crell\fp\explode;
use function Crell\fp\flatten;
use function Crell\fp\implode;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;
use function Crell\fp\prop;
use function Crell\fp\replace;

class ManualMap
{
    public function map(array $envArray, string $class): object
    {
        $rClass = new \ReflectionClass($class);

        $rProperties = $rClass->getProperties();

        $env = $rClass->newInstanceWithoutConstructor();

        $populator = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        $toSet = [];
        foreach ($rProperties as $rProp) {
            $propName = $rProp->getName();
            if (isset($envArray[$propName])) {
                $toSet[$propName] = $this->typeNormalize($envArray[$propName]);
            } elseif ($defaultValue = $this->getDefaultValueFromConstructor($rProp)) {
                $toSet[$propName] = $defaultValue;
            }
        }

        $populator->call($env, $toSet);

        return $env;
    }

    public function mapDynamicCaseFolding(array $envArray, string $class): object
    {
        $rClass = new \ReflectionClass($class);

        $rProperties = $rClass->getProperties();

        $normalizer = compose(prop('name'), $this->splitString(...),implode('_'), strtoupper(...));

        $normalizedNames = pipe($rProperties,
            amap($normalizer),
        );

        $propList = array_combine($normalizedNames, $rProperties);

        $populator = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        $toSet = [];
        foreach ($propList as $envName => $rProp) {
            $propName = $rProp->getName();
            if (isset($envArray[$envName])) {
                $toSet[$propName] = $this->typeNormalize($envArray[$envName]);
            } elseif ($defaultValue = $this->getDefaultValueFromConstructor($rProp)) {
                $toSet[$propName] = $defaultValue;
            }
        }

        $env = $rClass->newInstanceWithoutConstructor();
        $populator->call($env, $toSet);

        return $env;
    }

    public function mapDynamicCaseFoldingOptimized(array $envArray, string $class): object
    {
        $rClass = new \ReflectionClass($class);

        $rProperties = $rClass->getProperties();

        $toSet = [];
        foreach ($rProperties as $rProp) {
            $propName = $rProp->getName();
            $envName = $this->normalizeName($propName);
            if (isset($envArray[$envName])) {
                $toSet[$propName] = $this->typeNormalize($envArray[$envName]);
            } elseif ($defaultValue = $this->getDefaultValueFromConstructor($rProp)) {
                $toSet[$propName] = $defaultValue;
            }
        }

        $populator = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        $env = $rClass->newInstanceWithoutConstructor();
        $populator->call($env, $toSet);

        return $env;
    }

    /**
     * Normalizes a scalar value to its most-restrictive type.
     *
     * Env values are always imported as strings, but if we want to
     * push them into well-typed numeric fields we need to cast them
     * appropriately.
     *
     * @param mixed $val
     *   The value to normalize.
     * @return int|float|string
     *   The passed value, but now with the correct type.
     */
    private function typeNormalize(mixed $val): int|float|string
    {
        if (!is_numeric($val)) {
            return $val;
        }

        // It's either a float or an int, but floor() wants a float.
        $val = (float) $val;

        if (floor($val) === $val) {
            return (int) $val;
        }
        return (float) $val;
    }


    /**
     * This is amazingly slow.  It adds about 0.02ms to the runtime all on its own, which
     * for the optimized version means *doubling* the runtime.
     *
     * @param \ReflectionProperty $subject
     * @return mixed
     */
    protected function getDefaultValueFromConstructor(\ReflectionProperty $subject): mixed
    {
        // This could be an object property, but this keeps it consistent with the version in Serde
        // for easier maintenance, for now.
        static $params = [];

        $declaringClass = $subject->getDeclaringClass();
        /** @var array<string, \ReflectionParameter> $params */
        $params[$declaringClass->getName()] ??= pipe($declaringClass->getConstructor()?->getParameters() ?? [],
            indexBy(method('getName')),
        );

        $param = $params[$declaringClass->getName()][$subject->getName()] ?? null;

        return $param?->isDefaultValueAvailable()
            ? $param->getDefaultValue()
            : null;
    }

    /**
     * Normalizes a string to UPPER_CASE, as that's what env vars almost always use.
     */
    protected function normalizeName(string $input): string
    {
        $words = preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /* don't return empty elements */
            | PREG_SPLIT_DELIM_CAPTURE /* don't strip anything from output array */
        );

        return \implode('_', array_map(strtoupper(...), $words));
    }

    /**
     * @return string[]
     */
    protected function splitString(string $input): array
    {
        $words = preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /* don't return empty elements */
            | PREG_SPLIT_DELIM_CAPTURE /* don't strip anything from output array */
        );

        return pipe($words,
            amap(replace('_', ' ')),
            amap(explode(' ')),
            flatten(...),
            amap(trim(...)),
            afilter(),
        );
    }
}
