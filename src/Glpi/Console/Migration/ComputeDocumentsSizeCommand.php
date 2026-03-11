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

namespace Glpi\Console\Migration;

use Document;
use Glpi\Console\AbstractCommand;
use Glpi\Message\MessageType;
use Glpi\Progress\ConsoleProgressIndicator;
use LogicException;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\filesize;

final class ComputeDocumentsSizeCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('migration:compute_documents_size');
        $this->setDescription(__('Computes the missing file sizes in the database.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $doc_class = new Document();
        $documents = $doc_class->find([
            'NOT' => ['filepath' => null],
            'filesize' => null,
        ]);
        $counter = count($documents);
        if ($counter > 0) {
            $output->writeln('<comment>' . sprintf(__('Computing %s files...'), $counter) . '</comment>');
            $progress_indicator = new ConsoleProgressIndicator($output);
            $progress_indicator->setMaxSteps($counter);
            foreach ($documents as $document) {
                $filepath = GLPI_DOC_DIR . "/" . $document['filepath'];
                if (is_file($filepath)) {
                    try {
                        $filesize = filesize($filepath);
                    } catch (FilesystemException $e) {
                        $progress_indicator->addMessage(
                            MessageType::Error,
                            sprintf(__('Unable to read the file `%s` size.'), $document['filepath'])
                        );
                        $progress_indicator->advance();
                        continue;
                    }
                    $doc_class->update([
                        'id' => $document['id'],
                        'filesize' => $filesize,
                    ]);
                }
                $progress_indicator->advance();
            }

            $progress_indicator->finish();
        } else {
            $output->writeln('<comment>' . __('No elements found.') . '</comment>');
        }
        return self::SUCCESS;
    }
}
