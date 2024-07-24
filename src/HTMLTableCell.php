<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
class HTMLTableCell extends HTMLTableEntity
{
    private $row;
    private $header;
    private $father;
    private $sons = [];
    private $item;
    private $numberOfLines;
    private $start;

   // List of rows that have specific attributs
    private $attributForTheRow = false;

    /**
     * @param HTMLTableHeader $row
     * @param HTMLTableHeader $header
     * @param string          $content  see HTMLTableEntity#__construct()
     * @param HTMLTableCell   $father   HTMLTableCell object (default NULL)
     * @param CommonDBTM      $item     The item associated with the current cell (default NULL)
     **/
    public function __construct(
        $row,
        $header,
        $content,
        ?HTMLTableCell $father = null,
        ?CommonDBTM $item = null
    ) {

        parent::__construct($content);
        $this->row        = $row;
        $this->header     = $header;
        $this->father     = $father;

        if (!empty($item)) {
            $this->item = clone $item;
        } else {
            $this->item = null;
        }

        if (!is_null($this->father)) {
            if ($this->father->row != $this->row) {
                throw new HTMLTableCellFatherSameRow();
            }

            if ($this->father->header != $this->header->getFather()) {
                if (
                    ($this->father->header instanceof HTMLTableHeader)
                    && ($this->header->getFather() instanceof HTMLTableHeader)
                ) {
                    throw new HTMLTableCellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != ' .
                                                            $this->father->header->getName());
                }

                if ($this->father->header instanceof HTMLTableHeader) {
                    throw new HTMLTableCellFatherCoherentHeader('NULL != ' .
                                                            $this->father->header->getName());
                }

                if ($this->header->getFather() instanceof HTMLTableHeader) {
                    throw new HTMLTableCellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != NULL');
                }

                throw new HTMLTableCellFatherCoherentHeader('NULL != NULL');
            }

            $this->father->addSon($this, $header);
        } else if (!is_null($this->header->getFather())) {
            throw new HTMLTableCellWithoutFather();
        }

        $this->header->checkItemType($this->item);

        $this->copyAttributsFrom($this->header);
        if (is_string($content)) {
            $string = trim($content);
            $string = str_replace(['&nbsp;', '<br>'], '', $string);
            if (!empty($string)) {
                $this->header->addCell();
            }
        } else {
            $this->header->addCell();
        }
    }


    public function __get(string $property)
    {
        // TODO Deprecate access to variables in GLPI 10.1.
        $value = null;
        switch ($property) {
            case 'numberOfLines':
            case 'start':
                $value = $this->$property;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
        return $value;
    }

    public function __set(string $property, $value)
    {
        // TODO Deprecate access to variables in GLPI 10.1.
        switch ($property) {
            case 'numberOfLines':
            case 'start':
                $this->$property = $value;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
    }


    /**
     * @param $attributForTheRow
     **/
    public function setAttributForTheRow($attributForTheRow)
    {
        $this->attributForTheRow = $attributForTheRow;
    }


    public function getHeader()
    {
        return $this->header;
    }


    public function getItem()
    {

        if (!empty($this->item)) {
            return $this->item;
        }
        return false;
    }


    /**
     * @param $son          HTMLTableCell object
     * @param $sons_header  HTMLTableHeader object
     **/
    public function addSon(HTMLTableCell $son, HTMLTableHeader $sons_header)
    {

        if (!isset($this->sons[$sons_header->getName()])) {
            $this->sons[$sons_header->getName()] = [];
        }
        $this->sons[$sons_header->getName()][] = $son;
    }


    public function getNumberOfLines()
    {
        return $this->numberOfLines;
    }


    public function computeNumberOfLines()
    {

        if ($this->numberOfLines === null) {
            $this->numberOfLines = 1;
            if (count($this->sons) > 0) {
                foreach ($this->sons as $headered_sons) {
                    $numberOfLinesForHeader = 0;
                    foreach ($headered_sons as $son) {
                        $son->computeNumberOfLines();
                        $numberOfLinesForHeader += $son->getNumberOfLines();
                    }
                    if ($this->numberOfLines < $numberOfLinesForHeader) {
                        $this->numberOfLines = $numberOfLinesForHeader;
                    }
                }
            }
        }
    }


    /**
     * @param $value
     **/
    public function addToNumberOfLines($value)
    {
        $this->numberOfLines += $value;
    }


    /**
     * @param $cells                 array
     * @param $totalNumberOflines
     **/
    public static function updateCellSteps(array $cells, $totalNumberOflines)
    {

        $numberOfLines = 0;
        foreach ($cells as $cell) {
            $numberOfLines += $cell->getNumberOfLines();
        }

        $numberEmpty = $totalNumberOflines - $numberOfLines;
        $step        = floor($numberEmpty / (count($cells)));
        $last        = $numberEmpty % (count($cells));
        $index       = 0;

        foreach ($cells as $cell) {
            $cell->addToNumberOfLines($step + ($index < $last ? 1 : 0));
            $index++;
        }
    }


    /**
     * @param &$start
     **/
    public function computeStartEnd(&$start)
    {

        if ($this->start === null) {
            if ($this->attributForTheRow !== false) {
                $this->row->addAttributForLine($start, $this->attributForTheRow);
            }
            $this->start = $start;
            foreach ($this->sons as $sons_by_header) {
                self::updateCellSteps($sons_by_header, $this->getNumberOfLines());

                $son_start = $this->start;
                foreach ($sons_by_header as $son) {
                    $son->computeStartEnd($son_start);
                }
            }
            $start += $this->numberOfLines;
        } else {
            $start = $this->start + $this->numberOfLines;
        }
    }


    /**
     * @param $index
     * @param $options   array
     **/
    public function displayCell($index, array $options = [])
    {

        if (
            ($index >= $this->start)
            && ($index < ($this->start + $this->numberOfLines))
        ) {
            if ($index == $this->start) {
                if ($this->item instanceof CommonDBTM) {
                    Session::addToNavigateListItems($this->item->getType(), $this->item->getID());
                }
                echo "\t\t\t<td colspan='" . $this->header->getColSpan() . "'";
                if ($this->numberOfLines > 1) {
                    echo " rowspan='" . $this->numberOfLines . "'";
                }
                $this->displayEntityAttributs($options);
                echo ">";
                $this->displayContent();
                echo "</td>\n";
            }
            return true;
        }
        return false;
    }
}
