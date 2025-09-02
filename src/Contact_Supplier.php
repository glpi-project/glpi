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

use function Safe\preg_match;

class Contact_Supplier extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contact';
    public static $items_id_1 = 'contacts_id';

    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';


    public static function getTypeName($nb = 0)
    {
        return _n('Link Contact/Supplier', 'Links Contact/Supplier', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        if (!$withtemplate && Session::haveRight("contact_enterprise", READ)) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  self::countForItem($item);
                    }
                    return self::createTabEntry(Contact::getTypeName(Session::getPluralNumber()), $nb, $item::getType());

                case 'Contact':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Supplier::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch ($item::class) {
            case Supplier::class:
                self::showForSupplier($item);
                break;

            case Contact::class:
                self::showForContact($item);
                break;
        }
        return true;
    }

    /**
     * Print the HTML array for entreprises on the current contact
     *
     * @return void
     */
    public static function showForContact(Contact $contact)
    {
        $instID = (int) $contact->fields['id'];

        if (!$contact->can($instID, READ)) {
            return;
        }

        $canedit = $contact->can($instID, UPDATE);

        $iterator = self::getListForItem($contact);

        $suppliers = [];
        $used = [];
        foreach ($iterator as $data) {
            $suppliers[$data['linkid']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/management/contact_supplier.html.twig', [
                'peer' => $contact,
                'used' => $used,
            ]);
        }

        $entries = [];
        $suppliertype_cache = [];
        $entity_cache = [];
        $supplier = new Supplier();

        foreach ($suppliers as $data) {
            $website           = $data["website"];

            if (!empty($website)) {
                $website = $data["website"];

                if (!preg_match("?https*://?", $website)) {
                    $website = "http://" . $website;
                }
                $website = "<a target=_blank href='" . htmlescape($website) . "'>" . htmlescape($data["website"]) . "</a>";
            }
            $supplier->getFromDB($data["id"]);
            if (!isset($suppliertype_cache[$data["suppliertypes_id"]])) {
                $suppliertype_cache[$data["suppliertypes_id"]] = Dropdown::getDropdownName(
                    "glpi_suppliertypes",
                    $data["suppliertypes_id"]
                );
            }

            $entry = [
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'itemtype' => static::class,
                'id' => $data["linkid"],
                'supplier' => $supplier->getLink(),
                'suppliertypes_id' => $suppliertype_cache[$data["suppliertypes_id"]],
                'phonenumber' => $data["phonenumber"],
                'fax' => $data["fax"],
                'website' => $website,
            ];
            if (Session::isMultiEntitiesMode()) {
                if (!isset($entity_cache[$data["entity"]])) {
                    $entity_cache[$data["entity"]] = Dropdown::getDropdownName("glpi_entities", $data["entity"]);
                }
                $entry['entity'] = $entity_cache[$data["entity"]];
            }
            $entries[] = $entry;
        }

        $columns = [
            'supplier' => Supplier::getTypeName(1),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['suppliertypes_id'] = SupplierType::getTypeName(1);
        $columns['phonenumber'] = Phone::getTypeName(1);
        $columns['fax'] = __('Fax');
        $columns['website'] = __('Website');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'supplier' => 'raw_html',
                'website' => 'raw_html',
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
     * Show contacts associated to an enterprise
     *
     * @param Supplier $supplier
     *
     * @return void
     */
    public static function showForSupplier(Supplier $supplier)
    {
        $instID = $supplier->fields['id'];
        if (!$supplier->can($instID, READ)) {
            return;
        }
        $canedit = $supplier->can($instID, UPDATE);

        $iterator = self::getListForItem($supplier);

        $contacts = [];
        $used = [];
        foreach ($iterator as $data) {
            $contacts[$data['linkid']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/management/contact_supplier.html.twig', [
                'peer' => $supplier,
                'used' => $used,
            ]);
        }

        $entries = [];
        $entity_cache = [];
        $contacttype_cache = [];
        $contact = new Contact();
        foreach ($contacts as $data) {
            $contact->getFromDB($data["id"]);
            $email_link = "<a href='mailto:" . htmlescape($data["email"]) . "'>" . htmlescape($data["email"]) . "</a>";

            if (!isset($contacttype_cache[$data["contacttypes_id"]])) {
                $contacttype_cache[$data["contacttypes_id"]] = Dropdown::getDropdownName(
                    "glpi_contacttypes",
                    $data["contacttypes_id"]
                );
            }

            $entry = [
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'itemtype' => static::class,
                'id' => $data["linkid"],
                'contact' => $contact->getLink(),
                'phone' => $data["phone"],
                'phone2' => $data["phone2"],
                'mobile' => $data["mobile"],
                'fax' => $data["fax"],
                'email' => $email_link,
                'contacttypes_id' => $contacttype_cache[$data["contacttypes_id"]],
            ];
            if (Session::isMultiEntitiesMode()) {
                if (!isset($entity_cache[$data["entity"]])) {
                    $entity_cache[$data["entity"]] = Dropdown::getDropdownName("glpi_entities", $data["entity"]);
                }
                $entry['entity'] = $entity_cache[$data["entity"]];
            }
            $entries[] = $entry;
        }

        $columns = [
            'contact' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['phone'] = Phone::getTypeName(1);
        $columns['phone2'] = __('Phone 2');
        $columns['mobile'] = __('Mobile phone');
        $columns['fax'] = __('Fax');
        $columns['email'] = _n('Email', 'Emails', 1);
        $columns['contacttypes_id'] = _n('Type', 'Types', 1);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'contact' => 'raw_html',
                'email' => 'raw_html',
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
}
