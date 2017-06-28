<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

namespace tests\units;

use \DbTestCase;

/* Test for inc/computer_softwareversion.class.php */

class Software extends DbTestCase {

   public function testTypeName() {
      $this->string(\Software::getTypeName(1))->isIdenticalTo('Software');
      $this->string(\Software::getTypeName(0))->isIdenticalTo('Software');
      $this->string(\Software::getTypeName(10))->isIdenticalTo('Software');
   }

   public function testGetMenuShorcut() {
      $this->string(\Software::getMenuShorcut())->isIdenticalTo('s');
   }

   public function testGetTabNameForItem() {
      $this->Login();

      $software = new \Software();
      $input    = ['name'         => 'soft1',
                   'entities_id'  => 0,
                   'is_recursive' => 1
                  ];
      $softwares_id = $software->add($input);
      $this->integer((int)$softwares_id)->isGreaterThan(0);
      $software->getFromDB($softwares_id);
      $this->string($software->getTabNameForItem($software, 1))->isEmpty();
   }

   public function defineTabs() {
      $this->Login();

      $software = new \Software();
      $tabs     = $software->defineTabs();
      $this->array($tabs)->hasSize(16);

      $_SESSION['glpiactiveprofile']['license'] = 0;
      $tabs = $software->defineTabs();
      $this->array($tabs)->hasSize(15);

      $_SESSION['glpiactiveprofile']['link'] = 0;
      $tabs = $software->defineTabs();
      $this->array($tabs)->hasSize(14);

      $_SESSION['glpiactiveprofile']['infocom'] = 0;
      $tabs = $software->defineTabs();
      $this->array($tabs)->hasSize(13);

      $_SESSION['glpiactiveprofile']['document'] = 0;
      $tabs = $software->defineTabs();
      $this->array($tabs)->hasSize(12);

   }

   public function testPrepareInputForUpdate() {
      $software = new \Software();
      $result   = $software->prepareInputForUpdate(['is_update' => 0]);
      $this->array($result)->isIdenticalTo(['is_update' => 0, 'softwares_id' => 0]);
   }

   public function testPrepareInputForAdd() {
      $software = new \Software();

      $input    = ['name' => 'A name', 'is_update' => 0, 'id' => 3, 'withtemplate' => 0];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0, '_oldID' => 3];

      $this->array($result)->isIdenticalTo($expected);

      $input    = ['name' => 'A name', 'is_update' => 0, 'withtemplate' => 0];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0];

      $this->array($result)->isIdenticalTo($expected);

      $input    = ['is_update'             => 0,
                   'withtemplate'          => 0,
                   'softwarecategories_id' => 3
                  ];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['is_update'             => 0,
                   'softwarecategories_id' => 3,
                   'softwares_id'          => 0];

      $this->array($result)->isIdenticalTo($expected);

   }

   public function testPrepareInputForAddWithCategory() {
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();
      $action   = new \RuleAction();

      //Create a software category
      $category      = new \SoftwareCategory();
      $categories_id = $category->importExternal('Application');

      $rules_id = $rule->add(['name'        => 'Add application category',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleSoftwareCategory',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $this->integer(
         (int)$criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'MySoft'
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$action->add(['rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'softwarecategories_id',
            'value'       => $categories_id
         ])
      )->isGreaterThan(0);

      $input    = ['name'             => 'MySoft',
                   'is_update'        => 0,
                   'entities_id'      => 0,
                   'comment'          => 'Comment'
                  ];

      $software = new \Software();
      $result   = $software->prepareInputForAdd($input);
      $expected = [
         'name'                  => 'MySoft',
         'is_update'             => 0,
         'entities_id'           => 0,
         'comment'               => 'Comment',
         'softwares_id'          => 0,
         'softwarecategories_id' => "$categories_id"
      ];

      $this->array($result)->isIdenticalTo($expected);
   }

   public function testPost_addItem() {
      global $CFG_GLPI;

      $this->Login();

      $software     = new \Software();
      $softwares_id = $software->add([
         'name'         => 'MySoft',
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);

      $this->variable($software->fields['is_template'])->isEqualTo(0);
      $this->string($software->fields['name'])->isIdenticalTo('MySoft');

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(0);
      $this->integer((int)countElementsInTable('glpi_contracts_items', $query))->isIdenticalTo(0);

      //Force creation of infocom when an asset is added
      $CFG_GLPI['auto_create_infocoms'] = 1;

      $softwares_id = $software->add([
         'name'         => 'MySoft2',
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(1);
   }

   public function testPost_addItemWithTemplate() {
      $this->Login();

      $software     = new \Software();
      $softwares_id = $software->add([
         'name'          => 'MyTemplate',
         'is_template'   => 1,
         'template_name' => 'template'
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);

      $infocom = new \Infocom();
      $this->integer(
         (int)$infocom->add([
            'itemtype' => 'Software',
            'items_id' => $softwares_id,
            'value'    => '500'
         ])
      )->isGreaterThan(0);

      $contract     = new \Contract();
      $contracts_id = $contract->add([
         'name'         => 'contract01',
         'entities_id'  => 0
      ]);
      $this->integer((int)$contracts_id)->isGreaterThan(0);

      $contract_item = new \Contract_Item();
      $this->integer(
         (int)$contract_item->add([
            'itemtype'     => 'Software',
            'items_id'     => $softwares_id,
            'contracts_id' => $contracts_id
         ])
      )->isGreaterThan(0);

      $softwares_id_2 = $software->add([
         'name'         => 'MySoft',
         'id'           => $softwares_id,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id_2)->isGreaterThan(0);

      $this->boolean($software->getFromDB($softwares_id_2))->isTrue();
      $this->variable($software->fields['is_template'])->isEqualTo(0);
      $this->string($software->fields['name'])->isIdenticalTo('MySoft');

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id_2'";
      $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(1);
      $this->integer((int)countElementsInTable('glpi_contracts_items', $query))->isIdenticalTo(1);
   }

   public function testCleanDBonPurge() {
      global $CFG_GLPI;
      $this->Login();

      //Force creation of infocom when an asset is added
      $CFG_GLPI['auto_create_infocoms'] = 1;

      $software     = new \Software();
      $softwares_id = $software->add([
         'name'         => 'MySoft',
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);

      $contract     = new \Contract();
      $contracts_id = $contract->add([
         'name'         => 'contract02',
         'entities_id'  => 0
      ]);
      $this->integer((int)$contracts_id)->isGreaterThan(0);

      $contract_item = new \Contract_Item();
      $this->integer(
         (int)$contract_item->add([
            'itemtype'     => 'Software',
            'items_id'     => $softwares_id,
            'contracts_id' => $contracts_id
         ])
      )->isGreaterThan(0);

      $this->boolean($software->delete(['id' => $softwares_id], true))->isTrue();
      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(0);
      $this->integer((int)countElementsInTable('glpi_contracts_items', $query))->isIdenticalTo(0);

      //TODO : test Change_Item, Item_Problem, Item_Project
   }

   /**
    * Creates a new software
    *
    * @return \Software
    */
   private function createSoft() {
      $software     = new \Software();
      $softwares_id = $software->add([
         'name'         => 'Software ' .$this->getUniqueString(),
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);
      $this->boolean($software->getFromDB($softwares_id))->isTrue();

      return $software;
   }

   public function testUpdateValidityIndicatorIncreaseDecrease() {
      $software = $this->createSoft();

      //create a license with 3 installations
      $license = new \SoftwareLicense();
      $license_id = $license->add([
         'name'         => 'a_software_license',
         'softwares_id' => $software->getID(),
         'entities_id'  => 0,
         'number'       => 3
      ]);
      $this->integer((int)$license_id)->isGreaterThan(0);

      //attach 2 licenses
      $license_computer = new \Computer_SoftwareLicense();
      foreach (['_test_pc01', '_test_pc02'] as $pcid) {
         $computer = getItemByTypeName('Computer', $pcid);
         $input_comp = [
            'softwarelicenses_id'   => $license_id,
            'computers_id'          => $computer->getID(),
            'is_deleted'            => 0,
            'is_dynamic'            => 0
         ];
         $this->integer((int)$license_computer->add($input_comp))->isGreaterThan(0);
      }

      $this->boolean($software->getFromDB($software->getID()))->isTrue();
      $this->variable($software->fields['is_valid'])->isEqualTo(1);

      //Descrease number to one
      $this->boolean(
         $license->update(['id' => $license->getID(), 'number' => 1])
      )->isTrue();
      \Software::updateValidityIndicator($software->getID());

      $software->getFromDB($software->getID());
      $this->variable($software->fields['is_valid'])->isEqualTo(0);

      //Increase number to ten
      $this->boolean(
         $license->update(['id' => $license->getID(), 'number' => 10])
      )->isTrue();
      \Software::updateValidityIndicator($software->getID());

      $software->getFromDB($software->getID());
      $this->variable($software->fields['is_valid'])->isEqualTo(1);
   }

   public function testGetEmpty() {
      global $CFG_GLPI;

      $software = new \Software();
      $CFG_GLPI['default_software_helpdesk_visible'] = 0;
      $software->getEmpty();
      $this->variable($software->fields['is_helpdesk_visible'])->isEqualTo(0);

      $CFG_GLPI['default_software_helpdesk_visible'] = 1;

      $software->getEmpty();
      $this->variable($software->fields['is_helpdesk_visible'])->isEqualTo(1);

   }

   public function testGetSpecificMassiveActions() {
      $this->Login();

      $software = new \Software();
      $result = $software->getSpecificMassiveActions();
      $this->array($result)->hasSize(4);

      $all_rights = $_SESSION['glpiactiveprofile']['software'];

      $_SESSION['glpiactiveprofile']['software'] = 0;
      $result = $software->getSpecificMassiveActions();
      $this->array($result)->isEmpty();

      $_SESSION['glpiactiveprofile']['software'] = READ;
      $result = $software->getSpecificMassiveActions();
      $this->array($result)->isEmpty();

      $_SESSION['glpiactiveprofile']['software'] = $all_rights;
      $_SESSION['glpiactiveprofile']['knowbase'] = 0;
      $result = $software->getSpecificMassiveActions();
      $this->array($result)->hasSize(3);

      $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = 0;
      $result = $software->getSpecificMassiveActions();
      $this->array($result)->hasSize(3);
   }

   public function testGetSearchOptionsNew() {
      $software = new \Software();
      $result   = $software->getSearchOptionsNew();
      $this->array($result)->hasSize(32);

      $this->Login();
      $result   = $software->getSearchOptionsNew();
      $this->array($result)->hasSize(41);
   }
}
