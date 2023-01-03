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
 * Relation between Itil items and Projects
 *
 * @since 9.4.0
 **/
class Itil_Project extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype';
    public static $items_id_1 = 'items_id';
    public static $itemtype_2 = 'Project';
    public static $items_id_2 = 'projects_id';

    public static function getTypeName($nb = 0)
    {

        return _n('Link Project/Itil', 'Links Project/Itil', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $label = '';

        if (static::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case Change::class:
                case Problem::class:
                case Ticket::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            self::getTable(),
                            [
                                'itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                            ]
                        );
                    }
                    $label = self::createTabEntry(Project::getTypeName(Session::getPluralNumber()), $nb);
                    break;

                case Project::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(self::getTable(), ['projects_id' => $item->getID()]);
                    }
                    $label = self::createTabEntry(
                        _n('Itil item', 'Itil items', Session::getPluralNumber()),
                        $nb
                    );
                    break;
            }
        }

        return $label;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case Change::class:
            case Problem::class:
            case Ticket::class:
                self::showForItil($item);
                break;

            case Project::class:
                self::showForProject($item);
                break;
        }
        return true;
    }


    /**
     * Show ITIL items for a project.
     *
     * @param Project $project
     * @return void
     **/
    public static function showForProject(Project $project)
    {
        global $DB;

        $ID = $project->getField('id');
        if (!$project->can($ID, READ)) {
            return false;
        }

        $canedit = $project->canEdit($ID);

        /** @var CommonITILObject $itemtype */
        foreach ([Change::class, Problem::class, Ticket::class] as $itemtype) {
            $rand    = mt_rand();

            $selfTable = self::getTable();
            $itemTable = $itemtype::getTable();

            $iterator = $DB->request([
                'SELECT'          => [
                    "$selfTable.id AS linkid",
                    "$itemTable.*"
                ],
                'DISTINCT'        => true,
                'FROM'            => $selfTable,
                'LEFT JOIN'       => [
                    $itemTable => [
                        'FKEY' => [
                            $selfTable => 'items_id',
                            $itemTable => 'id',
                        ],
                    ],
                ],
                'WHERE'           => [
                    "{$selfTable}.itemtype"    => $itemtype,
                    "{$selfTable}.projects_id" => $ID,
                    'NOT'                      => ["{$itemTable}.id" => null],
                ],
                'ORDER'  => "{$itemTable}.name",
            ]);

            $numrows = $iterator->count();

            $items = [];
            $used  = [];
            foreach ($iterator as $data) {
                $items[$data['id']] = $data;
                $used[$data['id']]  = $data['id'];
            }
            if ($canedit) {
                echo '<div class="firstbloc">';
                $formId = 'itilproject_' . strtolower($itemtype) . '_form' . $rand;
                echo '<form name="' . $formId . '"
                        id="' . $formId . '"
                        method="post"
                        action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
                echo '<table class="tab_cadre_fixe">';

                $label = null;
                switch ($itemtype) {
                    case Change::class:
                        $label = __('Add a change');
                        break;
                    case Problem::class:
                        $label = __('Add a problem');
                        break;
                    case Ticket::class:
                         $label = __('Add a ticket');
                        break;
                }
                echo '<tr class="tab_bg_2"><th colspan="2">' . $label . '</th></tr>';
                echo '<tr class="tab_bg_2">';
                echo '<td>';
                echo '<input type="hidden" name="projects_id" value="' . $ID . '" />';
                echo '<input type="hidden" name="itemtype" value="' . $itemtype . '" />';
                $itemtype::dropdown(
                    [
                        'entity'      => $project->getEntityID(),
                        'entity_sons' => $project->isRecursive(),
                        'name'        => 'items_id',
                        'used'        => $used,
                    ]
                );
                echo '</td>';
                echo '<td class="center">';
                echo '<input type="submit" name="add" value="' . _sx('button', 'Add') . '" class="btn btn-primary" />';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
                Html::closeForm();
                echo '</div>';
            }

            echo '<div class="spaced">';
            $massContainerId = 'mass' . __CLASS__ . $rand;
            if ($canedit && $numrows) {
                Html::openMassiveActionsForm($massContainerId);
                $massiveactionparams = [
                    'num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                    'container'     => $massContainerId,
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo '<table class="tab_cadre_fixehov">';
            echo '<tr class="noHover">';
            echo '<th colspan="12">' . $itemtype::getTypeName($numrows) . '</th>';
            echo '</tr>';
            if ($numrows) {
                $itemtype::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
                Session::initNavigateListItems(
                    $itemtype,
                    //TRANS : %1$s is the itemtype name,
                    //        %2$s is the name of the item (used for headings of a list)
                    sprintf(__('%1$s = %2$s'), Project::getTypeName(1), $project->fields['name'])
                );

                $i = 0;
                foreach ($items as $data) {
                     Session::addToNavigateListItems($itemtype, $data['id']);
                     $itemtype::showShort(
                         $data['id'],
                         [
                             'row_num'                => $i,
                             'type_for_massiveaction' => __CLASS__,
                             'id_for_massiveaction'   => $data['linkid']
                         ]
                     );
                     $i++;
                }
                $itemtype::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
            }
            echo '</table>';

            if ($canedit && $numrows) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
            echo '</div>';
        }
    }

    /**
     * Show projects for an ITIL item.
     *
     * @param CommonITILObject $itil
     * @return void
     **/
    public static function showForItil(CommonITILObject $itil)
    {
        global $DB;

        $ID = $itil->getField('id');
        if (!$itil->can($ID, READ)) {
            return false;
        }

        $canedit = $itil->canEdit($ID);
        $rand    = mt_rand();

        $selfTable = self::getTable();
        $projectTable = Project::getTable();

        $iterator = $DB->request([
            'SELECT'          => [
                "$selfTable.id AS linkid",
                "$projectTable.*"
            ],
            'DISTINCT'        => true,
            'FROM'            => $selfTable,
            'LEFT JOIN'       => [
                $projectTable => [
                    'FKEY' => [
                        $selfTable    => 'projects_id',
                        $projectTable => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "{$selfTable}.itemtype" => $itil->getType(),
                "{$selfTable}.items_id" => $ID,
                'NOT'                   => ["{$projectTable}.id" => null],
            ],
            'ORDER'  => "{$projectTable}.name",
        ]);

        $numrows = $iterator->count();

        $projects = [];
        $used     = [];
        foreach ($iterator as $data) {
            $projects[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
        }

        if (
            $canedit
            && !in_array($itil->fields['status'], array_merge(
                $itil->getClosedStatusArray(),
                $itil->getSolvedStatusArray()
            ))
        ) {
            echo '<div class="firstbloc">';
            $formId = 'itilproject_form' . $rand;
            echo '<form name="' . $formId . '"
                     id="' . $formId . '"
                     method="post"
                     action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
            echo '<table class="tab_cadre_fixe">';
            echo '<tr class="tab_bg_2"><th colspan="2">' . __('Add a project') . '</th></tr>';
            echo '<tr class="tab_bg_2">';
            echo '<td>';
            echo '<input type="hidden" name="itemtype" value="' . $itil->getType() . '" />';
            echo '<input type="hidden" name="items_id" value="' . $ID . '" />';
            Project::dropdown(
                [
                    'used'   => $used,
                    'entity' => $itil->getEntityID()
                ]
            );
            echo '</td>';
            echo '<td class="center">';
            echo '<input type="submit" name="add" value=" ' . _sx('button', 'Add') . '" class="btn btn-primary" />';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            Html::closeForm();
            echo '</div>';
        }

        echo '<div class="spaced">';
        $massContainerId = 'mass' . __CLASS__ . $rand;
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm($massContainerId);
            $massiveactionparams = [
                'num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => $massContainerId,
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo '<table class="tab_cadre_fixehov">';
        echo '<tr class="noHover">';
        echo '<th colspan="12">' . Project::getTypeName($numrows) . '</th>';
        echo '</tr>';
        if ($numrows) {
            Project::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
            Session::initNavigateListItems(
                Project::class,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(__('%1$s = %2$s'), $itil::getTypeName(1), $itil->fields['name'])
            );

            $i = 0;
            foreach ($projects as $data) {
                Session::addToNavigateListItems(Project::class, $data['id']);
                Project::showShort(
                    $data['id'],
                    [
                        'row_num'               => $i,
                        'type_for_massiveaction' => __CLASS__,
                        'id_for_massiveaction'   => $data['linkid']
                    ]
                );
                 $i++;
            }
            Project::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
        }
        echo '</table>';

        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo '</div>';
    }
}
