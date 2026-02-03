<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Tools\Plugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

final class PluginReleaseCommand extends AbstractPluginCommand
{
    private string $dist_dir;
    private string $plugin_name = '';
    private string $commit = '';

    private const BANNED_FILES = [
        '.git*',
        '.gh_token',
        '.tx/',
        'tools/',
        'tests/',
        '.atoum.php',
        '.travis.yml',
        '.circleci/',
        '.ignore-release',
        '.stylelintrc.js',
        '.twig_cs.dist.php',
        'rector.php',
        'phpstan.neon',
        '.phpcs.xml',
        'phpunit.xml',
        'phpunit.xml.dist',
    ];

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:plugin:release');
        $this->setDescription('Build a GLPI plugin release archive.');

        $this->addOption('dest', 'd', InputOption::VALUE_REQUIRED, 'Destination path for the archive (e.g., /build/glpi-myplugin-1.0.0.tar.bz2)');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force rebuild even if release exists');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin_dir = $this->getPluginDirectory();

        if (!file_exists($plugin_dir . '/setup.php')) {
            $this->io->error('Current directory is not a valid GLPI plugin.');
            return Command::FAILURE;
        }

        $this->plugin_name = $this->getPluginName();

        $dest = $input->getOption('dest');
        if ($dest === null) {
            $this->io->error('The --dest option is required.');
            return Command::FAILURE;
        }

        // Resolve relative paths based on plugin directory
        if (!str_starts_with($dest, '/')) {
            $dest = $plugin_dir . '/' . $dest;
        }

        // Ensure parent directory exists
        $this->dist_dir = dirname($dest);
        if (!is_dir($this->dist_dir) && !mkdir($this->dist_dir, 0o777, true)) {
            $this->io->error(sprintf('Unable to create the `%s` directory.', $this->dist_dir));
            return Command::FAILURE;
        }

        if (!$input->getOption('force') && file_exists($dest)) {
            $this->io->warning("Archive $dest already exists.");
            if (!$this->io->confirm('Do you want to rebuild it?', false)) {
                return Command::FAILURE;
            }
        }

        return $this->build($dest);
    }

    private function build(string $dest): int
    {
        $this->io->title("Releasing plugin {$this->plugin_name}...");

        $plugin_dir = $this->getPluginDirectory();

        // Prepare working directory
        $src_dir = $this->dist_dir . '/src';
        $src_subdir = $src_dir . '/' . $this->plugin_name;
        $fs = new Filesystem();

        if (is_dir($src_dir)) {
            $fs->remove($src_dir);
        }
        if (!mkdir($src_subdir, 0o777, true)) {
            $this->io->error(sprintf('Unable to create the `%s` directory.', $src_subdir));
            return Command::FAILURE;
        }

        // Export current index using checkout-index
        $this->io->text("Exporting current index...");
        $process = new Process(
            ['git', 'checkout-index', '--all', '--force', '--prefix=' . $src_subdir . '/'],
            $plugin_dir
        );
        $process->setTimeout(600);
        $process->mustRun(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        // Composer
        if (file_exists($src_subdir . '/composer.json')) {
            $this->io->section("Installing composer dependencies...");
            $c_cmd = ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'];

            $proc = new Process($c_cmd, $src_subdir);
            $proc->setTimeout(300);
            $proc->mustRun(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            // Cleanup vendors
            $this->cleanupVendor($src_subdir . '/vendor');

            // Dump autoload
            $this->io->newLine();
            $this->io->section("Dumping composer autoload...");
            $proc = new Process(['composer', 'dump-autoload', '-o', '--no-dev'], $src_subdir);
            $proc->mustRun(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            // Remove composer.lock
            if (file_exists($src_subdir . '/composer.lock')) {
                unlink($src_subdir . '/composer.lock');
            }
            $this->io->writeln("<info>Composer dependencies installed.</info>");
        }

        // NPM
        if (file_exists($src_subdir . '/package.json')) {
            $this->io->section("Installing npm dependencies...");
            $n_cmd = ['npm', 'install'];
            $proc = new Process($n_cmd, $src_subdir);
            $proc->setTimeout(600);
            $proc->mustRun(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            // Remove node_modules (assume npm install triggers postinstall build)
            $fs->remove($src_subdir . '/node_modules');

            // Remove package-lock.json
            if (file_exists($src_subdir . '/package-lock.json')) {
                unlink($src_subdir . '/package-lock.json');
            }
            $this->io->writeln("<info>Npm dependencies installed.</info>");
        }

        // Compile locales
        if (is_dir($src_subdir . '/locales')) {
            $input = new ArrayInput([
                'command'  => 'tools:locales:compile',
                '--directory' => $src_subdir,
            ]);
            $input->setInteractive(false);
            $this->getApplication()->doRun($input, $this->output);
            $this->io->writeln("<info>Locales compiled.</info>");
        }

        // Remove banned files before archiving
        $this->io->section("Cleaning up banned files...");
        $banned = self::BANNED_FILES;
        $ignore_release_file = $src_subdir . '/.ignore-release';
        if (file_exists($ignore_release_file)) {
            $lines = file($ignore_release_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $banned = array_merge($banned, $lines);
        }

        $finder = new Finder();
        $finder
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($src_subdir);

        foreach (iterator_to_array($finder->getIterator()) as $file) {
            /* @var \SplFileInfo $file */
            $relative_path = $file->getRelativePathname();
            foreach ($banned as $ban) {
                if (
                    \file_exists($file->getRealPath())
                    && (
                        fnmatch($ban, $relative_path)
                        || fnmatch($ban, $file->getFilename())
                        || preg_match('#^' . preg_quote($ban, '#') . '#', $relative_path)
                        || ($file->isDir() && rtrim($ban, '/') === $relative_path)
                    )
                ) {
                    $fs->remove($file->getPathname());
                    $this->io->writeln(" Removed: $relative_path");
                    break;
                }
            }
        }

        // Create archive
        $this->io->section("Generating the archive");
        $this->io->writeln("<info>Target: $dest</info>", OutputInterface::VERBOSITY_VERBOSE);

        $tar_cmd = [
            'tar',
            '--format=ustar',
            '--auto-compress',
            '-cf',
            $dest,
            $this->plugin_name,
        ];
        $proc = new Process($tar_cmd, $src_dir);
        $proc->mustRun(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        // Cleanup src
        $fs->remove($src_dir);

        $this->io->success("Archive built: $dest");
        return Command::SUCCESS;
    }

    private function cleanupVendor(string $vendor_dir): void
    {
        if (!is_dir($vendor_dir)) {
            return;
        }

        $fs = new Filesystem();
        $finder = new Finder();

        // Remove git directories
        $finder->directories()->in($vendor_dir)->name('.git*')->ignoreVCS(false);
        foreach (iterator_to_array($finder->getIterator()) as $dir) {
            $fs->remove($dir->getPathname());
        }

        // Remove test directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('test')->name('tests');
        foreach (iterator_to_array($finder->getIterator()) as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove example directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('example')->name('examples');
        foreach (iterator_to_array($finder->getIterator()) as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove doc directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('doc')->name('docs');
        foreach (iterator_to_array($finder->getIterator()) as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove composer files in vendor subdirectories
        $finder = new Finder();
        $finder->files()->in($vendor_dir)->name('composer.*')->depth('> 0');
        foreach (iterator_to_array($finder->getIterator()) as $file) {
            $fs->remove($file->getPathname());
        }
    }
}
