<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use CallbackFilterIterator;
use SplFileInfo;

class ExtractLocalesCommand extends AbstractPluginCommand
{

    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:plugin:extract_locales');
        $this->setDescription('Extract strings from the project to generate POT file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script_dir = dirname(__DIR__, 2); // glpi-11-2/tools/plugin

        $root_dir = dirname($script_dir, 2); // glpi-11-2
        $working_dir = $this->getPluginDirectory();

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
            mkdir($temp_twig_dir . '/templates', 0777, true);

            // Using internal command to compile templates
            $compile_cmd = $this->getApplication()->find('tools:plugin:compile_twig_templates');
            $compile_input = new \Symfony\Component\Console\Input\ArrayInput([
                 'templates-directory' => $working_dir . '/templates',
                 'output-directory'    => $temp_twig_dir . '/templates',
                 '--quiet' => true
            ]);
            $compile_cmd->run($compile_input, $this->output);

            $twig_files = $this->getFiles($temp_twig_dir, 'twig');
            if (count($twig_files) > 0) {
                 $files_list = implode(' ', $twig_files);
                 $cmd = sprintf(
                     'cd %s && xgettext %s -o %s -L PHP --add-comments=TRANS --add-location=file --from-code=UTF-8 --force-po --join-existing ' .
                     '--keyword=_n:%s --keyword=__:%s --keyword=_x:%s --keyword=_nx:%s',
                     escapeshellarg($temp_twig_dir),
                     $files_list,
                     escapeshellarg($potfile),
                     $args['F_ARGS_N'],
                     $args['F_ARGS__'],
                     $args['F_ARGS_X'],
                     $args['F_ARGS_NX']
                 );
                 system($cmd);
            }

            // Cleanup
             system('rm -rf ' . escapeshellarg($temp_twig_dir));
        }

         // Append locales from PHP
        $this->io->section('Processing PHP files...');
        $php_files = $this->getFiles($working_dir, 'php', $exclude_regex);
        if (count($php_files) > 0) {
              // Write files list to usage in xgettext via -f
              $list_file = tempnam(sys_get_temp_dir(), 'phpfiles');
              file_put_contents($list_file, implode("\n", $php_files));

              $cmd = sprintf(
                 'cd %s && xgettext --files-from=%s -o %s -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing ' .
                 '--keyword=_n:%s --keyword=__s:%s --keyword=__:%s --keyword=_x:%s --keyword=_sx:%s --keyword=_nx:%s --keyword=_sn:%s',
                 escapeshellarg($working_dir),
                 escapeshellarg($list_file),
                 escapeshellarg($potfile),
                 $args['F_ARGS_N'],
                 $args['F_ARGS__S'],
                 $args['F_ARGS__'],
                 $args['F_ARGS_X'],
                 $args['F_ARGS_SX'],
                 $args['F_ARGS_NX'],
                 $args['F_ARGS_SN']
             );
             system($cmd);
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

             $cmd = sprintf(
                 'cd %s && xgettext --files-from=%s -o %s -L JavaScript --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing ' .
                 '--keyword=_n:%s --keyword=__:%s --keyword=_x:%s --keyword=_nx:%s ' .
                 '--keyword=i18n._n:%s --keyword=i18n.__:%s --keyword=i18n._p:%s --keyword=i18n.ngettext:%s --keyword=i18n.gettext:%s --keyword=i18n.pgettext:%s',
                 escapeshellarg($working_dir),
                  escapeshellarg($list_file),
                 escapeshellarg($potfile),
                 $args['F_ARGS_N'],
                 $args['F_ARGS__'],
                 $args['F_ARGS_X'],
                 $args['F_ARGS_NX'],
                 $args['F_ARGS_N'],
                 $args['F_ARGS__'],
                 $args['F_ARGS_X'],
                 $args['F_ARGS_N'],
                 $args['F_ARGS__'],
                 $args['F_ARGS_X']
             );
             system($cmd);
             unlink($list_file);
        }

        // Append locales from Vue
        $this->io->section('Processing Vue files...');
        // TODO: Port Vue extraction if needed (requires npm run vue:gettext:extract)
        // Original script ran npm install && npm run vue:gettext:extract in vendor tools.
        // For now, we might want to skip or implement depending on if we have Vue files.
        // Assuming we are in core or plugin with package.json
        if (file_exists($working_dir . '/package.json')) {
             // Logic for vue extraction would go here
             $this->io->warning('Vue extraction not yet fully implemented in this command port.');
        }

        // Update main language
        $this->io->section('Updating en_GB.po...');
        $cmd = sprintf(
            'LANG=C msginit --no-translator -i %s -l en_GB -o %s',
            escapeshellarg($potfile),
            escapeshellarg($working_dir . '/locales/en_GB.po')
        );
        system($cmd);

        $this->io->success('Locales extracted successfully.');
        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getFiles($directory, $extension, $exclude_regex = null): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $path = $file->getPathname();
                $relPath = './' . ltrim(substr($path, strlen($directory)), '/');
                if ($exclude_regex && preg_match($exclude_regex, $relPath)) {
                    continue;
                }
                $files[] = $relPath;
            }
        }
        return $files;
    }
}
