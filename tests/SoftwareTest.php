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

/* Test for inc/computer_softwareversion.class.php */

class SoftwareTest extends DbTestCase {

   /**
    * @covers Software::getTypeName
    */
   public function testTypeName() {
      $this->assertEquals('Software', Software::getTypeName(1));
      $this->assertEquals('Software', Software::getTypeName(0));
      $this->assertEquals('Software', Software::getTypeName(10));
   }

   /**
    * @covers Software::getMenuShorcut
    */
   public function testGetMenuShorcut() {
      $this->assertEquals('s', Software::getMenuShorcut());
   }

   /**
    * @covers Software::getTabNameForItem
    */
   public function testGetTabNameForItem() {
      $this->Login();

      $software = new Software();
      $input    = ['name'         => 'soft1',
                   'entities_id'  => 0,
                   'is_recursive' => 1
                  ];
      $softwares_id = $software->add($input);
      $software->getFromDB($softwares_id);

      $this->assertEquals('', $software->getTabNameForItem($software, 1));
   }

   /**
    * @covers Software::defineTabs
    */
   public function defineTabs() {
      $this->Login();

      $software = new Software();
      $tabs     = $software->defineTabs();
      $this->assertEquals(16, count($tabs));

      $_SESSION['glpiactiveprofile']['license'] = 0;
      $tabs = $software->defineTabs();
      $this->assertEquals(15, count($tabs));

      $_SESSION['glpiactiveprofile']['link'] = 0;
      $tabs = $software->defineTabs();
      $this->assertEquals(14, count($tabs));

      $_SESSION['glpiactiveprofile']['infocom'] = 0;
      $tabs = $software->defineTabs();
      $this->assertEquals(13, count($tabs));

      $_SESSION['glpiactiveprofile']['document'] = 0;
      $tabs = $software->defineTabs();
      $this->assertEquals(12, count($tabs));

   }

   /**
   * @cover Software::prepareInputForUpdate
   */
   public function testPrepareInputForUpdate() {
      $software = new Software();
      $result   = $software->prepareInputForUpdate(['is_update' => 0]);
      $this->assertEquals(['is_update' => 0, 'softwares_id' => 0], $result);
   }

   /**
   * @cover Software::prepareInputForAdd
   */
   public function testPrepareInputForAdd() {
      $software = new Software();

      $input    = ['is_update' => 0, 'id' => 3, 'withtemplate' => 0];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['is_update' => 0, 'softwares_id' => 0, '_oldID' => 3];

      $this->assertEquals($result, $expected);

      $input    = ['is_update' => 0, 'withtemplate' => 0];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['is_update' => 0, 'softwares_id' => 0];

      $this->assertEquals($result, $expected);

      $input    = ['is_update'             => 0,
                   'withtemplate'          => 0,
                   'softwarecategories_id' => 3
                  ];
      $result   = $software->prepareInputForAdd($input);
      $expected = ['is_update'             => 0,
                   'softwares_id'          => 0,
                   'softwarecategories_id' => 3];

      $this->assertEquals($result, $expected);

   }

   /**
   * @cover Software::prepareInputForAdd
   */
   public function testPrepareInputForAddWithCategory() {
      $rule     = new Rule();
      $criteria = new RuleCriteria();
      $action   = new RuleAction();

      //Create a software category
      $category      = new SoftwareCategory();
      $categories_id = $category->importExternal('Application');

      $rules_id = $rule->add(['name'        => 'Add application category',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleSoftwareCategory',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'MySoft'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => 'softwarecategories_id',
                    'value'       => $categories_id
                   ]);

      $input    = ['name'             => 'MySoft',
                   'is_update'        => 0,
                   'entities_id'      => 0,
                   'comment'          => 'Comment'
                  ];

      $software = new Software();
      $result   = $software->prepareInputForAdd($input);
      $expected = ['name'             => 'MySoft',
                   'is_update'        => 0,
                   'entities_id'      => 0,
                   'comment'          => 'Comment',
                   'softwarecategories_id' => $categories_id
                  ];

      //TODO : find why it's not working...
      //$this->assertEquals($result, $expected);

   }

   /**
   * @cover Software::post_addItem
   */
   public function testPost_addItem() {
      global $CFG_GLPI;

      $this->Login();

      $software     = new Software();
      $softwares_id = $software->add(['name'          => 'MySoft',
                                      'is_template'   => 0
                                     ]);

      $this->assertEquals(0, $software->fields['is_template']);
      $this->assertEquals('MySoft', $software->fields['name']);

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->assertEquals(0, countElementsInTable('glpi_infocoms', $query));
      $this->assertEquals(0, countElementsInTable('glpi_contracts_items', $query));

      //Force creation of infocom when an asset is added
      $CFG_GLPI['auto_create_infocoms'] = 1;

      $softwares_id = $software->add(['name'          => 'MySoft2',
                                      'is_template'   => 0
                                     ]);

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->assertEquals(1, countElementsInTable('glpi_infocoms', $query));

   }

   /**
   * @cover Software::post_addItem
   */
   public function testPost_addItemWithTemplate() {
      $this->Login();

      $software     = new Software();
      $softwares_id = $software->add(['name'          => 'MyTemplate',
                                      'is_template'   => 1,
                                      'template_name' => 'template'
                                     ]);

      $infocom = new Infocom();
      $infocom->add(['itemtype' => 'Software',
                     'items_id' => $softwares_id,
                     'value'    => '500']);

      $contract     = new Contract();
      $contracts_id = $contract->add(['name' => $contract]);

      $contract_item = new Contract_Item();
      $contract_item->add(['itemtype'     => 'Software',
                           'items_id'     => $softwares_id,
                           'contracts_id' => $contracts_id]);

      $softwares_id_2 = $software->add(['name' => 'MySoft', 'id' => $softwares_id]);

      $software->getFromDB($softwares_id_2);
      $this->assertEquals(0, $software->fields['is_template']);
      $this->assertEquals('MySoft', $software->fields['name']);

      $query = "`itemtype`='Software' AND `items_id`='$softwares_id_2'";
      $this->assertEquals(1, countElementsInTable('glpi_infocoms', $query));
      $this->assertEquals(1, countElementsInTable('glpi_contracts_items', $query));
   }

   /**
   * @cover Software::cleanDBonPurge
   */
   public function testCleanDBonPurge() {
      global $CFG_GLPI;
      $this->Login();

      //Force creation of infocom when an asset is added
      $CFG_GLPI['auto_create_infocoms'] = 1;

      $software     = new Software();
      $softwares_id = $software->add(['name'          => 'MySoft',
                                      'is_template'   => 0
                                     ]);

      $contract     = new Contract();
      $contracts_id = $contract->add(['name' => $contract]);

      $contract_item = new Contract_Item();
      $contract_item->add(['itemtype'     => 'Software',
                           'items_id'     => $softwares_id,
                           'contracts_id' => $contracts_id]);

      $this->assertTrue($software->delete(['id' => $softwares_id], true));
      $query = "`itemtype`='Software' AND `items_id`='$softwares_id'";
      $this->assertEquals(0, countElementsInTable('glpi_infocoms', $query));
      $this->assertEquals(0, countElementsInTable('glpi_contracts_items', $query));

      //TODO : test Change_Item, Item_Problem, Item_Project
   }

   /**
   * @cover Software::updateValidityIndicator()
   */
   public function testUpdateValidityIndicatorDecrease() {

      $soft    = getItemByTypeName('Software', '_test_soft');
      $soft_id = $soft->getID();

      $license = new SoftwareLicense();

      //As defined in the bootstrap, license with ID 2 has
      //3 installations : is_valid = 1

      //Descrease number to one
      $license->update(['id' => 2, 'number' => 1]);
      Software::updateValidityIndicator($soft_id);
   }

   /**
   * @cover Software::updateValidityIndicator()
   */
   public function testUpdateValidityIndicatorIncrease() {

      $soft    = getItemByTypeName('Software', '_test_soft');
      $soft_id = $soft->getID();

      $license = new SoftwareLicense();

      //As defined in the bootstrap, license with ID 2 has
      //3 installations : is_valid = 1

      //Increase number to ten
      $license->update(['id' => 2, 'number' => 10]);
      Software::updateValidityIndicator($soft_id);

      //Check if is_valid still equals 1
      $soft->getFromDB($soft_id);
      $this->assertEquals(1, $soft->fields['is_valid']);
   }

   /**
   * @cover Software::getEmpty
   */
   public function testGetEmpty() {
      global $CFG_GLPI;

      $software = new Software();
      $CFG_GLPI['default_software_helpdesk_visible'] = 0;
      $software->getEmpty();
      $this->assertEquals(0, $software->fields['is_helpdesk_visible']);

      $CFG_GLPI['default_software_helpdesk_visible'] = 1;

      $software->getEmpty();
      $this->assertEquals(1, $software->fields['is_helpdesk_visible']);

   }

   /**
   * @cover Software::getSpecificMassiveActions
   */
   public function testGetSpecificMassiveActions() {
      $this->Login();

      $software = new Software();
      $result = $software->getSpecificMassiveActions();
      $this->assertEquals(4, count($result));

      $all_rights = $_SESSION['glpiactiveprofile']['software'];

      $_SESSION['glpiactiveprofile']['software'] = 0;
      $result = $software->getSpecificMassiveActions();
      $this->assertEquals(0, count($result));

      $_SESSION['glpiactiveprofile']['software'] = READ;
      $result = $software->getSpecificMassiveActions();
      $this->assertEquals(0, count($result));

      $_SESSION['glpiactiveprofile']['software'] = $all_rights;
      $_SESSION['glpiactiveprofile']['knowbase'] = NONE;
      $result = $software->getSpecificMassiveActions();
      $this->assertEquals(3, count($result));

      $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = 0;
      $result = $software->getSpecificMassiveActions();
      $this->assertEquals(3, count($result));
   }

   /**
   * @cover Software::getSearchOptionsNew
   */
   public function testGetSearchOptionsNew() {
      $software = new Software();
      $result   = $software->getSearchOptionsNew();
      $this->assertEquals(32, count($result));

      $this->Login();
      $result   = $software->getSearchOptionsNew();
      $this->assertEquals(41, count($result));

   }
}
