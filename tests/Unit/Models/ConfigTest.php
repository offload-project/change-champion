<?php

declare(strict_types=1);

namespace ChangeChampion\Tests\Unit\Models;

use ChangeChampion\Models\Config;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new Config();

        $this->assertSame('main', $config->baseBranch);
        $this->assertTrue($config->changelog);
    }

    public function testCustomValues(): void
    {
        $config = new Config(
            baseBranch: 'develop',
            changelog: false
        );

        $this->assertSame('develop', $config->baseBranch);
        $this->assertFalse($config->changelog);
    }

    public function testFromArrayWithAllValues(): void
    {
        $config = Config::fromArray([
            'baseBranch' => 'master',
            'changelog' => false,
        ]);

        $this->assertSame('master', $config->baseBranch);
        $this->assertFalse($config->changelog);
    }

    public function testFromArrayWithDefaults(): void
    {
        $config = Config::fromArray([]);

        $this->assertSame('main', $config->baseBranch);
        $this->assertTrue($config->changelog);
        $this->assertNull($config->repository);
    }

    public function testFromArrayWithPartialValues(): void
    {
        $config = Config::fromArray([
            'baseBranch' => 'release',
        ]);

        $this->assertSame('release', $config->baseBranch);
        $this->assertTrue($config->changelog);
        $this->assertNull($config->repository);
    }

    public function testFromArrayWithRepository(): void
    {
        $config = Config::fromArray([
            'baseBranch' => 'main',
            'changelog' => true,
            'repository' => 'https://github.com/owner/repo',
        ]);

        $this->assertSame('https://github.com/owner/repo', $config->repository);
    }

    public function testToArray(): void
    {
        $config = new Config(
            baseBranch: 'develop',
            changelog: true
        );

        $array = $config->toArray();

        $this->assertSame([
            'baseBranch' => 'develop',
            'changelog' => true,
            'repository' => null,
        ], $array);
    }

    public function testToArrayWithRepository(): void
    {
        $config = new Config(
            baseBranch: 'main',
            changelog: true,
            repository: 'https://github.com/owner/repo'
        );

        $array = $config->toArray();

        $this->assertSame([
            'baseBranch' => 'main',
            'changelog' => true,
            'repository' => 'https://github.com/owner/repo',
        ], $array);
    }

    public function testRoundTrip(): void
    {
        $original = new Config(
            baseBranch: 'feature',
            changelog: false
        );

        $array = $original->toArray();
        $restored = Config::fromArray($array);

        $this->assertSame($original->baseBranch, $restored->baseBranch);
        $this->assertSame($original->changelog, $restored->changelog);
    }
}
