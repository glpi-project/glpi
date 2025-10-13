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
 * Class to link a certificate to an item
 */
class Certificate_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1    = "Certificate";
    public static $items_id_1    = 'certificates_id';
    public static $take_entity_1 = false;

    public static $itemtype_2    = 'itemtype';
    public static $items_id_2    = 'items_id';
    public static $take_entity_2 = true;

    /**
     * @since 9.2
     *
     **/
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param CommonDBTM $item
     */
    public static function cleanForItem(CommonDBTM $item)
    {
        $temp = new self();
        $temp->deleteByCriteria(['itemtype' => $item->getType(),
            'items_id' => $item->getField('id'),
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        if (!$withtemplate) {
            if (
                $item->getType() == 'Certificate'
                && count(Certificate::getTypes(false))
            ) {
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForMainItem($item);
                }
                return self::createTabEntry(_n('Associated item', 'Associated items', Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');
            } elseif (
                in_array($item->getType(), Certificate::getTypes(true))
                && Certificate::canView()
            ) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $count = self::countForItem($item);
                    return self::createTabEntry(text: Certificate::getTypeName(Session::getPluralNumber()), nb: $count, icon: Certificate::getIcon());
                }
                return self::createTabEntry(text: Certificate::getTypeName(Session::getPluralNumber()), icon: Certificate::getIcon());
            }
        }
        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Certificate) {
            self::showForCertificate($item);
        } elseif (in_array($item->getType(), Certificate::getTypes(true))) {
            self::showForItem($item);
        }
        return true;
    }


    /**
     * @param $certificates_id
     * @param $items_id
     * @param $itemtype
     * @return bool
     */
    public function getFromDBbyCertificatesAndItem($certificates_id, $items_id, $itemtype)
    {

        $certificate  = new self();
        $certificates = $certificate->find([
            'certificates_id' => $certificates_id,
            'itemtype'        => $itemtype,
            'items_id'        => $items_id,
        ]);
        if (count($certificates) != 1) {
            return false;
        }

        $cert         = current($certificates);
        $this->fields = $cert;

        return true;
    }

    /**
     * Link a certificate to an item
     *
     * @since 9.2
     * @param $values
     */
    public function addItem($values)
    {

        $this->add(['certificates_id' => $values["certificates_id"],
            'items_id'        => $values["items_id"],
            'itemtype'        => $values["itemtype"],
        ]);
    }

    /**
     * Delete a certificate link to an item
     *
     * @since 9.2
     *
     * @param integer $certificates_id the certificate ID
     * @param integer $items_id the item's id
     * @param string $itemtype the itemtype
     */
    public function deleteItemByCertificatesAndItem($certificates_id, $items_id, $itemtype)
    {

        if (
            $this->getFromDBbyCertificatesAndItem(
                $certificates_id,
                $items_id,
                $itemtype
            )
        ) {
            $this->delete(['id' => $this->fields["id"]]);
        }
    }

    /**
     * Show items linked to a certificate
     *
     * @since 9.2
     *
     * @param Certificate $certificate Certificate object
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showForCertificate(Certificate $certificate)
    {
        $instID = $certificate->fields['id'];
        if (!$certificate->can($instID, READ)) {
            return false;
        }
        $canedit = $certificate->can($instID, UPDATE);

        $types_iterator = self::getDistinctTypes($instID, ['itemtype' => Certificate::getTypes(true)]);

        if ($canedit) {
            $twig_params = [
                'btn_label' => _x('button', 'Associate'),
                'certificates_id' => $instID,
                'dropdown_params' => [
                    'items_id_name'   => 'items_id',
                    'itemtypes'       => Certificate::getTypes(true),
                    'entity_restrict' => ($certificate->fields['is_recursive']
                        ? getSonsOf(
                            'glpi_entities',
                            $certificate->fields['entities_id']
                        )
                        : $certificate->fields['entities_id']),
                    'checkright'      => true,
                ],
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" name="certificates_form" action="{{ 'Certificate_Item'|itemtype_form_path }}">
                        <input type="hidden" name="certificates_id" value="{{ certificates_id }}">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <div class="d-flex">
                            {{ fields.dropdownItemsFromItemtypes('items_id', '', dropdown_params|merge({
                                add_field_class: 'd-inline',
                                no_label: true,
                            })) }}
                            <div>
                                <button type="submit" name="add" class="btn btn-primary ms-3 mb-3">{{ btn_label }}</button>
                            </div>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $itemtype_name = $itemtype::getTypeName(1);

            if ($item::canView()) {
                $iterator = self::getTypeItems($instID, $itemtype);

                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        if (!$item->getFromDB($data["id"])) {
                            continue;
                        }

                        $entry = [
                            'itemtype' => static::class,
                            'id' => $data['linkid'],
                            'row_class' => $item->isDeleted() ? 'table-danger' : '',
                            'type' => $itemtype_name,
                            'name' => $item->getLink(),
                            'serial' => $data['serial'] ?? '-',
                            'otherserial' => $data['otherserial'] ?? '-',
                        ];

                        if (Session::isMultiEntitiesMode()) {
                            $entry['entity'] = $item->isEntityAssign() ? Dropdown::getDropdownName("glpi_entities", $data['entity']) : '-';
                        }
                        $entries[] = $entry;
                    }
                }
            }
        }

        $columns = [
            'type' => _n('Type', 'Types', 1),
            'name' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['serial'] = __('Serial number');
        $columns['otherserial'] = __('Inventory number');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    /**
     * Show certificates associated to an item
     *
     * @since 9.2
     *
     * @param CommonDBTM $item object for which associated certificates must be displayed
     * @param $withtemplate (default 0)
     *
     * @return bool
     */
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {

        $ID = $item->getField('id');

        if (
            $item->isNewID($ID)
            || !Certificate::canView()
            || !$item->can($item->fields['id'], READ)
        ) {
            return false;
        }

        $certificate  = new Certificate();

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        $canedit      = $item->canAddItem('Certificate');
        $is_recursive = $item->isRecursive();

        $iterator = self::getListForItem($item);

        $certificates = [];
        $used         = [];

        foreach ($iterator as $data) {
            $certificates[$data['linkid']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit && $withtemplate < 2) {
            $twig_params = [
                'btn_label' => _x('button', 'Associate'),
                'item' => $item,
                'is_recursive' => $is_recursive,
                'dropdown_params' => [
                    'entity' => $item->fields['entities_id'],
                    'is_recursive' => $is_recursive,
                    'used' => $used,
                ],
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" name="certificates_form" action="{{ 'Certificate_Item'|itemtype_form_path }}">
                        <input type="hidden" name="itemtype" value="{{ get_class(item) }}">
                        <input type="hidden" name="items_id" value="{{ item.getID() }}">
                        {% if get_class(item) is same as 'Ticket' %}
                            <input type="hidden" name="tickets_id" value="{{ item.getID() }}">
                        {% endif %}
                        <input type="hidden" name="entities_id" value="{{ item.getEntityID() }}">
                        <input type="hidden" name="is_recursive" value="{{ is_recursive ? 1 : 0 }}">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <div class="d-flex">
                            {{ fields.dropdownField('Certificate', 'certificates_id', null, '', dropdown_params|merge({
                                add_field_class: 'd-inline',
                                no_label: true,
                            })) }}
                            <div>
                                <button type="submit" name="add" class="btn btn-primary ms-3 mb-3">{{ btn_label }}</button>
                            </div>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $used = [];
        $entries = [];

        foreach ($certificates as $data) {
            $certificateID = $data["id"];
            $link = htmlescape(NOT_AVAILABLE);

            if ($certificate->getFromDB($certificateID)) {
                $link = $certificate->getLink();
            }
            $used[$certificateID] = $certificateID;

            $entry = [
                'itemtype' => static::class,
                'id' => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'name' => $link,
                'type' => Dropdown::getDropdownName("glpi_certificatetypes", $data["certificatetypes_id"]),
                'dns_name' => $data['dns_name'],
                'dns_suffix' => $data['dns_suffix'],
                'date_creation' => $data['date_creation'],
                'status' => Dropdown::getDropdownName("glpi_states", $data["states_id"]),
            ];
            if (Session::isMultiEntitiesMode()) {
                $entry['entity'] = Dropdown::getDropdownName("glpi_entities", $data['entities_id']);
            }

            $expiration = htmlescape(Html::convDate($data["date_expiration"]));
            if (
                !empty($data["date_expiration"])
                && $data["date_expiration"] <= date('Y-m-d')
            ) {
                $expiration = "<span class='table-deleted'>{$expiration}</span>";
            } elseif (empty($data["date_expiration"])) {
                $expiration = __s('Does not expire');
            }
            $entry['date_expiration'] = $expiration;
            $entries[] = $entry;
        }

        $columns = [
            'name' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['type'] = _n('Type', 'Types', 1);
        $columns['dns_name'] = __('DNS name');
        $columns['dns_suffix'] = __('DNS suffix');
        $columns['date_creation'] = __('Creation date');
        $columns['date_expiration'] = __('Expiration date');
        $columns['status'] = __('Status');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                'date_creation' => 'date',
                'date_expiration' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && $withtemplate < 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);

        return true;
    }
}
