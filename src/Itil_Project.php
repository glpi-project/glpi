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

use Glpi\Application\View\TemplateRenderer;

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
            switch ($item::class) {
                case Change::class:
                case Problem::class:
                case Ticket::class:
                    /** @var Change|Problem|Ticket $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            self::getTable(),
                            [
                                'itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                            ]
                        );
                    }
                    $label = self::createTabEntry(Project::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    break;

                case Project::class:
                    /** @var Project $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(self::getTable(), ['projects_id' => $item->getID()]);
                    }
                    $label = self::createTabEntry(
                        _n('Itil item', 'Itil items', Session::getPluralNumber()),
                        $nb,
                        $item::getType(),
                        Ticket::getIcon()
                    );
                    break;
            }
        }

        return $label;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
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
        /**
         * @var \DBmysql $DB
         * @var array $CFG_GLPI
         */
        global $DB, $CFG_GLPI;

        $ID = $project->getField('id');
        if (!$project->can($ID, READ)) {
            return false;
        }

        $canedit = $project->canEdit($ID);

        $queries = [];
        /** @var class-string<CommonITILObject> $itemtype */
        foreach ($CFG_GLPI['itil_types'] as $itemtype) {
            $link_table = self::getTable();
            $itil_table = $itemtype::getTable();
            $queries[] = [
                'SELECT'          => [
                    "$link_table.id AS linkid",
                    "$link_table.items_id AS id",
                    new \Glpi\DBAL\QueryExpression($DB::quoteValue($itemtype), 'itemtype'),
                ],
                'DISTINCT'        => true,
                'FROM'            => $link_table,
                'LEFT JOIN'       => [
                    $itil_table => [
                        'FKEY' => [
                            $link_table => 'items_id',
                            $itil_table => 'id',
                        ],
                    ],
                ],
                'WHERE'           => [
                    "{$link_table}.projects_id" => $ID,
                    "{$link_table}.itemtype"    => $itemtype,
                    'NOT'                      => ["{$itil_table}.id" => null],
                ],
            ];
        }

        $it = $DB->request([
            'FROM' => new \Glpi\DBAL\QueryUnion($queries),
        ]);
        $entries_by_itemtype = [];
        $used  = [];
        foreach ($it as $data) {
            $used[$data['itemtype']][$data['id']]  = $data['id'];
            $entries_by_itemtype[$data['itemtype']][] = [
                'id'             => $data['linkid'],
                'itemtype'       => $data['itemtype'],
                'item_id'        => $data['id'],
                'itemtype_label' => $data['itemtype']::getTypeName(1),
            ];
        }

        if ($canedit) {
            $twig_params = [
                'btn_msg' => _x('button', 'Add'),
                'used'    => $used,
                'ID'      => $ID,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    <div class="text-center mb-3">
                        <form method="post" action="{{ 'Itil_Project'|itemtype_form_path }}">
                            <input type="hidden" name="projects_id" value="{{ ID }}"/>
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}"/>
                            <div>
                                {{ fields.dropdownItemsFromItemtypes('items_id', null, {
                                    no_label: true,
                                    full_width: true,
                                    itemtypes: config('itil_types'),
                                    used: used,
                                    add_field_class: 'd-flex'
                                }) }}
                            </div>
                            <div class="card-body mx-n2 border-top d-flex flex-row-reverse align-items-start flex-wrap py-2">
                                <button class="btn btn-primary" type="submit" name="add" value="">{{ btn_msg }}</button>
                            </div>
                        </form>
                    </div>
TWIG, $twig_params);
        }

        $cols = CommonITILObject::getCommonDatatableColumns();
        // insert 'itemtype_label' column after 'item_id' column
        $cols['columns'] = array_merge(
            ['item_id' => $cols['columns']['item_id']],
            ['itemtype_label' => __('Type')],
            array_slice($cols['columns'], 1)
        );
        $entries = [];
        /** @var class-string<CommonITILObject> $itemtype */
        foreach ($entries_by_itemtype as $itemtype => $v) {
            $entries = [...$entries, ...$itemtype::getDatatableEntries($v)];
        }
        // add itemtype for MA
        foreach ($entries as &$entry) {
            $entry['itemtype'] = self::class;
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $cols['columns'],
            'formatters' => $cols['formatters'],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . self::class . mt_rand(),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')]
            ]
        ]);
    }

    /**
     * Show projects for an ITIL item.
     *
     * @param CommonITILObject $itil
     * @return void
     **/
    public static function showForItil(CommonITILObject $itil)
    {
        /** @var \DBmysql $DB */
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
