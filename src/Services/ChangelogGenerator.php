<?php

declare(strict_types=1);

namespace ChangeChampion\Services;

use ChangeChampion\Models\Changeset;

class ChangelogGenerator
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $filename = 'CHANGELOG.md',
    ) {}

    public function getChangelogPath(): string
    {
        return $this->basePath.'/'.$this->filename;
    }

    /**
     * Get the latest version from CHANGELOG.md.
     */
    public function getLatestVersion(): ?string
    {
        $content = $this->getExistingContent();

        if (empty($content)) {
            return null;
        }

        // Match first version header (e.g., "## 1.2.3" or "## 1.2.3-alpha.1 - 2024-01-01")
        if (preg_match('/^## (\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?)/m', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Generate or update the CHANGELOG.md file.
     *
     * @param string      $version    The new version
     * @param Changeset[] $changesets The changesets to include
     */
    public function update(string $version, array $changesets): void
    {
        $newEntry = $this->generateEntry($version, $changesets);
        $existingContent = $this->getExistingContent();

        $newContent = $this->mergeContent($newEntry, $existingContent);

        file_put_contents($this->getChangelogPath(), $newContent);
    }

    /**
     * Generate a changelog entry for a version.
     *
     * @param Changeset[] $changesets
     */
    public function generateEntry(string $version, array $changesets): string
    {
        $date = date('Y-m-d');
        $lines = [];
        $lines[] = "## {$version} - {$date}";
        $lines[] = '';

        // Group changesets by type
        $grouped = [
            Changeset::TYPE_MAJOR => [],
            Changeset::TYPE_MINOR => [],
            Changeset::TYPE_PATCH => [],
        ];

        foreach ($changesets as $changeset) {
            $grouped[$changeset->type][] = $changeset;
        }

        // Add breaking changes (major)
        if (!empty($grouped[Changeset::TYPE_MAJOR])) {
            $lines[] = '### Breaking Changes';
            $lines[] = '';
            foreach ($grouped[Changeset::TYPE_MAJOR] as $changeset) {
                $lines[] = '- '.$this->formatSummary($changeset->summary);
            }
            $lines[] = '';
        }

        // Add features (minor)
        if (!empty($grouped[Changeset::TYPE_MINOR])) {
            $lines[] = '### Features';
            $lines[] = '';
            foreach ($grouped[Changeset::TYPE_MINOR] as $changeset) {
                $lines[] = '- '.$this->formatSummary($changeset->summary);
            }
            $lines[] = '';
        }

        // Add fixes (patch)
        if (!empty($grouped[Changeset::TYPE_PATCH])) {
            $lines[] = '### Fixes';
            $lines[] = '';
            foreach ($grouped[Changeset::TYPE_PATCH] as $changeset) {
                $lines[] = '- '.$this->formatSummary($changeset->summary);
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function formatSummary(string $summary): string
    {
        // Take first line only for bullet points
        $lines = explode("\n", trim($summary));

        return trim($lines[0]);
    }

    private function getExistingContent(): string
    {
        $path = $this->getChangelogPath();

        if (!file_exists($path)) {
            return '';
        }

        return file_get_contents($path);
    }

    private function mergeContent(string $newEntry, string $existingContent): string
    {
        if (empty($existingContent)) {
            // Create new changelog with header
            return "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\n".$newEntry;
        }

        // Find the first version header (## x.x.x or ## x.x.x-prerelease.n) and insert before it
        if (preg_match('/^(# .+?\n\n(?:.*?\n\n)?)(## \d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?.*)/s', $existingContent, $matches)) {
            return $matches[1].$newEntry.$matches[2];
        }

        // If no version header found, just append after any header content
        if (preg_match('/^(# .+?\n\n(?:.*?\n\n)?)/s', $existingContent, $matches)) {
            return $matches[1].$newEntry;
        }

        // Fallback: prepend header and new entry
        return "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\n".$newEntry.$existingContent;
    }
}
