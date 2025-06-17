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

use Glpi\Application\View\TemplateRenderer;

/**
 * Item_Project Class
 *
 *  Relation between Projects and Items
 *
 *  @since 0.85
 **/
class Item_Project extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1          = 'Project';
    public static $items_id_1          = 'projects_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static function getTypeName($nb = 0)
    {
        return _n('Project item', 'Project items', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function prepareInputForAdd($input)
    {

        // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['projects_id' => $input['projects_id'],
                'itemtype'    => $input['itemtype'],
                'items_id'    => $input['items_id'],
            ]) > 0
        ) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }


    /**
     * Print the HTML array for Items linked to a project
     *
     * @param $project Project object
     *
     * @return void
     **/
    public static function showForProject(Project $project)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $instID = $project->fields['id'];

        if (!$project->can($instID, READ)) {
            return false;
        }
        $canedit = $project->canEdit($instID);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);
        $number = count($types_iterator);

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='projectitem_form$rand' id='projectitem_form$rand' method='post'
                action='" . htmlescape(Toolbox::getItemTypeFormURL(__CLASS__)) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __s('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                      => $CFG_GLPI["project_asset_types"],
                'entity_restrict'
                                                      => ($project->fields['is_recursive']
                                                          ? getSonsOf(
                                                              'glpi_entities',
                                                              $project->fields['entities_id']
                                                          )
                                                          : $project->fields['entities_id']),
            ]);
            echo "</td><td class='center' width='30%'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "<input type='hidden' name='projects_id' value='$instID'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixe'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . _sn('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . htmlescape(Entity::getTypeName(1)) . "</th>";
        $header_end .= "<th>" . __s('Name') . "</th>";
        $header_end .= "<th>" . __s('Serial number') . "</th>";
        $header_end .= "<th>" . __s('Inventory number') . "</th></tr>";
        echo $header_begin . $header_top . $header_end;

        $totalnb = 0;
        foreach ($types_iterator as $row) {
            $itemtype = $row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }

            if ($item->canView()) {
                $iterator = self::getTypeItems($instID, $itemtype);
                $nb = count($iterator);

                $prem = true;
                foreach ($iterator as $data) {
                    $name = $data[$itemtype::getNameField()];
                    if (
                        $_SESSION["glpiis_ids_visible"]
                        || empty($data[$itemtype::getNameField()])
                    ) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                    }
                    $link     = $item::getFormURLWithID($data['id']);
                    $namelink = "<a href=\"" . $link . "\">" . htmlescape($name) . "</a>";

                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                        echo "</td>";
                    }
                    if ($prem) {
                        $typename = htmlescape($item->getTypeName($nb));
                        echo "<td class='center top' rowspan='$nb'>" .
                         (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename) . "</td>";
                        $prem = false;
                    }
                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
                    echo "<td class='center" .
                        (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                    echo ">" . $namelink . "</td>";
                    echo "<td class='center'>" . (isset($data["serial"]) ? "" . htmlescape($data["serial"]) . "" : "-") .
                    "</td>";
                    echo "<td class='center'>" .
                      (isset($data["otherserial"]) ? "" . htmlescape($data["otherserial"]) . "" : "-") . "</td>";
                    echo "</tr>";
                }
                $totalnb += $nb;
            }
        }
        if ($totalnb > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='2'>" .
               (($totalnb > 0) ? sprintf(__s('%1$s = %2$s'), __('Total'), $totalnb) : "&nbsp;");
            echo "</td><td colspan='4'>&nbsp;</td></tr> ";
        }
        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Project':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');

                default:
                    if (
                        Project::canView()
                        && $item instanceof CommonDBTM
                        && in_array($item->getType(), $CFG_GLPI["project_asset_types"])
                    ) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            // Direct one
                            $nb = self::countForItem($item);

                            // Linked items
                            $linkeditems = $item->getLinkedItems();

                            if (count($linkeditems)) {
                                foreach ($linkeditems as $type => $tab) {
                                    $typeitem = getItemForItemtype($type);
                                    foreach ($tab as $ID) {
                                        $typeitem->getFromDB($ID);
                                        $nb += self::countForItem($typeitem);
                                    }
                                }
                            }
                        }
                        return self::createTabEntry(Project::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    }
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        switch ($item->getType()) {
            case 'Project':
                self::showForProject($item);
                break;

            default:
                if (
                    Project::canView()
                    && $item instanceof CommonDBTM
                    && in_array($item->getType(), $CFG_GLPI["project_asset_types"])
                ) {
                    self::showForAsset($item);
                }
                break;
        }
        return true;
    }

    private static function showForAsset(CommonDBTM $item): void
    {

        $item_project = new self();
        $item_projects = $item_project->find([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        $used = $entries = [];

        foreach ($item_projects as $value) {
            $used[] = $value['projects_id'];
            $project = new Project();
            $result = $project->getFromDB($value['projects_id']);

            if ($result === false) {
                continue;
            }

            $priority = CommonITILObject::getPriorityName($project->fields['priority']);
            $prioritycolor  = $_SESSION["glpipriority_" . $project->fields['priority']];
            $state = ProjectState::getById($project->fields['projectstates_id']);

            if (!$project->can($project->fields['id'], READ)) {
                $data = [
                    'name' => $project->getLink(),
                    'projectstates_id' => '',
                    'priority' => '',
                    'percent_done' => '',
                ];
            } else {
                $data = [
                    'name' => $project->getLink(),
                    'projectstates_id' => $state !== false
                        ? [
                            'content' => $state->fields['name'],
                            'color' => $state->fields['color'],
                        ] : '',
                    'priority' => [
                        'content' => $priority,
                        'color' => $prioritycolor,
                    ],
                    'percent_done' => Html::getProgressBar((float) $project->fields['percent_done']),
                ];
            }
            $entries[] = array_merge($project->fields, $data);
        }

        $cols = [
            'columns' => [
                "name" => __('Name'),
                "priority" => __('Priority'),
                "code" => __('Code'),
                "projectstates_id" => _n('State', 'States', 1),
                "percent_done" => __('Percent done'),
                "creation_date" => __('Creation date'),
                "content" => __('Description'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'priority' => 'badge',
                'projectstates_id' => 'badge',
                'percent_done' => 'raw_html',
                'creation_date' => 'date',
            ],
        ];

        TemplateRenderer::getInstance()->display('pages/tools/item_project.html.twig', [
            'item' => $item,
            'used' => $used,
            'datatable_params' => [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'columns' => $cols['columns'],
                'formatters' => $cols['formatters'],
                'entries' => $entries,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
            ],
        ]);
    }
}
