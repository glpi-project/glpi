<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Console\Database;

use CommonDBTM;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixHtmlEncodingCommand extends AbstractCommand
{
    /**
     * Error code returned when a specified itemtype does not exists
     *
     * @var integer
     */
    const ERROR_ITEMTYPE_NOT_FOUND = 1;

    /**
     * Error code returned when at least one item id is not found
     *
     * @var integer
     */
    const ERROR_ITEM_ID_NOT_FOUND = 2;

    /**
     * Error code returned when at least one field is not found
     *
     * @var integer
     */
    const ERROR_FIELD_NOT_FOUND = 3;

    /**
     * Error code returned when update of an item failed
     *
     * @var integer
     */
    const ERROR_UPDATE_FAILED = 4;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:fix_html_encoding');
        $this->setAliases(['db:fix_html']);
        $this->setDescription(__('Fix Html encoding in database.'));

        $this->addOption(
            'itemtype',
            null,
            InputOption::VALUE_REQUIRED,
            __('Itemtype to fix')
        );

        $this->addOption(
            'id',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            __('Id of the item to fix')
        );

        $this->addOption(
            'field',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            __('Field of the item to fix')
        );

        $this->addOption(
            'dump',
            null,
            InputOption::VALUE_OPTIONAL,
            __('Path of file containing dump of existing values.')
        );

        $this->addUsage('--itemtype=ITILFollowup --id=42 --field=content [--dump=file_path.sql]');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $DB;

        $itemtype = $input->getOption('itemtype');
        if (empty($itemtype) || !class_exists($itemtype) || !is_a($itemtype, CommonDBTM::class, true)) {
            $this->output->writeln(
                '<error>' . sprintf(__('Itemtype %s not found'), $itemtype) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_ITEMTYPE_NOT_FOUND;
        }

        $item_ids = $input->getOption('id');

        // Check all items exists
        if (count($item_ids) < 1) {
            $this->output->writeln(
                '<error>' . sprintf(__('Item id not specified')) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_ITEM_ID_NOT_FOUND;
        }
        foreach ($item_ids as $item_id) {
            $item = new $itemtype();
            if (!$item->getFromDB($item_id)) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Item id %s not found'), $item_id) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                return self::ERROR_ITEM_ID_NOT_FOUND;
            }
        }

        $fields = $input->getOption('field');

        // Check all fields exist
        if (count($fields) < 1) {
            $this->output->writeln(
                '<error>' . sprintf(__('Field not specified')) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_FIELD_NOT_FOUND;
        }
        foreach ($fields as $field) {
            if (!$DB->fieldExists($itemtype::getTable(), $field)) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Field %s not found'), $field) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                return self::ERROR_FIELD_NOT_FOUND;
            }
        }

        $dump_content = '';

        $failed_items = [];
        foreach ($item_ids as $item_id) {
            $item = new $itemtype();
            $item->getFromDB($item_id);

            // Create the rollback SQL query
            if ($input->getOption('dump')) {
                $object_state = [];
                foreach ($fields as $field) {
                    $object_state[$field] = $DB->escape($item->fields[$field]);
                }

                $item_rollback = $DB->buildUpdate(
                    $itemtype::getTable(),
                    $object_state,
                    ['id' => $item_id],
                ) . PHP_EOL . PHP_EOL;
            }

            // update the item
            $update = [];
            foreach ($fields as $field) {
                $update[$field] = $this->doubleEncoding($item->fields[$field]);
                $update[$field] = $DB->escape($update[$field]);
            }

            $success = $DB->update(
                $itemtype::getTable(),
                $update,
                ['id' => $item->fields['id']],
            );
            if (!$success) {
                $failed_items[] = $item_id;
                continue;
            }

            // feed the rollback SQL queries dump
            if ($input->getOption('dump')) {
                $dump_content .= $item_rollback;
            }
        }

        // Save the rollback SQL queries dump
        if ($input->getOption('dump')) {
            $dump_file_name = $input->getOption('dump');
            if (file_put_contents($dump_file_name, $dump_content) == strlen($dump_content)) {
                $this->output->writeln(
                    '<comment>' . sprintf(__('File %s contains SQL queries that can be used to rollback command.'), $dump_file_name) . '</comment>',
                    OutputInterface::VERBOSITY_QUIET
                );
            } else {
                $this->output->writeln(
                    '<comment>' . sprintf(__('Failed to write rollback SQL queries file %s'), $dump_file_name) . '</comment>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }
        }

        if (count($failed_items) > 0) {
            $this->output->writeln(
                '<error>' . sprintf(__('Unable to update %s items'), count($failed_items)) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            foreach ($failed_items as $item_id) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Itemtype %s ID %s'), $itemtype, $item_id) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }
            return self::ERROR_UPDATE_FAILED;
        }

        return 0;
    }

    /**
     * Remove double encoding of HTML tags
     * character < is encoded &#38;lt; but should be encoded &#60;
     * character > is encoded &#38;gt; but should be encoded &#62;
     *
     * Does not take into account the content of < and > pair
     *
     * @param string $input
     * @return string
     */
    private function doubleEncoding(string $input): string
    {
        // Prepare the double encoding fix of HTML tag
        $pattern = [
            '/&#38;lt;/', // Opening tag
            '/&#38;gt;/', // closing tag
        ];
        $replace = [
            '&#60;',
            '&#62;',
        ];
        return preg_replace($pattern, $replace, $input);
    }
}
