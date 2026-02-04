<?php

declare(strict_types=1);

namespace ChangeChampion\Tests\Unit\Services;

use ChangeChampion\Models\Changeset;
use ChangeChampion\Services\VersionCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class VersionCalculatorTest extends TestCase
{
    private VersionCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new VersionCalculator();
    }

    public function testBumpVersionPatch(): void
    {
        $this->assertSame('1.2.4', $this->calculator->bumpVersion('1.2.3', 'patch'));
    }

    public function testBumpVersionMinor(): void
    {
        $this->assertSame('1.3.0', $this->calculator->bumpVersion('1.2.3', 'minor'));
    }

    public function testBumpVersionMajor(): void
    {
        $this->assertSame('2.0.0', $this->calculator->bumpVersion('1.2.3', 'major'));
    }

    public function testBumpVersionWithVPrefix(): void
    {
        $this->assertSame('1.2.4', $this->calculator->bumpVersion('v1.2.3', 'patch'));
    }

    public function testBumpVersionWithCapitalVPrefix(): void
    {
        $this->assertSame('1.2.4', $this->calculator->bumpVersion('V1.2.3', 'patch'));
    }

    public function testBumpVersionFromZero(): void
    {
        $this->assertSame('0.0.1', $this->calculator->bumpVersion('0.0.0', 'patch'));
        $this->assertSame('0.1.0', $this->calculator->bumpVersion('0.0.0', 'minor'));
        $this->assertSame('1.0.0', $this->calculator->bumpVersion('0.0.0', 'major'));
    }

    public function testBumpVersionWithTwoPartVersion(): void
    {
        $this->assertSame('1.3.0', $this->calculator->bumpVersion('1.2', 'minor'));
    }

    public function testBumpVersionWithOnePartVersion(): void
    {
        $this->assertSame('2.0.0', $this->calculator->bumpVersion('1', 'major'));
    }

    public function testGetHighestBumpTypeWithMajor(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('major'),
            $this->createChangeset('minor'),
        ];

        $this->assertSame('major', $this->calculator->getHighestBumpType($changesets));
    }

    public function testGetHighestBumpTypeWithMinor(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('minor'),
            $this->createChangeset('patch'),
        ];

        $this->assertSame('minor', $this->calculator->getHighestBumpType($changesets));
    }

    public function testGetHighestBumpTypeWithOnlyPatch(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('patch'),
        ];

        $this->assertSame('patch', $this->calculator->getHighestBumpType($changesets));
    }

    public function testGetHighestBumpTypeWithSingleChangeset(): void
    {
        $changesets = [$this->createChangeset('minor')];

        $this->assertSame('minor', $this->calculator->getHighestBumpType($changesets));
    }

    public function testCalculateNextVersionWithNoChangesets(): void
    {
        $this->assertSame('1.2.3', $this->calculator->calculateNextVersion('1.2.3', []));
    }

    public function testCalculateNextVersionWithPatchChangesets(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('patch'),
        ];

        $this->assertSame('1.2.4', $this->calculator->calculateNextVersion('1.2.3', $changesets));
    }

    public function testCalculateNextVersionWithMixedChangesets(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('minor'),
            $this->createChangeset('patch'),
        ];

        $this->assertSame('1.3.0', $this->calculator->calculateNextVersion('1.2.3', $changesets));
    }

    public function testCalculateNextVersionWithMajorChangeset(): void
    {
        $changesets = [
            $this->createChangeset('patch'),
            $this->createChangeset('major'),
        ];

        $this->assertSame('2.0.0', $this->calculator->calculateNextVersion('1.2.3', $changesets));
    }

    private function createChangeset(string $type): Changeset
    {
        return new Changeset(
            id: 'test-'.uniqid(),
            type: $type,
            summary: 'Test changeset',
            filePath: '/tmp/test.md'
        );
    }
}
