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
 * @link      https://github.com/glpi-project/tools
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
use RecursiveIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class LicenceHeadersCheckCommand extends AbstractCommand
{
    /**
     * Header lines.
     *
     * @var array|null
     */
    private ?array $header_lines = null;

    #[Override]
    protected function isPluginOptionAvailable(): bool
    {
        return true;
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->setName('tools:licence_headers_check');
        $this->setDescription('Check licence header in code source files.');

        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Directory to parse (optional)',
        );

        $this->addOption(
            'header-file',
            null,
            InputOption::VALUE_OPTIONAL,
            'Header file to use (optional)',
        );

        $this->addOption(
            'fix',
            'f',
            InputOption::VALUE_NONE,
            'Fix missing and outdated headers'
        );

        $this->addOption(
            'discard-extra-tags',
            null,
            InputOption::VALUE_NONE,
            'Discard extra tags found in headers'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project_dir = dirname(__DIR__, 3); // Root of GLPI

        if ($this->isPluginCommand()) {
            $project_dir = $this->getPluginDirectory();
        }

        /** @var string|null $header_file_path */
        $header_file_path = $this->input->getOption('header-file');
        if (!$header_file_path) {
            $path = implode(DIRECTORY_SEPARATOR, [$project_dir, '.licence-header']);
            $legacy_path = implode(DIRECTORY_SEPARATOR, [$project_dir, 'tools', 'HEADER']);
            if (file_exists($path)) {
                $header_file_path = realpath($path);
            } elseif (file_exists($legacy_path)) {
                $header_file_path = realpath($legacy_path);
            }
        }

        if (!$header_file_path) {
            throw new \RuntimeException('No header path defined.');
        }

        if ($this->io->isVerbose()) {
            $this->io->info(sprintf('HEADER path: %s', $header_file_path));
        }

        $target_dir = $input->getOption('directory') ?? $project_dir;
        $files = $this->getFilesToParse($target_dir);

        if ($this->io->isVerbose()) {
            $this->io->info(sprintf('%s files to process in %s.', count($files), $target_dir));
        }

        $missing_found   = 0;
        $missing_errors  = 0;
        $outdated_found  = 0;
        $outdated_errors = 0;

        /** @var string $filename */
        foreach ($files as $filename) {
            if ($this->io->isVerbose()) {
                $this->io->text('<comment>' . sprintf('Processing "%s".', $filename) . '</comment>');
            }

            if (($file_lines = file($filename)) === false) {
                throw new \Exception(sprintf('Unable to read file "%s".', $filename));
            }

            $header_start_pattern   = null;
            $header_end_pattern     = null;
            $header_content_pattern = null;

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if ($extension === '') {
                // No extension, file is probably a binary.
                // Try to compute extension from shebang.
                $first_line = $file_lines[0];
                if (preg_match('/^#!/', $first_line)) {
                    $shebang_matches = [];
                    if (
                        // `#!/usr/bin/env php [options]` format
                        preg_match('/^#!\/usr\/bin\/env\s+(?<binary>[^\s]+)(\s+.*)?$/', $first_line, $shebang_matches)
                        // `#!/bin/bash [options]` format
                        || preg_match('/^#!(.{0}|\/([^\/]+\/)*(?<binary>[^\/\s]+))(\s+.*)?$/', $first_line, $shebang_matches)
                    ) {
                        $binary = $shebang_matches['binary'];
                        switch ($shebang_matches['binary']) {
                            case 'bash':
                                $extension = 'sh';
                                break;
                            case 'perl':
                                $extension = 'pl';
                                break;
                            case 'php':
                            default:
                                $extension = $binary;
                                break;
                        }
                    }
                }
            }
            switch ($extension) {
                case 'pl':
                case 'sh':
                case 'yaml':
                case 'yml':
                    $header_line_prefix     = '# ';
                    $header_prepend_line    = "#\n";
                    $header_append_line     = "#\n";
                    $header_start_pattern   = '/^#[^!]/'; // Any commented line except shebang (#!)
                    $header_content_pattern = '/^#/';
                    break;
                case 'sql':
                    $header_line_prefix     = '-- ';
                    $header_prepend_line    = "--\n";
                    $header_append_line     = "--\n";
                    $header_content_pattern = '/^(--|#)/'; // older headers were prefixed by "#"
                    break;
                case 'css':
                case 'scss':
                    $header_line_prefix     = ' * ';
                    $header_prepend_line    = "/*!\n";
                    $header_append_line     = " */\n";
                    $header_start_pattern   = '/^\/\*(\!|\*)?$/'; // older headers were starting by "/**" or "/*!"
                    $header_end_pattern     = '/\*\//';
                    break;
                case 'twig':
                    $header_line_prefix     = ' # ';
                    $header_prepend_line    = "{#\n";
                    $header_append_line     = " #}\n";
                    $header_start_pattern   = '/^\{#$/';
                    $header_end_pattern     = '/#}/';
                    break;
                default:
                    $header_line_prefix     = ' * ';
                    $header_prepend_line    = "/**\n";
                    $header_append_line     = " */\n";
                    $header_start_pattern   = '/^\/\*(\!|\*)?$/'; // accept "/*", "/**" and "/*!"
                    $header_end_pattern     = '/\*\//';
                    break;
            }

            if ($header_start_pattern === null) {
                // If there is no specific "start pattern", then first regular comment line is consider are header start.
                $header_start_pattern = $header_content_pattern;
            }

            $header_found         = false;
            $header_missing       = false;
            $is_header_line       = false;
            $is_last_header_line  = false;
            $pre_header_lines     = [];
            $current_header_lines = [];
            $post_header_lines    = [];

            foreach ($file_lines as $line) {
                if (!$header_found && !$header_missing) {
                    if (preg_match($header_start_pattern, $line)) {
                        // Line matches header opening line
                        $header_found = true;
                        $is_header_line = true;
                    } elseif (!$this->shouldLineBeLocatedBeforeHeader($line)) {
                        // Line does not match allowed lines before header,
                        // consider that header is missing.
                        $header_missing = true;
                    }
                } elseif ($is_last_header_line) {
                    // Previous line was "last header line", so current line is the first line after licence header
                    $is_last_header_line = false;
                    $is_header_line = false;
                } elseif ($is_header_line && $header_end_pattern !== null && preg_match($header_end_pattern, $line)) {
                    // Line matches header end pattern
                    $is_last_header_line = true;
                } elseif ($is_header_line && $header_content_pattern !== null && !preg_match($header_content_pattern, $line)) {
                    // Line does not match header, so it is the first line after licence header
                    $is_header_line = false;
                }

                if ($header_missing || ($header_found && !$is_header_line)) {
                    $post_header_lines[] = $line;
                } elseif ($is_header_line) {
                    $current_header_lines[] = $line;
                } else {
                    $pre_header_lines[] = $line;
                }
            }

            $preserved_tagged_data = $this->input->getOption('discard-extra-tags')
               ? []
               : $this->extractTaggedData($current_header_lines, $header_line_prefix);

            $updated_header_lines = $this->getLicenceHeaderLines(
                $header_file_path,
                $header_line_prefix,
                $header_prepend_line,
                $header_append_line,
                $preserved_tagged_data
            );

            $sliced_header_lines = array_slice($updated_header_lines, 1, -1);
            $header_outdated = $sliced_header_lines !== array_slice($current_header_lines, 1, -1);

            if (!$header_missing && !$header_outdated) {
                continue;
            }

            if ($header_missing) {
                $this->io->writeln(sprintf('<fg=red>Missing licence header in file "%s".</>', $filename));
                $missing_found++;
            } else {
                $this->io->writeln(sprintf('<fg=yellow>Licence header outdated in file "%s".</>', $filename));
                $outdated_found++;
            }

            if ($this->input->getOption('fix')) {
                $pre_header_lines  = $this->stripEmptyLines($pre_header_lines, false, true);
                $post_header_lines = $this->stripEmptyLines($post_header_lines, true, false);

                $file_contents = '';
                if ($pre_header_lines !== []) {
                    $file_contents .= implode('', $pre_header_lines) . "\n";
                }
                $file_contents .= implode('', $updated_header_lines);
                if ($post_header_lines !== []) {
                    $file_contents .= "\n" . implode('', $post_header_lines);
                }

                if (strlen($file_contents) !== file_put_contents($filename, $file_contents)) {
                    $this->io->error(sprintf('Unable to update licence header in file "%s".', $filename));
                    if ($header_missing) {
                        $missing_errors++;
                    } else {
                        $outdated_errors++;
                    }
                }
            }
        }

        if ($missing_found === 0 && $outdated_found === 0) {
            $this->io->success('Files headers are valid.');
            return 0; // Success
        }

        $build_msg = function (int $missing_count, int $outdated_count): string {
            $messages = [];
            if ($missing_count > 0) {
                $messages[] = sprintf(
                    '%d file%s without header',
                    $missing_count,
                    $missing_count > 1 ? 's' : ''
                );
            }
            if ($outdated_count > 0) {
                $messages[] = sprintf(
                    '%d file%s with outdated header',
                    $outdated_count,
                    $outdated_count > 1 ? 's' : ''
                );
            }
            return implode(' and ', $messages);
        };

        if (!$this->input->getOption('fix')) {
            $msg = sprintf(
                'Found %s. Use --fix option to fix these files.',
                $build_msg($missing_found, $outdated_found)
            );
            $this->io->error($msg);
            return self::FAILURE;
        }

        $msg = sprintf(
            'Fixed %s.',
            $build_msg(
                $missing_found - $missing_errors,
                $outdated_found - $outdated_errors
            )
        );
        $this->io->success($msg);

        if ($missing_errors > 0 || $outdated_errors > 0) {
            $this->io->error(sprintf('%s file(s) cannot be updated.', $missing_errors + $outdated_errors));
            return self::FAILURE;
        }

        return 0; // Success
    }

    /**
     * Get licence header lines.
     *
     * @param string|null $header_file_path
     * @param string $line_prefix
     * @param string $prepend_line
     * @param string $append_line
     * @param array  $extra_tagged_data
     *
     * @return array
     */
    private function getLicenceHeaderLines(
        string $header_file_path,
        string $line_prefix,
        string $prepend_line,
        string $append_line,
        array $extra_tagged_data = []
    ): array {
        if ($this->header_lines === null) {
            if (($lines = file($header_file_path)) === false) {
                throw new \Exception('Unable to read header file.');
            }
            $this->header_lines = $lines;
        }

        $lines = [];
        $lines[] = $prepend_line;
        foreach ($this->header_lines as $line) {
            $lines[] = (preg_match('/^\s+$/', $line) ? rtrim($line_prefix) : $line_prefix) . $line;
        }
        $lines[] = $append_line;

        $lines = $this->appendTaggedData($lines, $extra_tagged_data, $line_prefix);

        return $this->stripEmptyLines($lines, true, true);
    }

    /**
     * Return files to parse.
     *
     * @param string $directory
     *
     * @return array
     */
    private function getFilesToParse(string $directory): array
    {
        $directory = realpath($directory);

        if (!is_dir($directory) || !is_readable($directory)) {
            throw new InvalidOptionException(
                sprintf('Unable to read directory "%s"', $directory)
            );
        }

        $dir_iterator = new RecursiveDirectoryIterator($directory);
        $exclusion_pattern = $this->getExclusionPattern($directory);

        $filter_iterator = new class ($dir_iterator, $exclusion_pattern) extends RecursiveFilterIterator {
            private $exclusion_pattern;

            public function __construct(RecursiveIterator $iterator, ?string $exclusion_pattern)
            {
                $this->exclusion_pattern = $exclusion_pattern;
                parent::__construct($iterator);
            }

            public function accept(): bool
            {
                $file = $this->current();
                if ($this->exclusion_pattern !== null && preg_match($this->exclusion_pattern, $file->getRealPath())) {
                    return false;
                }
                if ($file->isDir()) {
                    return true; // parse subdirectories
                }
                if (preg_match('/^(css|js|ts|php|pl|scss|sh|sql|twig|ya?ml)$/', $file->getExtension())) {
                    return true; // handled extensions
                }
                if (basename($file->getPath()) === 'bin') {
                    return true; // executable
                }
                return false;
            }

            public function getChildren(): ?RecursiveFilterIterator
            {
                /** @var RecursiveIterator */
                $inner = $this->getInnerIterator();
                return new self($inner->getChildren(), $this->exclusion_pattern);
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

            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Indicates if a line can/should be located before licence header.
     *
     * @param string $line
     *
     * @return bool
     */
    private function shouldLineBeLocatedBeforeHeader(string $line): bool
    {
        // PHP opening tag
        if (rtrim($line) === '<?php') {
            return true;
        }

        // Shebang
        if (preg_match('/^#!/', $line)) {
            return true;
        }

        // File generated by bootstap
        if (str_contains($line, '// webpackBootstrap')
            || rtrim($line) === 'var __webpack_exports__ = {};') {
            return true;
        }

        // Empty line
        if (trim($line) === '') {
            return true;
        }

        return false;
    }

    /**
     * Strip empty top/bottom lines from an array.
     *
     * @param array $lines
     * @param bool $strip_top_lines
     * @param bool $strip_bottom_lines
     *
     * @return array
     */
    private function stripEmptyLines(array $lines, bool $strip_top_lines, bool $strip_bottom_lines): array
    {
        // Remove empty lines from top of an array
        $strip_top_fct = function (array $values): array {
            $filtered_values = [];
            $found_not_empty = false;

            foreach ($values as $value) {
                if (!$found_not_empty && empty(trim($value))) {
                    continue;
                }
                $found_not_empty = true;
                $filtered_values[] = $value;
            }

            return $filtered_values;
        };

        if ($strip_top_lines) {
            $lines = $strip_top_fct($lines);
        }

        if ($strip_bottom_lines) {
            $lines = array_reverse($lines);
            $lines = $strip_top_fct($lines);
            $lines = array_reverse($lines);
        }

        return $lines;
    }

    /**
     * Extract tagged data from header lines.
     *
     * @param array $lines
     * @param string|null $line_prefix
     *
     * @return array
     */
    private function extractTaggedData(array $lines, ?string $line_prefix = null): array
    {

        $tagged_data = [];

        $tag_pattern = $this->getTagPattern($line_prefix);

        foreach ($lines as $line) {
            $tag = null;
            if (preg_match($tag_pattern, $line, $tag)) {
                $tag_name = $tag['name'];
                $tag_value = $tag['value'];

                if (!array_key_exists($tag_name, $tagged_data)) {
                    $tagged_data[$tag_name] = [];
                }
                $tagged_data[$tag_name][] = $tag_value;
            }
        }

        return $tagged_data;
    }

    /**
     * Append tagged data to header lines.
     *
     * @param array $lines
     * @param array $data_to_append
     * @param string|null $line_prefix
     *
     * @return array
     */
    private function appendTaggedData(array $lines, array $data_to_append, ?string $line_prefix = null): array
    {

        $existing_data = $this->extractTaggedData($lines, $line_prefix);

        if (count($existing_data) === 0) {
            $existing_tag_lines_nums = [];
            $append_line_num = count($lines); // There is no tag in given lines, append new tags to the end.
        } else {
            $data_to_append = array_merge_recursive($existing_data, $data_to_append);
            $data_to_append = array_map('array_unique', $data_to_append);
            ksort($data_to_append);

            $existing_tag_lines_nums = array_keys(preg_grep($this->getTagPattern($line_prefix), $lines));
            $append_line_num = $existing_tag_lines_nums[0];
        }

        // Deduplicate tagged data
        if (is_array($data_to_append)) {
            foreach ($data_to_append as $tag_name => $tag_values) {
                if (preg_match('/^copy(right|left)$/', $tag_name) !== 1) {
                    continue;
                }
                $data_to_append[$tag_name] = $this->unduplicateCopyTag($tag_values);
            }
        }

        // Drop existing tag lines and re-append merged tagged data entirely
        $result_lines = [];
        foreach ($lines as $num => $line) {
            if (!in_array($num, $existing_tag_lines_nums)) {
                $result_lines[] = $line; // Line is ot a tag line, keep it.
            }
            if ($num === $append_line_num) {
                // Append entire tag data
                $pad = max(array_map('strlen', array_keys($data_to_append)));
                foreach ($data_to_append as $tag_name => $tag_values) {
                    foreach ($tag_values as $tag_value) {
                        $result_lines[] = $line_prefix . sprintf('@%s %s', str_pad($tag_name, $pad), $tag_value) . "\n";
                    }
                }
            }
        }

        return $result_lines;
    }

    /**
     * Get regex pattern used to detect/extract tagged data.
     *
     * @param string $line_prefix
     *
     * @return string
     */
    private function getTagPattern(?string $line_prefix = null): string
    {
        return '/^'
           . ($line_prefix !== null ? '(?:' . preg_quote($line_prefix, '/') . ')?' : '') // may be prefixed by line prefix
           . '\s*' // may be prefixed by whitespace
           . '@(?<name>[a-z]+)' // @tagname
           . '\s+' // space between tag and value
           . '(?<value>.+)' // value
           . '$/i';
    }

    /**
     * Unduplicate copyright/copyleft tags values.
     *
     * @param array $values
     *
     * @return array
     */
    private function unduplicateCopyTag(array $values): array
    {
        $copy_dates_pattern = '/^'
           . '(?<before>.+\s+)?' // capture everything before dates
           . '(?<starting_date>\d{4})' // mandatory date (unique year or starting year)
           . '(-(?<ending_date>\d{4}))?' // optionnal ending date with `-` separator
           . '(?<after>\s+.+)?' // capture everything after dates
           . '$/';

        $preserved_values = [];

        foreach ($values as $value) {
            $dates_matches = [];
            if (preg_match($copy_dates_pattern, $value, $dates_matches) !== 1) {
                continue;
            }

            $before = trim($dates_matches['before'] ?? '');
            $before_pattern = $before !== ''
               ? '\s*' . preg_quote($before, '/') . '\s+'
               : '';
            $after = trim($dates_matches['after'] ?? '');
            $after_pattern = $after !== ''
               ? '\s+' . preg_quote($after, '/') . '\s*'
               : '';

            $similar_pattern = '/^'
               . $before_pattern
               . '(?<starting_date>\d{4})(-(?<ending_date>\d{4}))?'
               . $after_pattern
               . '$/';

            if (count(preg_grep($similar_pattern, $preserved_values)) > 0) {
                // similar value already computed
                continue;
            }

            $similar_values = preg_grep($similar_pattern, $values);

            if (count($similar_values) === 1) {
                // found only current value, no need to deduplicate
                $preserved_values[] = $value;
                continue;
            }

            // Compute min starting and max ending dates
            $starting_date = $dates_matches['starting_date'];
            $ending_date   = !empty($dates_matches['ending_date']) ? $dates_matches['ending_date'] : $starting_date;
            foreach ($similar_values as $similar_value) {
                $similar_dates_matches = [];
                preg_match($copy_dates_pattern, $similar_value, $similar_dates_matches);
                if ($similar_dates_matches['starting_date'] < $starting_date) {
                    $starting_date = $similar_dates_matches['starting_date'];
                } elseif ($similar_dates_matches['starting_date'] > $ending_date) {
                    $ending_date = $similar_dates_matches['starting_date'];
                }
                if (!empty($similar_dates_matches['ending_date']) && $similar_dates_matches['ending_date'] > $ending_date) {
                    $ending_date = $similar_dates_matches['ending_date'];
                }
            }
            $preserved_values[] = ($dates_matches['before'] ?? '')
               . $starting_date
               . ($ending_date !== $starting_date ? '-' . $ending_date : '')
               . ($dates_matches['after'] ?? '');
        }

        return $preserved_values;
    }

    /**
     * Get files exclusion pattern. All files matching this pattern will be excluded from checks.
     *
     * @param string $directory
     *
     * @return string
     */
    protected function getExclusionPattern(string $directory): ?string
    {
        $excluded_elements = [
            '(\.|.*\/\.).+', // Any hidden file/directory

            '(docker-)?compose(\.override)?\.ya?ml', // docker compose configuration files

            'node_modules', // npm imported libs
            'vendor', // composer imported libs

            'public\/lib', // libs packaged using webpack
        ];
        if (file_exists($directory . DIRECTORY_SEPARATOR . 'setup.php')
            && file_exists($directory . DIRECTORY_SEPARATOR . 'hook.php')) {
            // Directory is a plugin root directory
            $excluded_elements = array_merge(
                $excluded_elements,
                [
                    'lib',  // Manually included libs
                    'dist', // Plugin archives
                    'var',  // Lint cache
                ]
            );
        } elseif (file_exists($directory . DIRECTORY_SEPARATOR . 'composer.json')
                   && preg_match('/"name"\s*:\s*"glpi\/glpi"/', file_get_contents($directory . DIRECTORY_SEPARATOR . 'composer.json'))) {
            // Directory is GLPI root directory
            $excluded_elements = array_merge(
                $excluded_elements,
                [
                    'config',
                    'css\/lib',
                    'lib\/(?!(bundles|index\.php)).+', // Manually included libs, but do not exclude "bundles" subdir or "index.php"
                    'files',
                    'marketplace',
                    'plugins',
                    'tests\/config',
                    'tests\/config_db\.php',
                    'tests\/files',
                    'tests\/e2e\/glpi_config',
                    'tests\/e2e\/glpi_files',
                ]
            );
        }

        if ($excluded_elements === []) {
            return null;
        }

        return '/^'
           . preg_quote($directory . DIRECTORY_SEPARATOR, '/')
           . '(' . implode('|', $excluded_elements) . ')'
           . '$/';
    }
}
