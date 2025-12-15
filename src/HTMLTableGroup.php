<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/**
 * @since 0.84
 **/
class HTMLTableGroup extends HTMLTableBase
{
    /** @var string */
    private $name;
    /** @var string */
    private $content;
    /** @var ?HTMLTableHeader[] */
    private $ordered_headers;
    /** @var HTMLTableMain */
    private $table;
    /** @var HTMLTableRow[] */
    private $rows = [];

    /**
     * @param HTMLTableMain $table
     * @param string $name
     * @param string $content
     */
    public function __construct(HTMLTableMain $table, string $name, string $content)
    {
        parent::__construct(false);
        $this->table      = $table;
        $this->name       = $name;
        $this->content    = $content;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return HTMLTableMain
     */
    public function getTable(): HTMLTableMain
    {
        return $this->table;
    }

    /**
     * @param HTMLTableHeader $header
     *
     * @return bool
     */
    public function haveHeader(HTMLTableHeader $header): bool
    {
        $header_name    = '';
        $subheader_name = '';
        $header->getHeaderAndSubHeaderName($header_name, $subheader_name);
        try {
            $subheaders = $this->getHeaders($header_name);
        } catch (HTMLTableUnknownHeaders $e) {
            try {
                $subheaders = $this->table->getHeaders($header_name);
            } catch (HTMLTableUnknownHeaders $e) {
                return false;
            }
        }
        return isset($subheaders[$subheader_name]);
    }

    public function tryAddHeader()
    {
        if ($this->ordered_headers !== null) {
            throw new Exception('Implementation error: must define all headers before any row');
        }
    }

    /**
     * @return HTMLTableRow
     */
    public function createRow(): HTMLTableRow
    {
        $new_row      = new HTMLTableRow($this);
        $this->rows[] = $new_row;
        return $new_row;
    }

    /**
     * @return void
     */
    public function prepareDisplay(): void
    {
        foreach ($this->table->getHeaderOrder() as $super_header_name) {
            $super_header = $this->table->getSuperHeaderByName($super_header_name);

            try {
                $sub_header_names = $this->getHeaderOrder($super_header_name);
                $count            = 0;

                foreach ($sub_header_names as $sub_header_name) {
                    $sub_header = $this->getHeaderByName($super_header_name, $sub_header_name);
                    if ($sub_header->hasToDisplay()) {
                        $count++;
                    }
                }

                if ($count === 0) {
                    $this->ordered_headers[] = $super_header;
                } else {
                    if ($super_header instanceof HTMLTableSuperHeader) {
                        $super_header->updateNumberOfSubHeader($count);
                    }
                    foreach ($sub_header_names as $sub_header_name) {
                        $sub_header = $this->getHeaderByName($super_header_name, $sub_header_name);
                        if ($sub_header->hasToDisplay()) {
                            $this->ordered_headers[]        = $sub_header;
                            $sub_header->numberOfSubHeaders = $count;
                        }
                    }
                }
            } catch (HTMLTableUnknownHeadersOrder $e) {
                $this->ordered_headers[] = $super_header;
            }
        }

        foreach ($this->rows as $row) {
            $row->prepareDisplay();
        }
    }

    /**
     * Display the current group (with headers and rows)
     *
     * @param int $totalNumberOfColumn  Total number of columns : to span correctly the title
     * @param array   $params               array of possible options:
     *     'display_super_for_each_group'           display the super header (ie.: big header of the table)
     *                                              before the group specific headers
     *     'display_title_for_each_group'           display the title of the header before the group
     *                                              specific headers
     *     'display_header_for_each_group'          display the header of each group
     *     'display_header_on_foot_for_each_group'  repeat group header on foot of group
     *
     * @return void
     **/
    public function displayGroup(int $totalNumberOfColumn, array $params): void
    {
        $p = array_replace([
            'display_header_for_each_group'         => true,
            'display_header_on_foot_for_each_group' => false,
            'display_super_for_each_group'          => true,
            'display_title_for_each_group'          => true,
        ], $params);

        if ($this->getNumberOfRows() > 0) {
            if (
                $p['display_title_for_each_group']
                && !empty($this->content)
            ) {
                echo "\t<tbody><tr><th colspan='" . ((int) $totalNumberOfColumn) . "'>" . htmlescape($this->content)
                 . "</th></tr></tbody>\n";
            }

            if ($p['display_super_for_each_group']) {
                echo "\t<tbody>\n";
                $this->table->displaySuperHeader();
                echo "\t</tbody>\n";
            }

            if ($p['display_header_for_each_group']) {
                echo "\t<tbody><tr class='tab_bg_1'>\n";
                foreach ($this->ordered_headers as $header) {
                    if ($header instanceof HTMLTableSubHeader) {
                        $header->updateColSpan($header->numberOfSubHeaders);
                        $with_content = true;
                    } else {
                        $with_content = false;
                    }

                    echo "\t\t";
                    $header->displayTableHeader($with_content, false);
                    echo "\n";
                }
                echo "\t</tr></tbody>\n";
            }

            $previousNumberOfSubRows = 0;
            foreach ($this->rows as $row) {
                if (!$row->notEmpty()) {
                    continue;
                }
                $currentNumberOfSubRow = $row->getNumberOfSubRows();
                if (($previousNumberOfSubRows * $currentNumberOfSubRow) > 1) {
                    echo "\t<tbody><tr class='tab_bg_1'><td colspan='" . ((int) $totalNumberOfColumn) . "'><hr></td></tr>"
                    . "</tbody>\n";
                }
                $row->displayRow($this->ordered_headers);
                $previousNumberOfSubRows = $currentNumberOfSubRow;
            }

            if ($p['display_header_on_foot_for_each_group']) {
                echo "\t<tbody><tr class='tab_bg_1'>\n";
                foreach ($this->ordered_headers as $header) {
                    if ($header instanceof HTMLTableSubHeader) {
                        $header->updateColSpan($header->numberOfSubHeaders);
                        $with_content = true;
                    } else {
                        $with_content = false;
                    }

                    echo "\t\t";
                    $header->displayTableHeader($with_content, false);
                    echo "\n";
                }
                echo "\t</tr></tbody>\n";
            }
        }
    }

    /**
     * @return int
     */
    public function getNumberOfRows(): int
    {
        return count(array_filter($this->rows, static fn($r) => $r->notEmpty()));
    }

    /**
     * @param string $name
     *
     * @return HTMLTableHeader
     */
    public function getSuperHeaderByName(string $name): HTMLTableHeader
    {
        try {
            return $this->getHeaderByName($name, '');
        } catch (HTMLTableUnknownHeader $e) {
            return $this->table->getSuperHeaderByName($name);
        }
    }
}
