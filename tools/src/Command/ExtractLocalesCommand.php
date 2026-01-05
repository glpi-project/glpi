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

ini_set('memory_limit', -1); // This is required due to high memory usage when extracting for core.

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ExtractLocalesCommand extends AbstractCommand
{
    protected const ALLOW_PLUGIN_OPTION = true;

    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:extract_locales');
        $this->setDescription('Extract strings from the project to generate POT file.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->isPluginCommand()) {
            $working_dir = $this->getPluginDirectory();
        } else {
            $working_dir = dirname(__DIR__, 3); // glpi
        }

        // Check availability of xgettext
        if (trim(shell_exec('which xgettext')) === '') {
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
        if (file_exists($working_dir . '/setup.php')) {
            // setup.php found: it's a plugin.
            $content = file_get_contents($working_dir . '/setup.php');
            if (preg_match('/PLUGIN_(.*)_VERSION/', $content, $matches)) {
                $name = $matches[1];
            } else {
                $name = 'UNKNOWN';
            }

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
            $exclude_regex = '/^\.\/(\..*|(config|files|lib|marketplace|node_modules|plugins|phpunit|public|tests|tools|vendor)\/).*/';
        }

        $potfile = $working_dir . '/locales/' . strtolower($name) . '.pot';

        if (!is_dir($working_dir . '/locales')) {
            mkdir($working_dir . '/locales');
        }

        // Clean existing POT file
        if (file_exists($potfile)) {
            unlink($potfile);
        }
        touch($potfile);

        // Append locales from Twig templates
        if (is_dir($working_dir . '/templates')) {
            $this->io->section('Processing Twig templates...');
            $temp_twig_dir = sys_get_temp_dir() . '/glpi-locales-' . uniqid();
            mkdir($temp_twig_dir . '/templates', 0o777, true);

            $this->io->writeln("<question>Compiling twig templates into php files</question>");
            // Using internal command to compile templates
            $compile_cmd = $this->getApplication()->find('tools:compile_twig_templates');
            $options = [
                'templates-directory' => $working_dir . '/templates',
                'output-directory'    => $temp_twig_dir . '/templates',
                '--quiet' => true,
            ];
            if ($this->isPluginCommand()) {
                $options['--plugin'] = $this->getPluginName();
            }

            $compile_input = new ArrayInput($options);
            $compile_cmd->run($compile_input, $this->output);

            $this->io->writeln("<question>Extracting translations from files</question>");
            $twig_files = $this->getFiles($temp_twig_dir, 'twig');
            if (count($twig_files) > 0) {
                $command = array_merge(
                    [
                        'xgettext',
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
            $list_file = tempnam(sys_get_temp_dir(), 'phpfiles');
            file_put_contents($list_file, implode("\n", $php_files));

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
            $list_file = tempnam(sys_get_temp_dir(), 'jsfiles');
            file_put_contents($list_file, implode("\n", $js_files));

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
            $this->runCommand(['npm', 'run', 'vue:gettext:extract'], $working_dir, false);

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
                $this->io->warning('No vue locales generated to merge.');
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
            null,
            true,
             // Environment variables for this command (LANG=C)
            ['LANG' => 'C']
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
            /** @var \SplFileInfo $file */
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

    private function runCommand(array $command, ?string $cwd = null, bool $must_succeed = true, array $env = []): Process
    {
        $process = new Process($command, $cwd, $env);
        $process->setTimeout(null);

        $callback = function ($type, $buffer) {
            $this->output->write($buffer);
        };

        if ($must_succeed) {
            $process->mustRun($callback);
        } else {
            $process->run($callback);
        }

        return $process;
    }
}
