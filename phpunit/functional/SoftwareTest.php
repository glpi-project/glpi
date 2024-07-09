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

namespace tests\units;

use DbTestCase;

/* Test for inc/software.class.php */

class SoftwareTest extends DbTestCase
{
    public function testTypeName()
    {
        $this->assertSame('Software', \Software::getTypeName(1));
        $this->assertSame('Software', \Software::getTypeName(0));
        $this->assertSame('Software', \Software::getTypeName(10));
    }

    public function testGetMenuShorcut()
    {
        $this->assertSame('s', \Software::getMenuShorcut());
    }

    public function testGetTabNameForItem()
    {
        $this->login();

        $software = new \Software();
        $input    = ['name'         => 'soft1',
            'entities_id'  => 0,
            'is_recursive' => 1
        ];
        $softwares_id = $software->add($input);
        $this->assertGreaterThan(0, (int)$softwares_id);
        $software->getFromDB($softwares_id);
        $this->assertEmpty($software->getTabNameForItem($software, 1));
    }

    public function defineTabs()
    {
        $this->login();

        $software = new \Software();
        $tabs     = $software->defineTabs();
        $this->assertCount(16, $tabs);

        $_SESSION['glpiactiveprofile']['license'] = 0;
        $tabs = $software->defineTabs();
        $this->assertCount(15, $tabs);

        $_SESSION['glpiactiveprofile']['link'] = 0;
        $tabs = $software->defineTabs();
        $this->assertCount(14, $tabs);

        $_SESSION['glpiactiveprofile']['infocom'] = 0;
        $tabs = $software->defineTabs();
        $this->assertCount(13, $tabs);

        $_SESSION['glpiactiveprofile']['document'] = 0;
        $tabs = $software->defineTabs();
        $this->assertCount(12, $tabs);
    }

    public function testPrepareInputForUpdate()
    {
        $software = new \Software();
        $result   = $software->prepareInputForUpdate(['is_update' => 0]);
        $this->assertSame(['is_update' => 0, 'softwares_id' => 0], $result);
    }

    public function testPrepareInputForAdd()
    {
        $software = new \Software();

        $input    = ['name' => 'A name', 'is_update' => 0, 'id' => 3, 'withtemplate' => 0];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0, '_oldID' => 3, 'softwarecategories_id' => 0];

        $this->assertSame($expected, $result);

        $input    = ['name' => 'A name', 'is_update' => 0, 'withtemplate' => 0];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0, 'softwarecategories_id' => 0];

        $this->assertSame($expected, $result);

        $input    = ['is_update'             => 0,
            'withtemplate'          => 0,
            'softwarecategories_id' => 3
        ];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['is_update'             => 0,
            'softwarecategories_id' => 3,
            'softwares_id'          => 0
        ];

        $this->assertSame($expected, $result);
    }

    public function testPrepareInputForAddWithCategory()
    {
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
        $this->assertGreaterThan(0, (int)$rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'MySoft'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add(['rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'softwarecategories_id',
                'value'       => $categories_id
            ])
        );

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

        $this->assertSame($expected, $result);
    }

    public function testPost_addItem()
    {
        global $CFG_GLPI;

        $this->login();

        $software     = new \Software();
        $softwares_id = $software->add([
            'name'         => 'MySoft',
            'is_template'  => 0,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id);

        $this->assertEquals(0, $software->fields['is_template']);
        $this->assertSame('MySoft', $software->fields['name']);

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
        $this->assertSame(0, (int)countElementsInTable('glpi_infocoms', $query));
        $this->assertSame(0, (int)countElementsInTable('glpi_contracts_items', $query));

        //Force creation of infocom when an asset is added
        $CFG_GLPI['auto_create_infocoms'] = 1;

        $softwares_id = $software->add([
            'name'         => 'MySoft2',
            'is_template'  => 0,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id);

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
        $this->assertSame(1, (int)countElementsInTable('glpi_infocoms', $query));
    }

    public function testPost_addItemWithTemplate()
    {
        $this->login();

        $software     = new \Software();
        $softwares_id = $software->add([
            'name'          => 'MyTemplate',
            'is_template'   => 1,
            'template_name' => 'template'
        ]);
        $this->assertGreaterThan(0, $softwares_id);

        $infocom = new \Infocom();
        //No idea why, but infocom already exists with phpunit
        $infocom->getFromDBByCrit(['itemtype' => 'Software', 'items_id' => $softwares_id]);
        $infocom->delete(['id' => $infocom->getID()], true);
        $this->assertGreaterThan(
            0,
            $infocom->add([
                'itemtype' => 'Software',
                'items_id' => $softwares_id,
                'value'    => '500'
            ])
        );

        $contract     = new \Contract();
        $contracts_id = $contract->add([
            'name'         => 'contract01',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$contracts_id);

        $contract_item = new \Contract_Item();
        $this->assertGreaterThan(
            0,
            $contract_item->add([
                'itemtype'     => 'Software',
                'items_id'     => $softwares_id,
                'contracts_id' => $contracts_id
            ])
        );

        $softwares_id_2 = $software->add([
            'name'         => 'MySoft',
            'id'           => $softwares_id,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id_2);

        $this->assertTrue($software->getFromDB($softwares_id_2));
        $this->assertEquals(0, $software->fields['is_template']);
        $this->assertSame('MySoft', $software->fields['name']);

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id_2];
        $this->assertSame(1, (int)countElementsInTable('glpi_infocoms', $query));
        $this->assertSame(1, (int)countElementsInTable('glpi_contracts_items', $query));
    }

    public function testCleanDBonPurge()
    {
        global $CFG_GLPI;
        $this->login();

        //Force creation of infocom when an asset is added
        $CFG_GLPI['auto_create_infocoms'] = 1;

        $software     = new \Software();
        $softwares_id = $software->add([
            'name'         => 'MySoft',
            'is_template'  => 0,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id);

        $contract     = new \Contract();
        $contracts_id = $contract->add([
            'name'         => 'contract02',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$contracts_id);

        $contract_item = new \Contract_Item();
        $this->assertGreaterThan(
            0,
            $contract_item->add([
                'itemtype'     => 'Software',
                'items_id'     => $softwares_id,
                'contracts_id' => $contracts_id
            ])
        );

        $this->assertTrue($software->delete(['id' => $softwares_id], true));
        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
        $this->assertSame(0, (int)countElementsInTable('glpi_infocoms', $query));
        $this->assertSame(0, (int)countElementsInTable('glpi_contracts_items', $query));

       //TODO : test Change_Item, Item_Problem, Item_Project
    }

    /**
     * Creates a new software
     *
     * @return \Software
     */
    private function createSoft()
    {
        $software     = new \Software();
        $softwares_id = $software->add([
            'name'         => 'Software ' . $this->getUniqueString(),
            'is_template'  => 0,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id);
        $this->assertTrue($software->getFromDB($softwares_id));

        return $software;
    }

    public function testUpdateValidityIndicatorIncreaseDecrease()
    {
        $software = $this->createSoft();

        //create a license with 3 installations
        $license = new \SoftwareLicense();
        $license_id = $license->add([
            'name'         => 'a_software_license',
            'softwares_id' => $software->getID(),
            'entities_id'  => 0,
            'number'       => 3
        ]);
        $this->assertGreaterThan(0, (int)$license_id);

        //attach 2 licenses
        $license_computer = new \Item_SoftwareLicense();
        foreach (['_test_pc01', '_test_pc02'] as $pcid) {
            $computer = getItemByTypeName('Computer', $pcid);
            $input_comp = [
                'softwarelicenses_id'   => $license_id,
                'items_id'              => $computer->getID(),
                'itemtype'              => 'Computer',
                'is_deleted'            => 0,
                'is_dynamic'            => 0
            ];
            $this->assertGreaterThan(0, (int)$license_computer->add($input_comp));
        }

        $this->assertTrue($software->getFromDB($software->getID()));
        $this->assertEquals(1, $software->fields['is_valid']);

        //Decrease number to one
        $this->assertTrue(
            $license->update(['id' => $license->getID(), 'number' => 1])
        );
        \Software::updateValidityIndicator($software->getID());

        $software->getFromDB($software->getID());
        $this->assertEquals(0, $software->fields['is_valid']);

        //Increase number to ten
        $this->assertTrue(
            $license->update(['id' => $license->getID(), 'number' => 10])
        );
        \Software::updateValidityIndicator($software->getID());

        $software->getFromDB($software->getID());
        $this->assertEquals(1, $software->fields['is_valid']);
    }

    public function testGetEmpty()
    {
        global $CFG_GLPI;

        $software = new \Software();
        $CFG_GLPI['default_software_helpdesk_visible'] = 0;
        $software->getEmpty();
        $this->assertEquals(0, $software->fields['is_helpdesk_visible']);

        $CFG_GLPI['default_software_helpdesk_visible'] = 1;

        $software->getEmpty();
        $this->assertEquals(1, $software->fields['is_helpdesk_visible']);
    }

    public function testGetSpecificMassiveActions()
    {
        $this->login();

        $software = new \Software();
        $result = $software->getSpecificMassiveActions();
        $this->assertCount(5, $result);

        $all_rights = $_SESSION['glpiactiveprofile']['software'];

        $_SESSION['glpiactiveprofile']['software'] = 0;
        $result = $software->getSpecificMassiveActions();
        $this->assertEmpty($result);

        $_SESSION['glpiactiveprofile']['software'] = READ;
        $result = $software->getSpecificMassiveActions();
        $this->assertEmpty($result);

        $_SESSION['glpiactiveprofile']['software'] = $all_rights;
        $_SESSION['glpiactiveprofile']['knowbase'] = 0;
        $result = $software->getSpecificMassiveActions();
        $this->assertCount(4, $result);

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = 0;
        $result = $software->getSpecificMassiveActions();
        $this->assertCount(4, $result);
    }

    public function testGetSearchOptionsNew()
    {
        $software = new \Software();
        $result   = $software->rawSearchOptions();
        $this->assertCount(43, $result);

        $this->login();
        $result   = $software->rawSearchOptions();
        $this->assertCount(59, $result);
    }
}
