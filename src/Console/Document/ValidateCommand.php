<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Console\Document;

use DBmysqlIterator;
use Document;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateCommand extends AbstractCommand
{
    private const ERROR_MISSING_FILE = 1;
    private const ERROR_UNEXPECTED_CONTENT = 2;

    /**
     * Keep track of errors (document id => error type)
     * @var
     */
    private array $errors = [];

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:document:check_files_integrity');
        $this->setAliases(['document:check_files_integrity']);
        $this->setDescription("Validate files integrity for GLPI's documents");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all documents
        $data = $this->getDocuments();

        // Init progress bar
        $progress_bar = new ProgressBar($output, count($data));
        $progress_bar->start();

        // Validate each documents
        foreach ($data as $document_row) {
            $this->validateDocument($document_row);
            $progress_bar->advance();
        }

        // Clean progress bar
        $progress_bar->finish();
        $output->writeln("");
        $output->writeln("");

        if (!count($this->errors)) {
            // Success
            $output->writeln('<info>' . __('All documents have been validated.') . '</info>');
        } else {
            // Failure
            $output->writeln('<error>' . __('The following documents are invalids:') . '</error>');

            foreach ($this->errors as $id => $type) {
                $document = Document::getById($id);
                $format = __('Document %d - %s: %s');

                // Print error details
                $output->writeln(sprintf(
                    $format,
                    $id,
                    $document->fields['filename'],
                    $this->getDetailedError($type, $document)
                ));
            }
        }

        return 0;
    }

    /**
     * Get all documents from db
     *
     * @return DBmysqlIterator
     */
    protected function getDocuments(): DBmysqlIterator
    {
        global $DB;

        return $DB->request([
            'SELECT' => ['id', 'filepath', 'sha1sum'],
            'FROM' => Document::getTable(),
        ]);
    }

    /**
     * Validate a document
     *
     * @param array $row Simplified row of glpi_documents (id, filepath, sha1sum)
     *
     * @return bool
     */
    protected function validateDocument(array $row): bool
    {
        // Check that file exist
        $path = GLPI_DOC_DIR . '/' . $row['filepath'];
        if (!file_exists($path)) {
            $this->errors[$row['id']] = self::ERROR_MISSING_FILE;
            return false;
        }

        // Validate content
        if (sha1_file($path) !== $row['sha1sum']) {
            $this->errors[$row['id']] = self::ERROR_UNEXPECTED_CONTENT;
            return false;
        }

        // All good
        return true;
    }

    /**
     * Explain why a given document is invalid
     *
     * @param int $type Error type
     * @param Document $document Invalid document
     *
     * @return string Formatted error message (error type (context))
     */
    protected function getDetailedError(int $type, Document $document): string
    {
        $format = __('%s (%s)');

        switch ($type) {
            case self::ERROR_MISSING_FILE:
                $message = __("File not found");
                $context = $document->fields['filepath'];
                break;
            case self::ERROR_UNEXPECTED_CONTENT:
                $message = __("Invalid sha1sum");
                $context = $document->fields['sha1sum'];
                break;
            default:
                // Should not happen
                $message = __("Unknown error");
                $context = "unkown";
                break;
        }

        return sprintf($format, $message, $context);
    }
}
