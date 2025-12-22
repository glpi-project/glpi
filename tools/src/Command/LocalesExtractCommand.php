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

namespace Glpi\Tools\Command;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Twig\Cache\CacheInterface;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class LocalesExtractCommand extends AbstractCommand
{
    #[Override]
    protected function isPluginOptionAvailable(): bool
    {
        return true;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:locales:extract');
        $this->setDescription('Extract strings from the project to generate POT file.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', -1); // This is required due to high memory usage when extracting for core.

        if ($this->isPluginCommand()) {
            $working_dir = $this->getPluginDirectory();
        } else {
            $working_dir = dirname(__DIR__, 3); // glpi
        }

        $finder = new ExecutableFinder();
        if (!$finder->find('xgettext')) {
            $this->io->error('xgettext not found. Please install gettext.');
            return Command::FAILURE;
        }

        // Define translate function args
        $args = [
            'F_ARGS_N'  => '1,2',
            'F_ARGS__S' => '1',
            'F_ARGS__'  => '1',
            'F_ARGS_X'  => '1c,2',
            'F_ARGS_SX' => '1c,2',
            'F_ARGS_NX' => '1c,2,3',
            'F_ARGS_SN' => '1,2',
        ];

        // Compute POT filename
        if ($this->isPluginCommand()) {
            $name = $this->getPluginName();
            $exclude_regex = '/^\.\/(\..*|(libs?|node_modules|tests|vendor)\/).*/';

            // Only strings with domain specified are extracted
            $args['F_ARGS_N']  .= ',4t';
            $args['F_ARGS__S'] .= ',2t';
            $args['F_ARGS__']  .= ',2t';
            $args['F_ARGS_X']  .= ',3t';
            $args['F_ARGS_SX'] .= ',3t';
            $args['F_ARGS_NX'] .= ',5t';
            $args['F_ARGS_SN'] .= ',4t';
        } else {
            // core
            $name = 'GLPI';
            $exclude_regex = '/^\.\/(\..*|(config|files|lib|marketplace|node_modules|plugins|public|tests|tools|vendor)\/).*/';
        }

        $potfile = $working_dir . '/locales/' . strtolower($name) . '.pot';

        if (!is_dir($working_dir . '/locales') && !mkdir($working_dir . '/locales')) {
            $this->io->error(sprintf('Unable to create the `%s/locales` directory.', $working_dir));
            return Command::FAILURE;
        }

        // Clean existing POT file
        if (file_exists($potfile) && (!unlink($potfile))) {
            $this->io->error(sprintf('Unable to override the `%s` file.', $potfile));
            return Command::FAILURE;
        }

        if (!touch($potfile)) {
            $this->io->error(sprintf('Unable to create the `%s` file.', $potfile));
            return Command::FAILURE;
        }

        // Append locales from Twig templates
        if (is_dir($working_dir . '/templates')) {
            $this->io->section('Processing Twig templates...');
            $temp_twig_dir = sys_get_temp_dir() . '/glpi-locales-' . uniqid();
            if (!mkdir($temp_twig_dir . '/templates', 0o777, true)) {
                $this->io->error(sprintf('Unable to create the `%s/templates` dir.', $temp_twig_dir));
                return Command::FAILURE;
            }

            $this->io->writeln("<info>Compiling twig templates into php files...</info>");
            $root_path = $this->isPluginCommand() ? $this->getPluginDirectory() : dirname($working_dir . '/templates');
            $this->compileTwigTemplates(
                $working_dir . '/templates',
                $temp_twig_dir . '/templates',
                $root_path
            );

            $this->io->writeln("<info>Extracting translations from files</info>");
            $twig_files = $this->getFiles($temp_twig_dir, 'twig');
            if (count($twig_files) > 0) {
                // Write files list to usage in xgettext via -f
                if (
                    ($list_file = tempnam(sys_get_temp_dir(), 'twigfiles')) === false
                    || file_put_contents($list_file, implode("\n", $twig_files)) === false
                ) {
                    $this->io->error('Unable to create the Twig files list file.');
                    return Command::FAILURE;
                }
                $command = array_merge(
                    [
                        'xgettext',
                        '--files-from=' . $list_file,
                        '-o', $potfile,
                        '-L', 'PHP',
                        '--add-comments=TRANS',
                        '--add-location=file',
                        '--from-code=UTF-8',
                        '--force-po',
                        '--join-existing',
                        '--keyword=_n:' . $args['F_ARGS_N'],
                        '--keyword=__:' . $args['F_ARGS__'],
                        '--keyword=_x:' . $args['F_ARGS_X'],
                        '--keyword=_nx:' . $args['F_ARGS_NX'],
                    ],
                    $twig_files
                );
                $this->runCommand($command, $temp_twig_dir);
            }

            // Cleanup
            $this->runCommand(['rm', '-rf', $temp_twig_dir]);
        }

        // Append locales from PHP
        $this->io->section('Processing PHP files...');
        $php_files = $this->getFiles($working_dir, 'php', $exclude_regex);
        if (count($php_files) > 0) {
            // Write files list to usage in xgettext via -f
            if (
                ($list_file = tempnam(sys_get_temp_dir(), 'phpfiles')) === false
                || file_put_contents($list_file, implode("\n", $php_files)) === false
            ) {
                $this->io->error('Unable to create the PHP files list file.');
                return Command::FAILURE;
            }

            $this->runCommand(
                [
                    'xgettext',
                    '--files-from=' . $list_file,
                    '-o', $potfile,
                    '-L', 'PHP',
                    '--add-comments=TRANS',
                    '--from-code=UTF-8',
                    '--force-po',
                    '--join-existing',
                    '--keyword=_n:' . $args['F_ARGS_N'],
                    '--keyword=__s:' . $args['F_ARGS__S'],
                    '--keyword=__:' . $args['F_ARGS__'],
                    '--keyword=_x:' . $args['F_ARGS_X'],
                    '--keyword=_sx:' . $args['F_ARGS_SX'],
                    '--keyword=_nx:' . $args['F_ARGS_NX'],
                    '--keyword=_sn:' . $args['F_ARGS_SN'],
                ],
                $working_dir
            );
            unlink($list_file);
        }

        // Append locales from JS
        $this->io->section('Processing JS files...');
        $js_files = $this->getFiles($working_dir, 'js', $exclude_regex);
        // Exclude min.js
        $js_files = array_filter($js_files, fn($f) => !str_ends_with($f, '.min.js'));

        if (count($js_files) > 0) {
            if (
                ($list_file = tempnam(sys_get_temp_dir(), 'jsfiles')) === false
                || file_put_contents($list_file, implode("\n", $js_files)) === false
            ) {
                $this->io->error('Unable to create the JS files list file.');
                return Command::FAILURE;
            }

            $this->runCommand(
                [
                    'xgettext',
                    '--files-from=' . $list_file,
                    '-o', $potfile,
                    '-L', 'JavaScript',
                    '--add-comments=TRANS',
                    '--from-code=UTF-8',
                    '--force-po',
                    '--join-existing',
                    '--keyword=_n:' . $args['F_ARGS_N'],
                    '--keyword=__:' . $args['F_ARGS__'],
                    '--keyword=_x:' . $args['F_ARGS_X'],
                    '--keyword=_nx:' . $args['F_ARGS_NX'],
                    '--keyword=i18n._n:' . $args['F_ARGS_N'],
                    '--keyword=i18n.__:' . $args['F_ARGS__'],
                    '--keyword=i18n._p:' . $args['F_ARGS_X'],
                    '--keyword=i18n.ngettext:' . $args['F_ARGS_N'],
                    '--keyword=i18n.gettext:' . $args['F_ARGS__'],
                    '--keyword=i18n.pgettext:' . $args['F_ARGS_X'],
                ],
                $working_dir
            );
            unlink($list_file);
        }

        // Append locales from Vue
        $this->io->section('Processing Vue files...');
        $vue_files = $this->getFiles($working_dir, 'vue', $exclude_regex);
        if (count($vue_files) > 0) {
            // Run extraction using local npm script
            $this->runCommand(['npm', 'run', 'vue:gettext:extract'], $working_dir);

            $vue_pot = $working_dir . '/locales/vue.pot';
            if (file_exists($vue_pot)) {
                $this->io->writeln("<info>Merge vue locales.</info>");
                // merge vue pot with the existing global pot file
                $this->runCommand([
                    'xgettext',
                    '-o', $potfile,
                    '--join-existing',
                    $vue_pot,
                ]);
                unlink($vue_pot);
            } else {
                $this->io->error('No vue locales generated to merge.');
                return Command::FAILURE;
            }
        }

        // Update main language
        $this->io->section('Updating en_GB.po...');
        $this->runCommand(
            [
                'msginit',
                '--no-translator',
                '-i', $potfile,
                '-l', 'en_GB',
                '-o', $working_dir . '/locales/en_GB.po',
            ],
            // Environment variables for this command (LANG=C)
            env: ['LANG' => 'C']
        );

        $this->io->success('Locales extracted successfully.');
        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getFiles($directory, $extension, $exclude_regex = null): array
    {
        $dir_iterator = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($dir_iterator);

        $filter = new \CallbackFilterIterator($iterator, function ($file) use ($directory, $extension, $exclude_regex) {
            /** @var SplFileInfo $file */
            if (!$file->isFile() || $file->getExtension() !== $extension) {
                return false;
            }

            if ($exclude_regex) {
                $path = $file->getPathname();
                $rel_path = './' . ltrim(substr($path, strlen($directory)), '/');
                if (preg_match($exclude_regex, $rel_path)) {
                    return false;
                }
            }

            return true;
        });
        // Count total matching files first for progress bar
        $total_files = iterator_count($filter);
        $filter->rewind(); // Reset iterator

        $progress_bar = $this->io->createProgressBar($total_files);
        $files = [];
        foreach ($filter as $file) {
            $path = $file->getPathname();
            $rel_path = './' . ltrim(substr($path, strlen($directory)), '/');
            $files[] = $rel_path;
            $progress_bar->advance();
        }
        $progress_bar->finish();
        $this->io->newLine(2); // Clean line break after progress bar

        return $files;
    }

    private function runCommand(array $command, ?string $cwd = null, array $env = []): Process
    {
        $process = new Process($command, $cwd, $env);
        $process->setTimeout(null);

        $callback = function ($type, $buffer) {
            $this->output->write($buffer);
        };

        $process->mustRun($callback);

        return $process;
    }

    /**
     * Compile Twig templates into PHP files for locale extraction.
     */
    private function compileTwigTemplates(string $templates_dir, string $output_dir, string $root_path): void
    {
        $loader = new FilesystemLoader($templates_dir, $root_path);
        $twig = $this->getMockedTwigEnvironment($loader);
        $twig->setCache($this->getTwigCacheHandler($output_dir));

        $files = $this->getTwigTemplateFiles($templates_dir);

        $progress_bar = $this->io->createProgressBar(count($files));
        foreach ($files as $file) {
            $twig->load($file);
            $progress_bar->advance();
        }
        $progress_bar->finish();

        $this->io->newLine(2);
    }

    /**
     * Return template files from a directory.
     *
     * @return string[]
     */
    private function getTwigTemplateFiles(string $directory): array
    {
        $directory = realpath($directory);

        if (!is_dir($directory) || !is_readable($directory)) {
            throw new \RuntimeException(
                sprintf('Unable to read directory "%s"', $directory)
            );
        }

        $dir_iterator = new RecursiveDirectoryIterator($directory);

        $filter_iterator = new class ($dir_iterator) extends RecursiveFilterIterator {
            public function accept(): bool
            {
                /** @var SplFileInfo $this */
                if ($this->isFile() && !preg_match('/^twig$/', $this->getExtension())) {
                    return false;
                }
                return true;
            }
        };

        $recursive_iterator = new RecursiveIteratorIterator(
            $filter_iterator,
            RecursiveIteratorIterator::SELF_FIRST
        );

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($recursive_iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $files[] = preg_replace(
                '/^' . preg_quote($directory . DIRECTORY_SEPARATOR, '/') . '/',
                '',
                $file->getRealPath()
            );
        }

        return $files;
    }

    /**
     * Return a mocked Twig environment.
     * This mocked environment will prevent exceptions to be thrown when custom
     * functions, filters or tests are used in templates.
     */
    private function getMockedTwigEnvironment(LoaderInterface $loader): Environment
    {
        return new class ($loader) extends Environment {
            public function getFunction(string $name): ?TwigFunction
            {
                if (in_array($name, ['__', '_n', '_x', '_nx'], true)) {
                    // Return a function that has its own name as callback
                    // for translation functions, so Twig will generate code following this pattern:
                    // $name($parameter, ...)`, e.g. `__('str')` or `_n('str', 'strs', 5)`.
                    return new TwigFunction($name, $name);
                }
                return parent::getFunction($name) ?? new TwigFunction($name, function () {});
            }

            public function getFilter(string $name): ?TwigFilter
            {
                return parent::getFilter($name) ?? new TwigFilter($name, function () {});
            }

            public function getTest(string $name): ?TwigTest
            {
                if (in_array($name, ['divisible', 'same'])) {
                    // `same as` and `divisible by` will be search in 2 times.
                    // First check will be done on first word, should return `null` to
                    // trigger second search that will be done on full name.
                    return null;
                }
                return parent::getTest($name) ?? new TwigTest($name, function () {});
            }
        };
    }

    /**
     * Return a custom Twig cache handler.
     * This handler is useful to be able to preserve filenames of compiled files.
     */
    private function getTwigCacheHandler(string $directory): CacheInterface
    {
        return new class ($directory) extends FilesystemCache {
            private string $directory;

            public function __construct(string $directory, int $options = 0)
            {
                $this->directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                parent::__construct($directory, $options);
            }

            public function generateKey(string $name, string $className): string
            {
                return $this->directory . $name;
            }
        };
    }
}
