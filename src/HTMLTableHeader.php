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
 *
 */
abstract class HTMLTableHeader extends HTMLTableEntity
{
    /** @var string */
    private $name;
    private ?\HTMLTableHeader $father;
    /** @var array<class-string<CommonDBTM>, string> */
    private $itemtypes   = [];
    /** @var int */
    private $colSpan     = 1;
    private int $numberCells = 0;
    /** @var int */
    public $numberOfSubHeaders;

    /**
     * get the table of the header (for a subheader, it is the table of its super header)
     *
     * @return HTMLTableBase the table owning the current header
     **/
    abstract protected function getTable();

    /**
     * get its name and subname : usefull for instance to create an index for arrays
     *
     * @param string $header_name [out]     header name
     * @param string $subheader_name [out]  sub header name ( = '' in case of super header)
     *
     * @return void
     **/
    abstract public function getHeaderAndSubHeaderName(&$header_name, &$subheader_name);

    /**
     * check to see if it is a super header or not
     *
     * @return bool
     */
    abstract public function isSuperHeader();

    /**
     * @param class-string<CommonDBTM> $itemtype
     * @param string $title         (default '')
     *
     * @return void
     */
    public function setItemType($itemtype, $title = ''): void
    {
        $this->itemtypes[$itemtype] = $title;
    }

    /**
     * @param string               $name    the name of the header
     * @param string               $content see HTMLTableEntity#__construct()
     * @param HTMLTableHeader|null $father  father of the current column (default NULL)
     */
    public function __construct($name, $content, ?HTMLTableHeader $father = null)
    {
        parent::__construct($content);

        $this->name           = $name;
        $this->father         = $father;
    }

    /**
     * @param ?CommonDBTM $item
     *
     * @return void
     *
     * @throws Exception
     */
    public function checkItemType(?CommonDBTM $item = null): void
    {
        if (($item === null) && (count($this->itemtypes) > 0)) {
            throw new Exception('Implementation error: header requires an item');
        }
        if ($item !== null) {
            if (!isset($this->itemtypes[$item->getType()])) {
                throw new Exception('Implementation error: type mismatch between header and cell');
            }
            $table = $this->getTable();
            if ($table instanceof HTMLTableMain) {
                $table->addItemType($item->getType(), $this->itemtypes[$item->getType()]);
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $colSpan
     *
     * @return void
     */
    public function setColSpan($colSpan): void
    {
        $this->colSpan = $colSpan;
    }

    /**
     * @return void
     */
    public function addCell(): void
    {
        $this->numberCells++;
    }

    /**
     * @return bool
     */
    public function hasToDisplay()
    {
        return ($this->numberCells > 0);
    }

    /**
     * @return int
     */
    public function getColSpan()
    {
        return $this->colSpan;
    }

    /**
     * @param bool $with_content do we display the content ?
     * @param bool $main_header  main header (from table) or secondary (from group) ? (true by default)
     *
     * @return void
     */
    public function displayTableHeader($with_content, $main_header = true): void
    {
        if ($main_header) {
            echo "<th";
        } else {
            echo "<td class='subheader'";
        }
        echo " colspan='" . ((int) $this->colSpan) . "'>";
        if ($with_content) {
            $this->displayContent();
        } else {
            echo "&nbsp;";
        }
        if ($main_header) {
            echo "</th>";
        } else {
            echo "</td>";
        }
    }

    /**
     * @return ?HTMLTableHeader
     */
    public function getFather()
    {
        return $this->father;
    }
}
