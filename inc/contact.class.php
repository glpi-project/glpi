<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Sabre\VObject;

/**
 * Contact class
**/
class Contact extends CommonDBTM{

   // From CommonDBTM
   public $dohistory           = true;

   static $rightname           = 'contact_enterprise';
   protected $usenotepad       = true;



   static function getTypeName($nb = 0) {
      return _n('Contact', 'Contacts', $nb);
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Contact_Supplier::class,
            ProjectTaskTeam::class,
            ProjectTeam::class,
         ]
      );
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Contact_Supplier', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Get address of the contact (company one)
    *
    *@return string containing the address
   **/
   function getAddress() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_suppliers.name',
            'glpi_suppliers.address',
            'glpi_suppliers.postcode',
            'glpi_suppliers.town',
            'glpi_suppliers.state',
            'glpi_suppliers.country'
         ],
         'FROM'         => 'glpi_suppliers',
         'INNER JOIN'   => [
            'glpi_contacts_suppliers'  => [
               'ON' => [
                  'glpi_contacts_suppliers'  => 'suppliers_id',
                  'glpi_suppliers'           => 'id'
               ]
            ]
         ],
         'WHERE'        => ['contacts_id' => $this->fields['id']]
      ]);

      if ($data = $iterator->next()) {
         return $data;
      }
   }


   /**
    * Get website of the contact (company one)
    *
    *@return string containing the website
   **/
   function getWebsite() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_suppliers.website AS website'
         ],
         'FROM'         => 'glpi_suppliers',
         'INNER JOIN'   => [
            'glpi_contacts_suppliers'  => [
               'ON' => [
                  'glpi_contacts_suppliers'  => 'suppliers_id',
                  'glpi_suppliers'           => 'id'
               ]
            ]
         ],
         'WHERE'        => ['contacts_id' => $this->fields['id']]
      ]);

      if ($data = $iterator->next()) {
         return $data['website'];
      }
      return '';
   }


   /**
    * Print the contact form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return true
   **/
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Surname')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='4' class='middle right'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='4'>";
      echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('First name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "firstname");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Phone')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "phone");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Phone 2')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "phone2");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Mobile phone')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "mobile");
      echo "</td>";
      echo "<td class='middle'>".__('Address')."</td>";
      echo "<td class='middle'>";
      echo "<textarea cols='37' rows='3' name='address'>".$this->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Fax')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "fax");
      echo "</td>";
      echo "<td>".__('Postal code')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "postcode", ['size' => 10]);
      echo "&nbsp;&nbsp;". __('City'). "&nbsp;";
      Html::autocompletionTextField($this, "town", ['size' => 23]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Email', 'Emails', 1)."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "email");
      echo "</td>";
      echo "<td>"._x('location', 'State')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ContactType::dropdown(['value' => $this->fields["contacttypes_id"]]);
      echo "</td>";
      echo "<td>".__('Country')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "country");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . _x('person', 'Title') . "</td><td>";
      UserTitle::dropdown(['value' => $this->fields["usertitles_id"]]);
      echo "<td>&nbsp;</td><td class='center'>";
      if ($ID > 0) {
         echo "<a target=''_blank' href='".$this->getFormURL().
                "?getvcard=1&amp;id=$ID'>".__('Vcard')."</a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['Contact_Supplier'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add a supplier');
      }

      return $actions;
   }


   function getRawName() {

      if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
         return formatUserName('',
                               '',
                               (isset($this->fields["name"]) ? $this->fields["name"] : ''),
                               (isset($this->fields["firstname"]) ? $this->fields["firstname"] : ''));
      }
      return '';
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Last name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'firstname',
         'name'               => __('First name'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'phone',
         'name'               => __('Phone'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'phone2',
         'name'               => __('Phone 2'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'mobile',
         'name'               => __('Mobile phone'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'fax',
         'name'               => __('Fax'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'email',
         'name'               => _n('Email', 'Emails', 1),
         'datatype'           => 'email'
      ];

      $tab[] = [
         'id'                 => '82',
         'table'              => $this->getTable(),
         'field'              => 'address',
         'name'               => __('Address')
      ];

      $tab[] = [
         'id'                 => '83',
         'datatype'           => 'string',
         'table'              => $this->getTable(),
         'field'              => 'postcode',
         'name'               => __('Postal code')
      ];

      $tab[] = [
         'id'                 => '84',
         'table'              => $this->getTable(),
         'field'              => 'town',
         'name'               => __('City'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '85',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => _x('location', 'State'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '87',
         'table'              => $this->getTable(),
         'field'              => 'country',
         'name'               => __('Country'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_contacttypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '81',
         'table'              => 'glpi_usertitles',
         'field'              => 'name',
         'name'               => __('Title'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_suppliers',
         'field'              => 'name',
         'name'               => _n('Associated supplier', 'Associated suppliers', Session::getPluralNumber()),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contacts_suppliers',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   /**
    * Generate the Vcard for the current Contact
    *
    * @return void
    */
   function generateVcard() {

      if (!$this->can($this->fields['id'], READ)) {
         return;
      }

      // build the Vcard
      $vcard = new VObject\Component\VCard([
         'N'     => [$this->fields["name"], $this->fields["firstname"]],
         'EMAIL' => $this->fields["email"],
         'NOTE'  => $this->fields["comment"],
      ]);

      $vcard->add('TEL', $this->fields["phone"], ['type' => 'PREF;WORK;VOICE']);
      $vcard->add('TEL', $this->fields["phone2"], ['type' => 'HOME;VOICE']);
      $vcard->add('TEL', $this->fields["mobile"], ['type' => 'WORK;CELL']);
      $vcard->add('URL', $this->GetWebsite(), ['type' => 'WORK']);

      $addr = $this->GetAddress();
      if (is_array($addr)) {
         $addr_string = implode(";", array_filter($addr));
         $vcard->add('ADR', $addr_string, ['type' => 'WORK;POSTAL']);
      }

      // send the  VCard
      $output   = $vcard->serialize();
      $filename = $this->fields["name"]."_".$this->fields["firstname"].".vcf";

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      @Header("Content-Length: ".Toolbox::strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/x-vcard; charset=UTF-8");

      echo $output;
   }

}
