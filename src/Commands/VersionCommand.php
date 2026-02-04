<?php

declare(strict_types=1);

namespace ChangeChampion\Commands;

use ChangeChampion\Services\ChangelogGenerator;
use ChangeChampion\Services\ChangesetManager;
use ChangeChampion\Services\ConfigManager;
use ChangeChampion\Services\VersionCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'version',
    description: 'Apply changesets, bump version, and update changelog'
)]
class VersionCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('no-changelog', null, InputOption::VALUE_NONE, 'Skip changelog generation')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without making changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = getcwd();

        $configManager = new ConfigManager($basePath);
        $changesetManager = new ChangesetManager($configManager);
        $versionCalculator = new VersionCalculator();
        $changelogGenerator = new ChangelogGenerator($basePath);

        if (!$configManager->isInitialized()) {
            $io->error('Changesets not initialized. Run "cc init" first.');

            return Command::FAILURE;
        }

        $changesets = $changesetManager->getAll();

        if (empty($changesets)) {
            $io->warning('No changesets found. Nothing to do.');

            return Command::SUCCESS;
        }

        $config = $configManager->getConfig();
        $currentVersion = $configManager->getCurrentVersion();
        $nextVersion = $versionCalculator->calculateNextVersion($currentVersion, $changesets);
        $bumpType = $versionCalculator->getHighestBumpType($changesets);
        $packageName = $configManager->getPackageName();

        $dryRun = $input->getOption('dry-run');
        $skipChangelog = $input->getOption('no-changelog') || !$config->changelog;

        $io->title($dryRun ? 'Version (Dry Run)' : 'Version');

        $io->text([
            "Package: <info>{$packageName}</info>",
            "Version bump: <info>{$currentVersion}</info> → <info>{$nextVersion}</info> ({$bumpType})",
            'Changesets to apply: <info>'.count($changesets).'</info>',
        ]);

        if ($dryRun) {
            $io->newLine();
            $io->note('Dry run mode - no changes will be made.');

            $io->section('Changes that would be applied:');
            $io->listing([
                $skipChangelog ? 'Skip changelog (disabled)' : "Update CHANGELOG.md with {$nextVersion} section",
                'Delete '.count($changesets).' changeset file(s)',
            ]);

            return Command::SUCCESS;
        }

        // Confirm
        if (!$io->confirm("Apply {$bumpType} bump to version {$nextVersion}?", true)) {
            $io->warning('Aborted.');

            return Command::SUCCESS;
        }

        // Update changelog
        if (!$skipChangelog) {
            $changelogGenerator->update($nextVersion, $changesets);
            $io->text('✓ Updated CHANGELOG.md');
        }

        // Delete changesets
        $changesetManager->deleteAll();
        $io->text('✓ Deleted '.count($changesets).' changeset file(s)');

        $io->newLine();
        $io->success([
            "Version {$nextVersion} is ready!",
            'Next steps:',
            '  1. Review the changes to CHANGELOG.md',
            '  2. Commit the changes',
            '  3. Run "cc publish" to create a git tag (v'.$nextVersion.')',
        ]);

        return Command::SUCCESS;
    }
}
