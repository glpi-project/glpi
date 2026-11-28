<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Console\Diagnostic;

use DirectoryIterator;
use Document;
use Glpi\Console\AbstractCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\date;
use function Safe\fclose;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\sha1_file;

final class CheckDocumentsIntegrityCommand extends AbstractCommand
{
    private const DOCUMENT_OK = 0;
    private const ERROR_MISSING_FILE = 1;
    private const ERROR_UNEXPECTED_CONTENT = 2;
    private const ERROR_UNREADABLE_FILE = 3;

    /**
     * Path of the log file.
     */
    private string $log_file;

    /**
     * Log file handle.
     *
     * @var resource
     */
    private $log_handle;

    /**
     * Whether the detected issues entry header has been written in the log file.
     */
    private bool $log_has_issues = false;

    /**
     * Summary lines to write at the end of the log file.
     *
     * @var string[]
     */
    private array $log_summary_lines = [];

    protected function configure()
    {
        parent::configure();

        $this->setName('diagnostic:check_documents_integrity');
        $this->setDescription(__("Validate files integrity for GLPI's documents."));

        $this->addOption(
            'check-orphans',
            null,
            InputOption::VALUE_NONE,
            __('Check for files stored in documents directory that are not linked to any document, instead of checking documents integrity')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Detected issues are written in the log file as soon as they are found,
        // to keep them if the command is interrupted
        $this->openLogFile();

        if ($input->getOption('check-orphans')) {
            [$has_error, $files_summary] = $this->checkOrphans();
            $this->outputOrphansSummary($files_summary);
        } else {
            [$has_error, $summary] = $this->checkDocuments();
            $this->outputSummary($summary);
        }

        $this->closeLogFile();

        return $has_error ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Check that each document from db has its file, with the expected content
     *
     * @return array{bool, array<int, int>} Whether an error was found, and number of documents for each status
     */
    private function checkDocuments(): array
    {
        // Get all documents
        $data = $this->getDocuments();

        // Keep track of global command status, one error = failed
        $has_error = false;

        // Validate each documents
        $progress_message = (fn(array $document_row) => sprintf(
            __('Checking document #%s "%s" (%s)...'),
            $document_row['id'],
            $document_row['name'],
            $document_row['filepath']
        ));

        // Keep track of each status count, for the final summary
        $summary = [
            self::DOCUMENT_OK              => 0,
            self::ERROR_MISSING_FILE       => 0,
            self::ERROR_UNEXPECTED_CONTENT => 0,
            self::ERROR_UNREADABLE_FILE    => 0,
        ];

        $count = $this->countDocuments();
        foreach ($this->iterate($data, $progress_message, $count) as $document_row) {
            $status = $this->validateDocument($document_row);
            $summary[$status]++;

            if ($status != self::DOCUMENT_OK) {
                $this->report($this->getDetailedError($status, $document_row), 'error');
                $has_error = true;
            }
        }

        return [$has_error, $summary];
    }

    /**
     * Check that each file stored in documents directories is linked to a document from db.
     *
     * Thumbnails generated by GLPI (see `Document::getResizedImagePath()`) are stored next to their original file,
     * with a `_<width>-<height>.<png|jpg>` suffix. They are not linked to a document in db, so they are considered
     * as orphans only if their original file is not linked to a document.
     *
     * @return array{bool, array<string, int>} Whether an error was found, and number of files for each kind
     */
    private function checkOrphans(): array
    {
        $has_error = false;
        $files_summary = [
            'checked'           => 0,
            'documents'         => 0,
            'thumbnails'        => 0,
            'orphans'           => 0,
            'orphan_thumbnails' => 0,
        ];

        $known_filepaths = $this->getKnownFilepaths();

        // Known filepaths without extension, to find original file of thumbnails
        $known_filepaths_without_ext = [];
        foreach (array_keys($known_filepaths) as $known_filepath) {
            $known_filepaths_without_ext[preg_replace('/\.[^.\/]*$/', '', $known_filepath)] = true;
        }

        $progress_message = (fn(string $filepath) => sprintf(
            __('Checking file %s...'),
            $filepath
        ));

        foreach ($this->iterate($this->getStoredFiles(), $progress_message) as $filepath) {
            $files_summary['checked']++;

            if (isset($known_filepaths[$filepath])) {
                $files_summary['documents']++;
                continue;
            }

            preg_match('/^(.+)_\d+-\d+\.(png|jpg)$/', $filepath, $matches);
            if ($matches !== []) {
                if (isset($known_filepaths_without_ext[$matches[1]])) {
                    $files_summary['thumbnails']++;
                    continue;
                }

                // Documents are stored in a directory named after their extension
                $original_filepath = $matches[1] . '.' . strstr($filepath, '/', true);
                $this->report(
                    sprintf(
                        __('Orphan thumbnail %s: original document %s not found in database.'),
                        GLPI_DOC_DIR . '/' . $filepath,
                        $original_filepath
                    ),
                    'error'
                );
                $files_summary['orphan_thumbnails']++;
                $has_error = true;
                continue;
            }

            $this->report(
                sprintf(__('Orphan file %s: no matching document in database.'), GLPI_DOC_DIR . '/' . $filepath),
                'error'
            );
            $files_summary['orphans']++;
            $has_error = true;
        }

        return [$has_error, $files_summary];
    }

    /**
     * Get all documents from db
     *
     * @return iterable
     */
    protected function getDocuments(): iterable
    {
        global $DB;

        $i = 0;

        do {
            $rows = $DB->request([
                'SELECT' => ['id', 'name', 'filepath', 'sha1sum', 'filename'],
                'FROM'   => Document::getTable(),
                'LIMIT'  => 1000,
                'OFFSET' => $i * 1000,
            ]);
            yield from $rows;

            $i++;
        } while (count($rows) > 0);
    }

    /**
     * Get the number of documents in the database db
     *
     * @return int
     */
    protected function countDocuments(): int
    {
        return countElementsInTable(Document::getTable());
    }

    /**
     * Validate a document
     *
     * @param array $row Simplified row of glpi_documents (id, filepath, sha1sum, filename)
     *
     * @return int DOCUMENT_OK or error code
     */
    protected function validateDocument(array $row): int
    {
        // Check that file exist
        $path = GLPI_DOC_DIR . '/' . $row['filepath'];
        if (!file_exists($path)) {
            return self::ERROR_MISSING_FILE;
        }

        // Check that file can be read
        if (!is_readable($path)) {
            return self::ERROR_UNREADABLE_FILE;
        }

        // Validate content
        if (sha1_file($path) !== $row['sha1sum']) {
            return self::ERROR_UNEXPECTED_CONTENT;
        }

        // All good
        return self::DOCUMENT_OK;
    }

    /**
     * Get filepaths of all documents from db, as keys for a fast lookup
     *
     * @return array<string, true>
     */
    protected function getKnownFilepaths(): array
    {
        global $DB;

        $filepaths = [];

        $rows = $DB->request([
            'SELECT' => ['filepath'],
            'FROM'   => Document::getTable(),
            'WHERE'  => [
                'NOT' => ['filepath' => null],
                ['filepath' => ['<>', '']],
            ],
        ]);
        foreach ($rows as $row) {
            $filepaths[$row['filepath']] = true;
        }

        return $filepaths;
    }

    /**
     * Get relative paths of all files stored in documents directories
     * (i.e. `GLPI_DOC_DIR/<EXT>/`, `_*` directories are not documents storage).
     *
     * @return iterable<string>
     */
    protected function getStoredFiles(): iterable
    {
        $basedir = GLPI_DOC_DIR;

        foreach (new DirectoryIterator($basedir) as $directory) {
            if (
                !$directory->isDir()
                || $directory->isDot()
                || str_starts_with($directory->getFilename(), '_')
                || str_starts_with($directory->getFilename(), '.')
            ) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory->getPathname(), RecursiveDirectoryIterator::SKIP_DOTS)
            );
            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                yield str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($file->getPathname(), strlen($basedir) + 1)
                );
            }
        }
    }

    /**
     * Output the summary of the checked documents
     *
     * @param array<int, int> $summary Number of documents for each status
     *
     * @return void
     */
    protected function outputSummary(array $summary): void
    {
        $this->report('');
        $this->report(__('Summary'), 'info', true);
        $this->report(sprintf(__('Checked documents: %d'), array_sum($summary)), 'info', true);
        $this->report(sprintf(__('Valid documents: %d'), $summary[self::DOCUMENT_OK]), 'info', true);

        $errors = [
            self::ERROR_MISSING_FILE       => __('File not found'),
            self::ERROR_UNEXPECTED_CONTENT => __('Invalid checksum'),
            self::ERROR_UNREADABLE_FILE    => __('Unreadable file'),
        ];
        foreach ($errors as $type => $label) {
            $this->report(sprintf('%s: %d', $label, $summary[$type]), $summary[$type] > 0 ? 'error' : 'info', true);
        }
    }

    /**
     * Output the summary of the checked files
     *
     * @param array<string, int> $files_summary Number of files for each kind
     *
     * @return void
     */
    private function outputOrphansSummary(array $files_summary): void
    {
        $this->report('');
        $this->report(__('Summary'), 'info', true);
        $this->report(sprintf(__('Checked files: %d'), $files_summary['checked']), 'info', true);
        $this->report(sprintf(__('Document files: %d'), $files_summary['documents']), 'info', true);
        $this->report(sprintf(__('Thumbnails: %d'), $files_summary['thumbnails']), 'info', true);

        $errors = [
            'orphans'           => __('Orphan files'),
            'orphan_thumbnails' => __('Orphan thumbnails'),
        ];
        foreach ($errors as $key => $label) {
            $this->report(sprintf('%s: %d', $label, $files_summary[$key]), $files_summary[$key] > 0 ? 'error' : 'info', true);
        }
    }

    /**
     * Output a message in console and write it in the log file
     *
     * @param string $message    Message
     * @param string $tag        Console output tag (`info`, `error`...)
     * @param bool   $is_summary Whether the message is part of the summary
     *
     * @return void
     */
    private function report(string $message, string $tag = 'info', bool $is_summary = false): void
    {
        $this->outputMessage(
            $message !== '' ? sprintf('<%s>%s</%s>', $tag, $message, $tag) : '',
            OutputInterface::VERBOSITY_QUIET
        );

        if ($message === '') {
            return;
        }
        if ($is_summary) {
            // Summary is written at the end, see `closeLogFile()`
            $this->log_summary_lines[] = $message;
            return;
        }

        if (!$this->log_has_issues) {
            fwrite($this->log_handle, '[' . date('Y-m-d H:i:s') . '] ' . __('Detected issues') . PHP_EOL);
            $this->log_has_issues = true;
        }
        fwrite($this->log_handle, $message . PHP_EOL);
    }

    /**
     * Open the log file, inside GLPI logs directory.
     *
     * @return void
     */
    private function openLogFile(): void
    {
        $this->log_file   = GLPI_LOG_DIR . '/check_documents_integrity-' . date('Ymd-His') . '.log';
        $this->log_handle = fopen($this->log_file, 'a');
    }

    /**
     * Write the summary in the log file and close it.
     *
     * Detected issues and summary are written as two dated entries, so the web log viewer displays them
     * as separate entries. Summary is written last, as the log viewer only reads the end of large files.
     *
     * @return void
     */
    private function closeLogFile(): void
    {
        fwrite($this->log_handle, '[' . date('Y-m-d H:i:s') . '] ' . implode(PHP_EOL, $this->log_summary_lines) . PHP_EOL);
        fclose($this->log_handle);

        $this->output->writeln('', OutputInterface::VERBOSITY_QUIET);
        $this->output->writeln(
            '<comment>' . sprintf(__('Log file: %s'), $this->log_file) . '</comment>',
            OutputInterface::VERBOSITY_QUIET
        );
    }

    /**
     * Get detailed error message
     *
     * @param int   $type         Error type
     * @param array $document_row Invalid document's data
     *
     * @return string
     */
    protected function getDetailedError(int $type, array $document_row): string
    {
        switch ($type) {
            case self::ERROR_MISSING_FILE:
                $message = __("File not found");
                break;
            case self::ERROR_UNEXPECTED_CONTENT:
                $message = __("Invalid checksum");
                break;
            case self::ERROR_UNREADABLE_FILE:
                $message = __("Unreadable file");
                break;
            default:
                // Should not happen
                $message = __("Unknown error");
                break;
        }

        return sprintf(
            '%s #%s "%s" (%s): %s.',
            Document::getTypeName(1),
            $document_row['id'],
            $document_row['name'],
            $document_row['filepath'],
            $message
        );
    }
}
