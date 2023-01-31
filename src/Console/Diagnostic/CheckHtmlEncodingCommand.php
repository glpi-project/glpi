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

use CommonDBTM;
use Glpi\Console\AbstractCommand;
use ITILFollowup;
use Search;
use Session;
use Ticket;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Prior from GLPI 10.0, some HTML contents were not properly encoded.
 *
 * This CLI tool helps to fix items encoding issues.
 */
final class CheckHtmlEncodingCommand extends AbstractCommand
{
    /**
     * Error code returned when invalid items are found and are not fixed.
     *
     * @var integer
     */
    public const ERROR_INVALID_ITEMS_FOUND = 1;

    /**
     * Error code returned when update of an item failed.
     *
     * @var integer
     */
    public const ERROR_UPDATE_FAILED = 2;

    /**
     * Error code returned when rollback file could not be created.
     *
     * @var integer
     */
    public const ERROR_ROLLBACK_FILE_FAILED = 3;

    /**
     * Items with invalid HTML.
     *
     * @var array
     */
    private array $invalid_items = [];

    /**
     * Count of items with invalid HTML that have NOT been fixed.
     *
     * @var int
     */
    private int $failed_items_count = 0;

    /**
     * Columns which contains rich text, populated by analyzing search options.
     *
     * @var array
     */
    private array $text_fields = [];

    protected function configure()
    {
        parent::configure();

        $this->setName('diagnostic:check_html_encoding');
        $this->setDescription(__('Check for badly HTML encoded content in database.'));

        $this->addOption(
            'fix',
            'f',
            InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
            __('Fix detected issues')
        );

        $this->addOption(
            'dump',
            'd',
            InputOption::VALUE_REQUIRED,
            __('Path of file where will be stored SQL queries that can be used to rollback changes')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->warnAboutExecutionTime();
        $this->findTextFields();
        $this->scanItems();

        $count = $this->countItems($this->invalid_items);
        if ($count === 0) {
            $output->writeln('<info>' . __('No item to fix.') . '</info>');
            return 0;
        }

        $output->writeln('<info>' . sprintf(_n('Found %d item to fix.', 'Found %d items to fix.', $count), $count) . '</info>');

        $fix = $input->getOption('fix');

        if ($fix === null && !$this->input->getOption('no-interaction')) {
            $question_helper = $this->getHelper('question');
            $fix = $question_helper->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    _n('Do you want to fix it?', 'Do you want to fix them?', $count) . ' [Yes/no]'
                )
            );
        }

        if ($fix !== true) {
            return self::ERROR_INVALID_ITEMS_FOUND;
        }

        if ($input->getOption('dump')) {
            $this->dumpObjects();
        }

        $this->fixItems();

        if ($this->failed_items_count > 0) {
            $this->output->writeln(
                '<error>' . sprintf(__('Unable to update %s items'), $this->failed_items_count) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_UPDATE_FAILED;
        }

        $output->writeln('<info>' . __('HTML encoding has been fixed.') . '</info>');

        return 0;
    }

    /**
     * Dump items
     *
     * @return void
     */
    private function dumpObjects(): void
    {
        global $DB;

        $dump_content = '';

        foreach ($this->invalid_items as $itemtype => $items) {
            foreach ($items as $item_id => $fields) {
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

    /**
     * Fix encoding issues.
     *
     * @return void
     */
    private function fixItems(): void
    {
        foreach ($this->invalid_items as $itemtype => $items) {
            $this->outputMessage(
                '<comment>' . sprintf(__('Fixing %s...'), $itemtype::getTypeName(Session::getPluralNumber())) . '</comment>',
            );
            $progress_message = function (array $fields, int $id) use ($itemtype) {
                return sprintf(__('Fixing %s with ID %s...'), $itemtype::getTypeName(1), $id);
            };

            foreach ($this->iterate($items, $progress_message) as $item_id => $fields) {
                /* @var \CommonDBTM $item */
                $item = new $itemtype();
                if (!$item->getFromDB($item_id)) {
                    $this->outputMessage(
                        '<error>' . sprintf(__('Unable to fix %s with ID %s.'), $itemtype::getTypeName(1), $item_id) . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $this->failed_items_count++;
                    continue;
                }
                $this->fixOneItem($item, $fields);
            }
        }
    }

    /**
     * Fix a single item, on specified fields.
     *
     * @param CommonDBTM $item item to fix
     * @param array $fields fields names to fix
     * @return void
     */
    private function fixOneItem(CommonDBTM $item, array $fields): void
    {
        global $DB;

        $itemtype = $item::getType();

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
            $this->outputMessage(
                '<error>' . sprintf(__('Unable to fix %s with ID %s.'), $itemtype::getTypeName(1), $item->getID()) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            $this->failed_items_count++;
        }
    }

    /**
     * Fix a single field of an item.
     *
     * @param CommonDBTM $item
     * @param string $field
     * @return string
     */
    private function fixOneField(CommonDBTM $item, string $field): string
    {
        $new_value = $item->fields[$field];

        if (in_array($item::getType(), [Ticket::getType(), ITILFollowup::getType()]) && $field == 'content') {
            $new_value = $this->fixEmailHeadersEncoding($new_value);
        }

        $new_value = $this->fixQuoteEntityWithoutSemicolon($new_value);

        return $new_value;
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
        $replace = '&#38;lt;${2}&#38;gt;';
        $output = preg_replace($pattern, $replace, $output);
        // Triple encoded should be now double encoded (this double encoding is expected)

        return $output;
    }

    /**
     * Fix &quot; HTML entity without its final semicolon.
     * @see https://github.com/glpi-project/glpi/pull/6084
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

    /**
     * Find rich text fields for itemtypes given as CLI argument.
     *
     * @return void
     */
    private function findTextFields(): void
    {
        global $DB;

        $table_iterator = $DB->listTables();
        foreach ($table_iterator as $table_data) {
            $table = $table_data['TABLE_NAME'];
            $itemtype = getItemTypeForTable($table);

            if (!is_a($itemtype, CommonDBTM::class, true)) {
                continue;
            }

            $search_options = Search::getOptions($itemtype);
            foreach ($search_options as $search_option) {
                if (!isset($search_option['table'])) {
                    continue;
                }
                if (
                    $search_option['table'] === $table
                    && ($search_option['datatype'] ?? '') === 'text'
                    && ($search_option['htmltext'] ?? false) === true
                ) {
                    $this->text_fields[$itemtype][] = $search_option['field'];
                }
            }
        }
    }

    /**
     * Search in all items of an itemtype for bad HTML.
     *
     * @return void
     */
    private function scanItems(): void
    {
        $this->outputMessage(
            '<comment>' . __('Scanning database for items to fix...') . '</comment>'
        );
        foreach ($this->text_fields as $itemtype => $fields) {
            foreach ($fields as $field) {
                $this->scanField($itemtype, $field);
            }
        }
    }

    /**
     * Search for bad HTML in a single column of a table
     *
     * @param string $itemtype
     * @param string $field
     * @return void
     */
    private function scanField(string $itemtype, string $field): void
    {
        global $DB;

        $searches = [
            [$field => ['LIKE', '%&quot(?!;)/%']],
        ];

        if (in_array($itemtype, [Ticket::getType(), ITILFollowup::getType()]) && $field == 'content') {
            $searches[] = [
                $field => ['REGEXP', $DB->escape('(&#38;amp;lt;)(?<email>[^@]*?@[a-zA-Z0-9\-.]*?)(&#38;amp;gt;)')]
            ];
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $itemtype::getTable(),
            'WHERE'  => [
                'OR' => $searches,
            ],
        ]);

        foreach ($iterator as $row) {
            $this->invalid_items[$itemtype][$row['id']][] = $field;
        }
    }

    /**
     * Count items in list of invalid idems
     *
     * @return integer
     */
    private function countItems(array $items_array): int
    {
        $count = 0;

        if (count($items_array) === 0) {
            return 0;
        }

        foreach ($items_array as $items) {
            $count += count($items);
        }

        return $count;
    }
}
