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
     * @param Project $project
     *
     * @return bool
     **/
    public static function showForProject(Project $project): bool
    {
        $instID = $project->getID();

        if (!$project->can($instID, READ)) {
            return false;
        }
        $canedit = $project->canEdit($instID);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);

        $totalnb = 0;
        $entity_names_cache = [];
        $entries = [];
        $used = [];

        foreach ($types_iterator as $row) {
            $itemtype = $row['itemtype'];
            if (!($item = getItemForItemtype($itemtype)) || !$item::canView()) {
                continue;
            }

            $itemtype_name = $item::getTypeName(1);
            $iterator = self::getTypeItems($instID, $itemtype);
            $nb = count($iterator);

            foreach ($iterator as $data) {
                $name = $data[$itemtype::getNameField()];
                if (
                    $_SESSION["glpiis_ids_visible"]
                    || empty($data[$itemtype::getNameField()])
                ) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                }
                $link     = $item::getFormURLWithID($data['id']);
                $namelink = "<a href=\"" . htmlescape($link) . "\">" . htmlescape($name) . "</a>";

                if (!isset($entity_names_cache[$data['entity']])) {
                    $entity_names_cache[$data['entity']] = Dropdown::getDropdownName("glpi_entities", $data['entity']);
                }

                $entries[] = [
                    'itemtype' => self::class,
                    'id' => $data['linkid'],
                    'row_class' => (isset($data['is_deleted']) && $data['is_deleted']) ? 'table-deleted' : '',
                    'type' => $itemtype_name,
                    'name' => $namelink,
                    'entity' => $entity_names_cache[$data['entity']],
                    'serial' => $data["serial"] ?? '-',
                    'otherserial' => $data["otherserial"] ?? '-',
                ];
                $used[$itemtype][$data['id']] = $data['id'];
            }
            $totalnb += $nb;
        }

        $columns = [
            'type' => _n('Type', 'Types', 1),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns += [
            'name' => __('Name'),
            'serial' => __('Serial number'),
            'otherserial' => __('Inventory number'),
        ];
        $formatters = [
            'name' => 'raw_html',
        ];
        $footers = [];
        if ($totalnb > 0) {
            $footers = [
                [sprintf(__('%1$s = %2$s'), __('Total'), $totalnb)],
            ];
        }

        TemplateRenderer::getInstance()->display('pages/tools/item_project.html.twig', [
            'item' => $project,
            'can_edit' => $canedit,
            'used' => $used,
            'datatable_params' => [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'columns' => $columns,
                'formatters' => $formatters,
                'entries' => $entries,
                'footers' => $footers,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => $canedit,
                'massiveactionparams' => [
                    'container' => 'massiveactioncontainer' . $rand,
                    'itemtype'  => self::class,
                ],
            ],
        ]);

        return true;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$withtemplate) {
            $nb = 0;
            switch (true) {
                case $item instanceof Project:
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
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Project) {
            return self::showForProject($item);
        }

        if (
            Project::canView()
            && in_array($item->getType(), $CFG_GLPI["project_asset_types"])
        ) {
            return self::showForAsset($item);
        }

        return false;
    }

    private static function showForAsset(CommonDBTM $item): bool
    {
        $item_project = new self();
        $item_projects = $item_project->find([
            'itemtype' => $item::class,
            'items_id' => $item->getID(),
        ]);

        $used = $entries = [];

        foreach ($item_projects as $value) {
            $used[] = $value['projects_id'];
            $project = new Project();
            $result = $project->getFromDB($value['projects_id']);

            if ($result === false || !$project->can($project->getID(), READ)) {
                continue;
            }

            $priority = CommonITILObject::getPriorityName($project->fields['priority']);
            $prioritycolor  = $_SESSION["glpipriority_" . $project->fields['priority']];
            $state = ProjectState::getById($project->fields['projectstates_id']);

            $entries[] = [
                'name' => $project->getLink(),
                'priority' => [
                    'content' => $priority,
                    'color' => $prioritycolor,
                ],
                'code' => $project->fields['code'],
                'projectstates_id' => $state !== false
                    ? [
                        'content' => $state->fields['name'],
                        'color' => $state->fields['color'],
                    ] : '',
                'percent_done' => (float) $project->fields['percent_done'],
                'creation_date' => $project->fields['date_creation'],
            ];
        }

        $cols = [
            'columns' => [
                "name" => __('Name'),
                "priority" => __('Priority'),
                "code" => __('Code'),
                "projectstates_id" => _n('State', 'States', 1),
                "percent_done" => __('Percent done'),
                "creation_date" => __('Creation date'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'priority' => 'badge',
                'projectstates_id' => 'badge',
                'percent_done' => 'progress',
                'creation_date' => 'date',
            ],
        ];

        TemplateRenderer::getInstance()->display('pages/tools/item_project.html.twig', [
            'item' => $item,
            'can_edit' => $item->canEdit($item->getID()),
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

        return true;
    }
}
