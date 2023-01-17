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

namespace Glpi\Console\Diagnostic;

use DBmysqlIterator;
use Document;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateCommand extends AbstractCommand
{
    private const DOCUMENT_OK = 0;
    private const ERROR_MISSING_FILE = 1;
    private const ERROR_UNEXPECTED_CONTENT = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:diagnostic:check_documents_integrity');
        $this->setAliases(['diagnostic:check_documents_integrity']);
        $this->setDescription("Validate files integrity for GLPI's documents");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all documents
        $data = $this->getDocuments();

        // Keep track of global command status, one error = failed
        $has_error = false;

        // Validate each documents
        $progress_message = function (array $document_row) {
            return sprintf(__('Checking document "%s"...'), $document_row['filename']);
        };
        foreach ($this->iterate($data, $progress_message) as $document_row) {
            $status = $this->validateDocument($document_row);

            // Print error message
            if ($status != self::DOCUMENT_OK) {
                $format = __('Document %d - %s: %s');

                $this->outputMessage("<error>" . sprintf(
                    $format,
                    $document_row['id'],
                    $document_row['filename'],
                    $this->getDetailedError($status, $document_row)
                ) . '</error>');
            }
            $has_error = true;
        }

        return $has_error ? Command::FAILURE : Command::SUCCESS;
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
            'SELECT' => ['id', 'filepath', 'sha1sum', 'filename'],
            'FROM' => Document::getTable(),
        ]);
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

        // Validate content
        if (sha1_file($path) !== $row['sha1sum']) {
            return self::ERROR_UNEXPECTED_CONTENT;
        }

        // All good
        return self::DOCUMENT_OK;
    }

    /**
     * Explain why a given document is invalid
     *
     * @param int   $type     Error type
     * @param array $document Invalid document's data
     *
     * @return string Formatted error message (error type (context))
     */
    protected function getDetailedError(int $type, array $document_row): string
    {
        $format = __('%s (%s)');

        switch ($type) {
            case self::ERROR_MISSING_FILE:
                $message = __("File not found");
                $context = $document_row['filepath'];
                break;
            case self::ERROR_UNEXPECTED_CONTENT:
                $message = __("Invalid sha1sum");
                $context = $document_row['sha1sum'];
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
