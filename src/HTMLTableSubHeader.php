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

/**
 * @since 0.84
 **/
class HTMLTableSubHeader extends HTMLTableHeader
{
   // The headers of each column
    private $header;
    public $numberOfSubHeaders;

    /**
     * @param HTMLTableSuperHeader $header
     * @param string               $name
     * @param string               $content
     * @param HTMLTableHeader      $father
     **/
    public function __construct(
        HTMLTableSuperHeader $header,
        $name,
        $content,
        HTMLTableHeader $father = null
    ) {

        $this->header = $header;
        parent::__construct($name, $content, $father);
        $this->copyAttributsFrom($this->header);
    }


    public function isSuperHeader()
    {
        return false;
    }


    public function getHeaderAndSubHeaderName(&$header_name, &$subheader_name)
    {

        $header_name    = $this->header->getName();
        $subheader_name = $this->getName();
    }


    public function getCompositeName()
    {
        return $this->header->getCompositeName() . $this->getName();
    }


    protected function getTable()
    {
        return $this->header->getTable();
    }


    public function getHeader()
    {
        return $this->header;
    }


    /**
     * @param $numberOfSubHeaders
     **/
    public function updateColSpan($numberOfSubHeaders)
    {
        $this->setColSpan($this->header->getColSpan() / $numberOfSubHeaders);
    }
}
