<?php

declare(strict_types=1);

namespace ChangeChampion\Commands;

use ChangeChampion\Services\ConfigManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'init',
    description: 'Initialize changesets in the current project'
)]
class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('with-github-actions', null, InputOption::VALUE_NONE, 'Also install GitHub Actions workflows for automation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = getcwd();

        $configManager = new ConfigManager($basePath);

        // Check if composer.json exists
        if (!file_exists($configManager->getComposerJsonPath())) {
            $io->error('No composer.json found in the current directory.');

            return Command::FAILURE;
        }

        // Check if already initialized
        $alreadyInitialized = $configManager->isInitialized();
        if ($alreadyInitialized && !$input->getOption('with-github-actions')) {
            $io->warning('Changesets already initialized in this project.');

            return Command::SUCCESS;
        }

        // Initialize .changes directory
        if (!$alreadyInitialized) {
            $configManager->initialize();
            $io->text('✓ Created .changes directory with config');
        }

        // Install GitHub Actions workflows
        if ($input->getOption('with-github-actions')) {
            $this->installGitHubActions($basePath, $io);
        }

        $io->success('Changesets initialized!');
        $io->note([
            'Next steps:',
            '  1. Run "cc add" to create your first changeset',
            '  2. Commit the .changes directory to your repository',
        ]);

        return Command::SUCCESS;
    }

    private function installGitHubActions(string $basePath, SymfonyStyle $io): void
    {
        $workflowsDir = $basePath.'/.github/workflows';

        if (!is_dir($workflowsDir)) {
            mkdir($workflowsDir, 0o755, true);
        }

        // Find the package's resources directory
        $resourcesDir = $this->getResourcesDir();

        if (!is_dir($resourcesDir)) {
            $io->warning('Could not find workflow templates. Please copy them manually from the package.');

            return;
        }

        $finder = new Finder();
        $finder->files()->in($resourcesDir.'/github-workflows')->name('*.yml');

        $copied = 0;
        foreach ($finder as $file) {
            $targetPath = $workflowsDir.'/'.$file->getFilename();

            if (file_exists($targetPath)) {
                $io->text("  Skipped {$file->getFilename()} (already exists)");

                continue;
            }

            copy($file->getPathname(), $targetPath);
            $io->text("✓ Created .github/workflows/{$file->getFilename()}");
            ++$copied;
        }

        if ($copied > 0) {
            $io->newLine();
            $io->note([
                'GitHub Actions installed! Remember to:',
                '  1. Enable "Allow GitHub Actions to create and approve pull requests"',
                '     in Settings → Actions → General → Workflow permissions',
            ]);
        }
    }

    private function getResourcesDir(): string
    {
        // When installed as a dependency: vendor/offload-project/change-composer/resources
        // When running from source: ./resources
        $paths = [
            __DIR__.'/../../resources',
            __DIR__.'/../../../resources',
        ];

        foreach ($paths as $path) {
            $realPath = realpath($path);
            if ($realPath && is_dir($realPath)) {
                return $realPath;
            }
        }

        return __DIR__.'/../../resources';
    }
}
