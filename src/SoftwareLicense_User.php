<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
    public static $itemtype_1 = User::class;
    public static $items_id_1 = 'users_id';

    public static $itemtype_2 = SoftwareLicense::class;
    public static $items_id_2 = 'softwarelicenses_id';

    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    public static $checkItem_2_Rights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public function prepareInputForAdd($input)
    {
        if (
            !isset($input['softwarelicenses_id'])
            || !is_numeric($input['softwarelicenses_id'])
            || !isset($input['users_id'])
        ) {
            trigger_error('softwarelicenses_id and users_id are mandatory', E_USER_WARNING);
            return false;
        }

        $softwarelicenses_id = (int) $input['softwarelicenses_id'];

        $license = new SoftwareLicense();
        if (!$license->getFromDB($softwarelicenses_id)) {
            trigger_error(sprintf('Unable to load software license %d', $softwarelicenses_id), E_USER_WARNING);
            return false;
        }

        // Check quota if not unlimited (-1) and over-quota not allowed
        if (
            $license->getField('number') != -1
            && !$license->getField('allow_overquota')
        ) {
            // Count current assignments (users + items)
            $count = self::countForLicense($softwarelicenses_id);
            $count += Item_SoftwareLicense::countForLicense($softwarelicenses_id);

            if ($count >= $license->getField('number')) {
                Session::addMessageAfterRedirect(
                    __s('Maximum number of items reached for this license.'),
                    false,
                    ERROR
                );
                return false;
            }
        }

        return parent::prepareInputForAdd($input);
    }

    public static function getTypeName($nb = 0)
    {
        return SoftwareLicense::getTypeName($nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && SoftwareLicense::canView() && $item::class === User::class) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(SoftwareLicense_User::getTable(), ['users_id' => $item->fields['id']]);
            }
            return self::createTabEntry(
                SoftwareLicense_User::getTypeName(Session::getPluralNumber()),
                $nb,
                $item::class,
                'ti ti-package'
            );
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$withtemplate && SoftwareLicense::canView() && $item::class === User::class) {
            self::showForUser($item, $withtemplate);
        }

        return true;
    }

    public static function countForLicense(int $softwarelicenses_id): int
    {
        global $DB;

        $iterator = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => static::getTable(),
            'INNER JOIN' => [
                User::getTable() => [
                    'FKEY' => [
                        static::getTable() => 'users_id',
                        User::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                static::getTable() . '.softwarelicenses_id' => $softwarelicenses_id,
                User::getTable() . '.is_deleted' => 0,
            ],
        ]);

        return $iterator->current()['cpt'];
    }

    private static function showForUser(CommonDBTM $item, int $withtemplate = 0): void
    {
        $ID = $item->fields['id'];

        if (
            !SoftwareLicense::canView()
            || !$item->can($ID, READ)
        ) {
            return;
        }

        $canedit = $item->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($item);

        $licenses = [];
        $used     = [];
        foreach ($iterator as $data) {
            $licenses[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
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
                            {{ fields.dropdownField('SoftwareLicense', 'softwarelicenses_id', 0, __('Add a licence'), {
                                used: used,
                            }) }}
                            {% set btn %}
                                <button type="submit" name="add" class="btn btn-primary"><i class="ti ti-link"></i><span>{{ btn_label }}</span></button>
                            {% endset %}
                            {{ fields.htmlField('', btn, null) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $type_cache = [];
        foreach ($licenses as $data) {
            $entry = [
                'itemtype'  => self::class,
                'id'        => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'number'    => $data['number'],
            ];
            $license = new SoftwareLicense();
            $license->getFromResultSet($data);
            $entry['name'] = $license->getLink();
            if (!isset($entity_cache[$license->fields["entities_id"]])) {
                $entity_cache[$license->fields["entities_id"]] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $license->fields["entities_id"]
                );
            }
            $entry['entity'] = $entity_cache[$license->fields["entities_id"]];

            $software = new Software();
            $entry['software'] = $software->getFromDB($license->fields["softwares_id"])
                ? $software->getLink()
                : '-';

            if (!isset($type_cache[$license->fields["softwarelicensetypes_id"]])) {
                $type_cache[$license->fields["softwarelicensetypes_id"]] = Dropdown::getDropdownName(
                    "glpi_softwarelicensetypes",
                    $license->fields["softwarelicensetypes_id"]
                );
            }
            $entry['type'] = $type_cache[$license->fields["softwarelicensetypes_id"]];
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
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
            ],
        ]);
    }
}
