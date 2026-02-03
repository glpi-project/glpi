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

        $this->addOption('ref', 'r', InputOption::VALUE_REQUIRED, 'Git ref to build', 'HEAD');
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

        $ref = $input->getOption('ref');
        return $this->build($ref, $dest);
    }

    private function build(string $ref, string $dest): int
    {
        $this->io->title("Releasing plugin {$this->plugin_name}@{$ref}...");

        $plugin_dir = $this->getPluginDirectory();

        // git ls-tree
        $process = new Process(['git', 'ls-tree', '-r', $ref, '--name-only'], $plugin_dir);
        $process->mustRun();
        $files = explode("\n", trim($process->getOutput()));

        // Filter banned
        $banned = self::BANNED_FILES;
        $ignore_release_file = $plugin_dir . '/.ignore-release';
        if (file_exists($ignore_release_file)) {
            $lines = file($ignore_release_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $banned = array_merge($banned, $lines);
        }

        $valid_files = [];
        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            $excluded = false;
            foreach ($banned as $ban) {
                if (fnmatch($ban, $file) || fnmatch($ban, basename($file)) || preg_match('#^' . preg_quote($ban, '#') . '#', $file)) {
                    $excluded = true;
                    break;
                }
            }
            if (!$excluded) {
                $valid_files[] = $file;
            }
        }

        // Git archive
        $temp_tar = $this->dist_dir . '/temp.tar';
        $cmd = ['git', 'archive', '--prefix=' . $this->plugin_name . '/', '--output=' . $temp_tar, $ref];
        foreach ($valid_files as $f) {
            $cmd[] = $f;
        }

        $this->io->text("Archiving GIT ref {$ref}...");

        $process = new Process($cmd, $plugin_dir);
        $process->setTimeout(600);
        $process->mustRun(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        // Now we need to prepare (extract, add vendors, re-compress)
        $src_dir = $this->dist_dir . '/src';
        $src_subdir = $src_dir . '/' . $this->plugin_name;
        $fs = new Filesystem();
        if (is_dir($src_dir)) {
            $fs->remove($src_dir);
        }
        if (!mkdir($src_dir)) {
            $this->io->error(sprintf('Unable to create the `%s` directory.', $src_dir));
            return Command::FAILURE;
        }

        $untar = new Process(['tar', '-xf', $temp_tar, '-C', $src_dir]);
        $untar->mustRun(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (!unlink($temp_tar)) {
            $this->io->error(sprintf('Unable to delete the `%s` file.', $temp_tar));
            return Command::FAILURE;
        }

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

        // Compress to bz2
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
