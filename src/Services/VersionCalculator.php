<?php

declare(strict_types=1);

namespace ChangeChampion\Services;

use ChangeChampion\Models\Changeset;

class VersionCalculator
{
    /**
     * Calculate the next version based on changesets.
     *
     * @param Changeset[] $changesets
     */
    public function calculateNextVersion(string $currentVersion, array $changesets): string
    {
        if (empty($changesets)) {
            return $currentVersion;
        }

        // Determine the highest bump type
        $bumpType = $this->getHighestBumpType($changesets);

        return $this->bumpVersion($currentVersion, $bumpType);
    }

    /**
     * Get the highest priority bump type from changesets.
     * Priority: major > minor > patch.
     *
     * @param Changeset[] $changesets
     */
    public function getHighestBumpType(array $changesets): string
    {
        $hasMajor = false;
        $hasMinor = false;

        foreach ($changesets as $changeset) {
            if (Changeset::TYPE_MAJOR === $changeset->type) {
                $hasMajor = true;

                break; // Can't get higher than major
            }
            if (Changeset::TYPE_MINOR === $changeset->type) {
                $hasMinor = true;
            }
        }

        if ($hasMajor) {
            return Changeset::TYPE_MAJOR;
        }

        if ($hasMinor) {
            return Changeset::TYPE_MINOR;
        }

        return Changeset::TYPE_PATCH;
    }

    /**
     * Bump a version by the specified type.
     */
    public function bumpVersion(string $version, string $type): string
    {
        // Remove 'v' prefix if present
        $version = ltrim($version, 'vV');

        // Parse the version
        $parts = explode('.', $version);

        // Ensure we have at least 3 parts
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        $major = (int) $parts[0];
        $minor = (int) $parts[1];
        $patch = (int) $parts[2];

        switch ($type) {
            case Changeset::TYPE_MAJOR:
                $major++;
                $minor = 0;
                $patch = 0;

                break;

            case Changeset::TYPE_MINOR:
                $minor++;
                $patch = 0;

                break;

            case Changeset::TYPE_PATCH:
            default:
                $patch++;

                break;
        }

        return sprintf('%d.%d.%d', $major, $minor, $patch);
    }
}
