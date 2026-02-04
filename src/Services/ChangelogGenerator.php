<?php

declare(strict_types=1);

namespace ChangeChampion\Services;

use ChangeChampion\Models\Changeset;
use ChangeChampion\Models\Config;

class ChangelogGenerator
{
    private ?string $repositoryUrl = null;
    private array $sections = Config::DEFAULT_SECTIONS;

    public function __construct(
        private readonly string $basePath,
        private readonly string $filename = 'CHANGELOG.md',
    ) {}

    /**
     * Set the repository URL for linking issues.
     */
    public function setRepositoryUrl(?string $url): void
    {
        $this->repositoryUrl = $url;
    }

    /**
     * Set custom section headers.
     */
    public function setSections(array $sections): void
    {
        $this->sections = $sections;
    }

    /**
     * Get the repository URL, auto-detecting from git if not set.
     */
    public function getRepositoryUrl(): ?string
    {
        if (null !== $this->repositoryUrl) {
            return $this->repositoryUrl;
        }

        // Try to auto-detect from git remote
        return $this->detectRepositoryUrl();
    }

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

        // Add sections in order: major, minor, patch
        foreach ([Changeset::TYPE_MAJOR, Changeset::TYPE_MINOR, Changeset::TYPE_PATCH] as $type) {
            if (!empty($grouped[$type])) {
                $lines[] = '### '.$this->getSectionHeader($type);
                $lines[] = '';
                foreach ($grouped[$type] as $changeset) {
                    $lines[] = '- '.$this->formatSummary($changeset->summary);
                }
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get section header for a changeset type.
     */
    private function getSectionHeader(string $type): string
    {
        return $this->sections[$type] ?? ucfirst($type);
    }

    /**
     * Detect repository URL from git remote.
     */
    private function detectRepositoryUrl(): ?string
    {
        $output = [];
        $returnCode = 0;

        exec('git -C '.escapeshellarg($this->basePath).' remote get-url origin 2>/dev/null', $output, $returnCode);

        if (0 !== $returnCode || empty($output)) {
            return null;
        }

        $remoteUrl = trim($output[0]);

        // Convert SSH URL to HTTPS URL
        // git@github.com:owner/repo.git -> https://github.com/owner/repo
        if (preg_match('/^git@([^:]+):(.+?)(?:\.git)?$/', $remoteUrl, $matches)) {
            return 'https://'.$matches[1].'/'.$matches[2];
        }

        // Already HTTPS URL, strip .git suffix
        // https://github.com/owner/repo.git -> https://github.com/owner/repo
        if (preg_match('/^https?:\/\/(.+?)(?:\.git)?$/', $remoteUrl, $matches)) {
            return 'https://'.$matches[1];
        }

        return null;
    }

    private function formatSummary(string $summary): string
    {
        // Take first line only for bullet points
        $lines = explode("\n", trim($summary));
        $text = trim($lines[0]);

        // Link issue references if repository URL is available
        $repoUrl = $this->getRepositoryUrl();
        if (null !== $repoUrl) {
            $text = $this->linkIssues($text, $repoUrl);
        }

        return $text;
    }

    /**
     * Convert issue references to markdown links.
     *
     * Patterns matched:
     * - #123
     * - Fixes #123
     * - Closes #123
     * - Resolves #123
     */
    private function linkIssues(string $text, string $repoUrl): string
    {
        // Match issue references that aren't already linked
        // Negative lookbehind to avoid matching already-linked issues like [#123](url)
        return preg_replace_callback(
            '/(?<!\[)#(\d+)(?!\])/',
            fn ($matches) => '[#'.$matches[1].']('.$repoUrl.'/issues/'.$matches[1].')',
            $text
        );
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
