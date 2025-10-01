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
 * Consider using the {@link templates/components/datatable.html.twig} template instead when possible.
 *
 * Create a smart HTML table. The table allows cells to depend on other ones. As such, it is
 * possible to have rowspan for cells that are "father" of other ones. If a "father" has several
 * sons, then, it "rowspans" on all.
 * The table integrates the notion of group of rows ({@link HTMLTableGroup}). For instance, for
 * {@link Item_Devices}, each group represents a kind of device (network card, graphics card,
 * processor, memory, ...).
 * There is {@link HTMLTableSuperHeader} that defines global headers for all groups. Each group can cut
 * these {@link HTMLTableSuperHeader} as many {@link HTMLTableSubHeader} as necessary. There is an automatic
 * organisation of the headers between groups.
 *
 * The (strict) order of definition of the table is:
 * * Define all {@link HTMLTableSuperHeader} that are used by each group: {@link HTMLTableMain::addHeader()}
 * * Define one {@link HTMLTableGroup}: {@link HTMLTableMain::createGroup()}
 * * Define all{@link  HTMLTableSubHeader} depending of previously defined {@link  HTMLTableSuperHeader}
 *   for the given group: {@link HTMLTableGroup::addHeader()}
 * * Create all {@link HTMLTableRow} for the given group: {@link HTMLTableGroup::createRow()}
 * * Create all {@link HTMLTableCell} for the given row : {@link HTMLTableRow::addCell()}
 * and so on for each group.
 * When done, call {@link HTMLTableMain::display()} to render the table.
 *
 * A column that don't have any content is collapsed
 *
 * For further explaination, refer to {@link NetworkPort} and all its dependencies (NetworkName, IPAddress,
 * IPNetwork, ...) or {@link Item_Devices} and each kind of device.
 * @since 0.84
 */
class HTMLTableMain extends HTMLTableBase
{
    private $title;

    /** @var array<HTMLTableGroup> */
    private $groups    = [];
    private $itemtypes = [];

    public function __construct()
    {
        parent::__construct(true);
    }

    /**
     * We can define a global name for the table : this will print as header that colspan all columns
     *
     * @param string $name  the name to print inside the header
     *
     * @return void
     **/
    public function setTitle($name)
    {
        $this->title = $name;
    }

    public function tryAddHeader()
    {
        if (count($this->groups) > 0) {
            throw new Exception('Implementation error: must define all headers before any subgroups');
        }
    }

    /**
     * @param string $name The name of the group, to be able to retrieve the group
     *                            later with {@link HTMLTableMain::getHeaderByName()}
     * @param mixed $content            The title of the group : display before the group itself
     *
     * TODO : study to be sure that the order is the one we have defined ...
     *
     * @return boolean|HTMLTableGroup
     * @see HTMLTableEntity::content
     **/
    public function createGroup($name, $content)
    {
        if (!empty($name) && !isset($this->groups[$name])) {
            $this->groups[$name] = new HTMLTableGroup($this, $name, $content);
        }
        return $this->getGroup($name);
    }

    /**
     * @param $itemtype
     * @param $title
     **/
    public function addItemType($itemtype, $title)
    {
        $this->itemtypes[$itemtype] = $title;
    }

    /**
     * Retrieve a group by its name
     *
     * @param string $group_name the group name
     *
     * @return boolean|HTMLTableGroup
     **/
    public function getGroup($group_name)
    {
        return $this->groups[$group_name] ?? false;
    }

    /**
     * Display the super headers, for the global table, or the groups
     **/
    public function displaySuperHeader()
    {
        echo "\t\t<tr class='noHover'>\n";
        foreach ($this->getHeaderOrder() as $header_name) {
            $header = $this->getSuperHeaderByName($header_name);
            echo "\t\t\t";
            $header->displayTableHeader(true);
            echo "\n";
        }
        echo "\t\t</tr>\n";
    }

    /**
     * get the total number of rows (ie.: the sum of each group number of rows)
     *
     * Beware that a row is counted only if it is not empty (ie.: at least one addCell)
     *
     * @return integer the total number of rows
     **/
    public function getNumberOfRows()
    {
        $numberOfRow = 0;
        foreach ($this->groups as $group) {
            $numberOfRow += $group->getNumberOfRows();
        }
        return $numberOfRow;
    }

    /**
     * Display the table itself
     *
     * @param array $params    array of possible options:
     *    'html_id'                                the global HTML ID of the table
     *    'display_thead'                          display the header before the first group
     *    'display_tfoot'                          display the header at the end of the table
     *    'display_header_for_each_group'          display the header of each group
     *    'display_header_on_foot_for_each_group'  repeat group header on foot of group
     *    'display_super_for_each_group'           display the super header befor each group
     *    'display_title_for_each_group'           display the title of each group
     *
     * @return void
     **/
    public function display(array $params)
    {
        $p = array_replace([
            'html_id' => '',
            'display_thead' => true,
            'display_tfoot' => true,
        ], $params);

        foreach ($this->groups as $group) {
            $group->prepareDisplay();
        }

        $totalNumberOfRow = $this->getNumberOfRows();

        $totalNumberOfColumn = 0;
        foreach ($this->getHeaders() as $header) {
            $colspan              = $header['']->getColSpan();
            $totalNumberOfColumn += $colspan;
        }

        foreach ($this->itemtypes as $itemtype => $title) {
            Session::initNavigateListItems($itemtype, $title);
        }

        echo "\n<table class='tab_cadre_fixehov'";
        if (!empty($p['html_id'])) {
            echo " id='" . htmlescape($p['html_id']) . "'";
        }
        echo ">\n";

        $open_thead = ((!empty($this->title)) || ($p['display_thead']));
        if ($open_thead) {
            echo "\t<thead>\n";
        }

        if (!empty($this->title)) {
            echo "\t\t<tr class='noHover'><th colspan='" . ((int) $totalNumberOfColumn) . "'>" . htmlescape($this->title)
              . "</th></tr>\n";
        }

        if ($totalNumberOfRow === 0) {
            if ($open_thead) {
                echo "\t</thead>\n";
            }

            echo "\t\t<tr class='tab_bg_1'>"
              . "<td class='center' colspan='" . ((int) $totalNumberOfColumn) . "'>" . __s('None') . "</td></tr>\n";
        } else {
            if ($p['display_thead']) {
                $this->displaySuperHeader();
            }

            if ($open_thead) {
                echo "\t</thead>\n";
            }

            if ($p['display_tfoot']) {
                echo "\t<tfoot>\n";
                $this->displaySuperHeader();
                echo "\t</tfoot>\n";
            }

            foreach ($this->groups as $group) {
                $group->displayGroup($totalNumberOfColumn, $p);
            }
        }
        echo "</table>\n";
    }
}
