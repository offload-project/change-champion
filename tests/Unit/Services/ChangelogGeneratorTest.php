<?php

declare(strict_types=1);

namespace ChangeChampion\Tests\Unit\Services;

use ChangeChampion\Models\Changeset;
use ChangeChampion\Services\ChangelogGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ChangelogGeneratorTest extends TestCase
{
    private string $tempDir;
    private ChangelogGenerator $generator;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/change-champion-test-'.uniqid();
        mkdir($this->tempDir, 0o755, true);
        $this->generator = new ChangelogGenerator($this->tempDir, 'TEST_CHANGELOG.md');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGetChangelogPath(): void
    {
        $this->assertSame($this->tempDir.'/TEST_CHANGELOG.md', $this->generator->getChangelogPath());
    }

    public function testUpdateCreatesNewChangelog(): void
    {
        $changesets = [
            $this->createChangeset('minor', 'Add new feature'),
            $this->createChangeset('patch', 'Fix bug'),
        ];

        $this->generator->update('1.0.0', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        $this->assertStringContainsString('# Changelog', $content);
        $this->assertStringContainsString('## 1.0.0', $content);
        $this->assertStringContainsString('### Features', $content);
        $this->assertStringContainsString('Add new feature', $content);
        $this->assertStringContainsString('### Fixes', $content);
        $this->assertStringContainsString('Fix bug', $content);
    }

    public function testUpdateWithMajorChanges(): void
    {
        $changesets = [
            $this->createChangeset('major', 'Breaking API change'),
        ];

        $this->generator->update('2.0.0', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        $this->assertStringContainsString('### Breaking Changes', $content);
        $this->assertStringContainsString('Breaking API change', $content);
    }

    public function testUpdatePrependsToExistingChangelog(): void
    {
        // Create existing changelog
        $existingContent = <<<'CHANGELOG'
            # Changelog

            All notable changes to this project will be documented in this file.

            ## 0.1.0 - 2024-01-01

            ### Features

            - Initial release
            CHANGELOG;
        file_put_contents($this->generator->getChangelogPath(), $existingContent);

        $changesets = [
            $this->createChangeset('minor', 'New feature in 0.2.0'),
        ];

        $this->generator->update('0.2.0', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        // New version should come before old version
        $pos020 = strpos($content, '## 0.2.0');
        $pos010 = strpos($content, '## 0.1.0');

        $this->assertNotFalse($pos020);
        $this->assertNotFalse($pos010);
        $this->assertLessThan($pos010, $pos020);
        $this->assertStringContainsString('New feature in 0.2.0', $content);
        $this->assertStringContainsString('Initial release', $content);
    }

    public function testUpdateIncludesDate(): void
    {
        $changesets = [
            $this->createChangeset('patch', 'Fix something'),
        ];

        $this->generator->update('1.0.1', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());
        $today = date('Y-m-d');

        $this->assertStringContainsString("## 1.0.1 - {$today}", $content);
    }

    public function testUpdateGroupsChangesetsByType(): void
    {
        $changesets = [
            $this->createChangeset('patch', 'Fix 1'),
            $this->createChangeset('minor', 'Feature 1'),
            $this->createChangeset('patch', 'Fix 2'),
            $this->createChangeset('major', 'Breaking 1'),
            $this->createChangeset('minor', 'Feature 2'),
        ];

        $this->generator->update('2.0.0', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        // Check order: Breaking Changes -> Features -> Fixes
        $posBreaking = strpos($content, '### Breaking Changes');
        $posFeatures = strpos($content, '### Features');
        $posFixes = strpos($content, '### Fixes');

        $this->assertLessThan($posFeatures, $posBreaking);
        $this->assertLessThan($posFixes, $posFeatures);
    }

    public function testIssueLinkingWithRepositoryUrl(): void
    {
        $this->generator->setRepositoryUrl('https://github.com/owner/repo');

        $changesets = [
            $this->createChangeset('patch', 'Fix bug. Fixes #123'),
        ];

        $this->generator->update('1.0.1', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        $this->assertStringContainsString('[#123](https://github.com/owner/repo/issues/123)', $content);
    }

    public function testIssueLinkingMultipleIssues(): void
    {
        $this->generator->setRepositoryUrl('https://github.com/owner/repo');

        $changesets = [
            $this->createChangeset('patch', 'Fix bugs #1, #2 and #3'),
        ];

        $this->generator->update('1.0.1', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        $this->assertStringContainsString('[#1](https://github.com/owner/repo/issues/1)', $content);
        $this->assertStringContainsString('[#2](https://github.com/owner/repo/issues/2)', $content);
        $this->assertStringContainsString('[#3](https://github.com/owner/repo/issues/3)', $content);
    }

    public function testNoIssueLinkingWithoutRepositoryUrl(): void
    {
        // Don't set repository URL and use temp dir without git
        $changesets = [
            $this->createChangeset('patch', 'Fix bug #123'),
        ];

        $this->generator->update('1.0.1', $changesets);

        $content = file_get_contents($this->generator->getChangelogPath());

        // Should contain the raw #123, not linked
        $this->assertStringContainsString('#123', $content);
        $this->assertStringNotContainsString('[#123]', $content);
    }

    private function createChangeset(string $type, string $summary): Changeset
    {
        return new Changeset(
            id: 'test-'.uniqid(),
            type: $type,
            summary: $summary,
            filePath: '/tmp/test.md'
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
