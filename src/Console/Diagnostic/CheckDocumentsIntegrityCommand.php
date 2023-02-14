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

final class CheckDocumentsIntegrityCommand extends AbstractCommand
{
    private const DOCUMENT_OK = 0;
    private const ERROR_MISSING_FILE = 1;
    private const ERROR_UNEXPECTED_CONTENT = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('diagnostic:check_documents_integrity');
        $this->setDescription(__("Validate files integrity for GLPI's documents."));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all documents
        $data = $this->getDocuments();

        // Keep track of global command status, one error = failed
        $has_error = false;

        // Validate each documents
        $progress_message = function (array $document_row) {
            return sprintf(
                __('Checking document #%s "%s" (%s)...'),
                $document_row['id'],
                $document_row['name'],
                $document_row['filepath']
            );
        };

        $count = $this->countDocuments();
        foreach ($this->iterate($data, $progress_message, $count) as $document_row) {
            $status = $this->validateDocument($document_row);

            if ($status != self::DOCUMENT_OK) {
                $this->outputMessage(
                    '<error>' . $this->getDetailedError($status, $document_row) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $has_error = true;
            }
        }

        return $has_error ? Command::FAILURE : Command::SUCCESS;
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

        // Validate content
        if (sha1_file($path) !== $row['sha1sum']) {
            return self::ERROR_UNEXPECTED_CONTENT;
        }

        // All good
        return self::DOCUMENT_OK;
    }

    /**
     * Get detailed error message
     *
     * @param int   $type     Error type
     * @param array $document Invalid document's data
     *
     * @return string
     */
    protected function getDetailedError(int $type, array $document_row): string
    {
        switch ($type) {
            case self::ERROR_MISSING_FILE:
                $message = __("file not found");
                break;
            case self::ERROR_UNEXPECTED_CONTENT:
                $message = __("invalid checksum");
                break;
            default:
                // Should not happen
                $message = __("unknown error");
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
