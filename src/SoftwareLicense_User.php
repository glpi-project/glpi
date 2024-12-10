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
 * SoftwareLicense_User Class
 *
 * Relation between SoftwareLicense and Users
 **/
class SoftwareLicense_User extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'User';
    public static $items_id_1 = 'users_id';

    public static $itemtype_2 = 'SoftwareLicense';
    public static $items_id_2 = 'softwarelicenses_id';

    public static function getTypeName($nb = 0)
    {
        return User::getTypeName($nb);
    }

    public static function countForLicense($softwarelicenses_id)
    {
        return countElementsInTable(static::getTable(), ['softwarelicenses_id' => $softwarelicenses_id]);
    }

    public static function showForUser(CommonDBTM $item, $withtemplate = 0)
    {
        $ID       = $item->fields['id'];

        if (
            !Item_SoftwareLicense::canView()
            || !$item->can($ID, READ)
        ) {
            return;
        }

        $canedit = $item->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($item);

        $licenses = [];
        $used      = [];
        foreach ($iterator as $data) {
            $licenses[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ((int) $withtemplate !== 2)) {
            $twig_params = [
                'item' => $item,
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Item_SoftwareLicense'|itemtype_form_path }}">
                        <input type="hidden" name="itemtype" value="{{ get_class(item) }}">
                        <input type="hidden" name="items_id" value="{{ item.getID() }}">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <div class="d-flex">
                            {{ fields.dropdownField('SoftwareLicense', 'softwarelicenses_id', 0, null, {
                                used: used,
                            }) }}
                            {% set btn %}
                                <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                            {% endset %}
                            {{ fields.htmlField('', btn, null) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $software_cache = [];
        $type_cache = [];
        foreach ($licenses as $data) {
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'number'      => $data['number']
            ];
            $con         = new SoftwareLicense();
            $con->getFromResultSet($data);
            $entry['name'] = $con->getLink();
            if (!isset($entity_cache[$con->fields["entities_id"]])) {
                $entity_cache[$con->fields["entities_id"]] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $con->fields["entities_id"]
                );
            }
            $entry['entity'] = $entity_cache[$con->fields["entities_id"]];

            if (
                !isset($type_cache[$con->fields["softwares_id"]])
                && !empty($con->fields["softwares_id"])
            ) {
                $soft = new Software();
                $url = $software_cache[$con->fields["softwares_id"]] = $soft->getFormURLWithID($con->fields["softwares_id"]);
                $name = Dropdown::getDropdownName(
                    "glpi_softwares",
                    $con->fields["softwares_id"]
                );
                $software_cache[$con->fields["softwares_id"]] = "<a href=\"$url\">" . $name . "</a>";
                $entry['software'] = $software_cache[$con->fields["softwares_id"]];
            }

            if (!isset($type_cache[$con->fields["softwarelicensetypes_id"]])) {
                $type_cache[$con->fields["softwarelicensetypes_id"]] = Dropdown::getDropdownName(
                    "glpi_softwarelicensetypes",
                    $con->fields["softwarelicensetypes_id"]
                );
            }
            $entry['type'] = $type_cache[$con->fields["softwarelicensetypes_id"]];
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'software' => Software::getTypeName(1),
                'type' => _n('Type', 'Types', 1),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'software' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && (int) $withtemplate !== 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ]
        ]);
    }
}
