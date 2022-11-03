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
use ITILFollowup;
use Ticket;
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

    /**
     * Error code returned when rollback file cound not be created
     *
     * @var integer
     */
    const ERROR_ROLLBACK_FILE_FAILED = 5;

    private array $failed_items = [];

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:fix_html_encoding');
        $this->setAliases(['db:fix_html']);
        $this->setDescription(__('Fix HTML encoding in database.'));

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

        $this->checkArguments();

        $itemtype = $input->getOption('itemtype');

        if ($input->getOption('dump')) {
            $this->dumpObjects();
        }

        $this->fixItems();

        if (count($this->failed_items) > 0) {
            $this->output->writeln(
                '<error>' . sprintf(__('Unable to update %s items'), count($this->failed_items)) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            foreach ($this->failed_items as $item_id) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Itemtype %s ID %s'), $itemtype, $item_id) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }
            return self::ERROR_UPDATE_FAILED;
        }

        $output->writeln('<info>' . __('HTML encoding has been fixed.') . '</info>');
        return 0;
    }

    /**
     * Check that the arguments are correct
     *
     * @return void
     */
    private function checkArguments()
    {
        global $DB;

        // Check itemtype exists
        $itemtype = $this->input->getOption('itemtype');
        if (empty($itemtype) || !class_exists($itemtype) || !is_a($itemtype, CommonDBTM::class, true)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . sprintf(__('Itemtype %s not found'), $itemtype) . '</error>',
                self::ERROR_ITEMTYPE_NOT_FOUND
            );
        }

        // Check all items exists
        $item_ids = $this->input->getOption('id');
        if (count($item_ids) < 1) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . sprintf(__('Item id not specified')) . '</error>',
                self::ERROR_ITEM_ID_NOT_FOUND
            );
        }
        foreach ($item_ids as $item_id) {
            $item = new $itemtype();
            if (!$item->getFromDB($item_id)) {
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . sprintf(__('Item id %s not found'), $item_id) . '</error>',
                    self::ERROR_ITEM_ID_NOT_FOUND
                );
            }
        }

        // Check all fields exist
        $fields = $this->input->getOption('field');
        if (count($fields) < 1) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . sprintf(__('Field not specified')) . '</error>',
                self::ERROR_FIELD_NOT_FOUND
            );
        }
        foreach ($fields as $field) {
            if (!$DB->fieldExists($itemtype::getTable(), $field)) {
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . sprintf(__('Field %s not found'), $field) . '</error>',
                    self::ERROR_FIELD_NOT_FOUND
                );
            }
        }
    }

    /**
     * Dump items
     *
     * @return void
     */
    private function dumpObjects()
    {
        global $DB;

        $itemtype = $this->input->getOption('itemtype');
        $item_ids = $this->input->getOption('id');
        $fields = $this->input->getOption('field');

        $dump_content = '';

        foreach ($item_ids as $item_id) {
            // Get the item to save
            $item = new $itemtype();
            $item->getFromDB($item_id);

            // read the fields to save
            $object_state = [];
            foreach ($fields as $field) {
                $object_state[$field] = $DB->escape($item->fields[$field]);
            }

            // Build the SQL query
            $dump_content .= $DB->buildUpdate(
                $itemtype::getTable(),
                $object_state,
                ['id' => $item_id],
            ) . ';' . PHP_EOL;
        }

        // Save the rollback SQL queries dump
        $dump_file_name = $this->input->getOption('dump');
        if (@file_put_contents($dump_file_name, $dump_content) == strlen($dump_content)) {
            $this->output->writeln(
                '<comment>' . sprintf(__('File %s contains SQL queries that can be used to rollback command.'), $dump_file_name) . '</comment>',
                OutputInterface::VERBOSITY_QUIET
            );
        } else {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<comment>' . sprintf(__('Failed to write rollback SQL queries in "%s" file.'), $dump_file_name) . '</comment>',
                self::ERROR_ROLLBACK_FILE_FAILED
            );
        }
    }

    private function fixItems()
    {
        $itemtype = $this->input->getOption('itemtype');
        $item_ids = $this->input->getOption('id');

        foreach ($item_ids as $item_id) {
            $item = new $itemtype();
            if (!$item->getFromDB($item_id)) {
                $this->failed_items[] = $item_id;
                continue;
            }
            $this->fixOneItem($item);
        }
    }

    private function fixOneItem(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $this->input->getOption('itemtype');
        $fields = $this->input->getOption('field');

        // update the item
        $update = [];
        foreach ($fields as $field) {
            $update[$field] = $this->fixOneField($item, $field);
            $update[$field] = $DB->escape($update[$field]);
        }

        $success = $DB->update(
            $itemtype::getTable(),
            $update,
            ['id' => $item->fields['id']],
        );
        if (!$success) {
            $this->failed_items[] = $item->getID();
        }
    }

    private function fixOneField(CommonDBTM $item, string $field)
    {
        $new_value = $item->fields[$field];

        $new_value = $this->doubleEncoding($new_value);

        if (in_array($item::getType(), [Ticket::getType(), ITILFollowup::getType()]) && $field == 'content') {
            $new_value = $this->fixEmailHeadersEncoding($new_value);
        }

        $new_value = $this->fixQuoteEntityWithoutSemicolon($new_value);

        return $new_value;
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

    /**
     * Fix double encoded HTML entities in old followups
     * @see https://github.com/glpi-project/glpi/issues/8330
     *
     * @param string $input
     * @return string
     */
    private function fixEmailHeadersEncoding(string $input): string
    {
        $output = $input;

        // Not very strict pattern for emails, but should be enough
        // Capturing parentheses:
        // 1: Triple encoded < character
        // 2: email address
        // 3: Triple encoded > character
        $pattern = '/(&#38;amp;lt;)(?<email>[^@]*?@[a-zA-Z0-9\-.]*?)(&#38;amp;gt;)/';
        $replace = '&amp;lt;${2}&amp;gt;';
        $output = preg_replace($pattern, $replace, $output);
        // Triple encoded should be now double encoded

        // Not very strict pattern for emails, but should be enough
        // Capturing parentheses:
        // 1: Double encoded < character
        // 2: email address
        // 3: Double encoded > character
        $pattern = '/(&amp;lt;)(?<email>[^@]*?@[a-zA-Z0-9\-.]*?)(&amp;gt;)/';
        $replace = '&lt;${2}&gt;';
        $output = preg_replace($pattern, $replace, $output);

        return $output;
    }

    /**
     * Fix &quot; HTML entity without its final semicolon
     * @see https://github.com/glpi-project/glpi/pull/6084
     *
     * The pattern searches for &quot (without semicolon) found only between encoded < and >
     * Therefore any ocurence found between HTML tabs are ignored
     *
     * @param string $input
     * @return string
     */
    private function fixQuoteEntityWithoutSemicolon(string $input): string
    {
        $output = $input;

        // Add the missing semicolon to &quot; HTML entity
        $pattern = '/&quot(?!;)/';
        $replace = '&quot;';
        $output = preg_replace($pattern, $replace, $output);

        return $output;
    }
}
