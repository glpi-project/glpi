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

class Software extends DbTestCase
{
    public function testTypeName()
    {
        $this->string(\Software::getTypeName(1))->isIdenticalTo('Software');
        $this->string(\Software::getTypeName(0))->isIdenticalTo('Software');
        $this->string(\Software::getTypeName(10))->isIdenticalTo('Software');
    }

    public function testGetMenuShorcut()
    {
        $this->string(\Software::getMenuShorcut())->isIdenticalTo('s');
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
        $this->integer((int)$softwares_id)->isGreaterThan(0);
        $software->getFromDB($softwares_id);
        $this->string($software->getTabNameForItem($software, 1))->isEmpty();
    }

    public function defineTabs()
    {
        $this->login();

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

    public function testPrepareInputForUpdate()
    {
        $software = new \Software();
        $result   = $software->prepareInputForUpdate(['is_update' => 0]);
        $this->array($result)->isIdenticalTo(['is_update' => 0, 'softwares_id' => 0]);
    }

    public function testPrepareInputForAdd()
    {
        $software = new \Software();

        $input    = ['name' => 'A name', 'is_update' => 0, 'id' => 3, 'withtemplate' => 0];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0, '_oldID' => 3, 'softwarecategories_id' => 0];

        $this->array($result)->isIdenticalTo($expected);

        $input    = ['name' => 'A name', 'is_update' => 0, 'withtemplate' => 0];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['name' => 'A name', 'is_update' => 0, 'softwares_id' => 0, 'softwarecategories_id' => 0];

        $this->array($result)->isIdenticalTo($expected);

        $input    = ['is_update'             => 0,
            'withtemplate'          => 0,
            'softwarecategories_id' => 3
        ];
        $result   = $software->prepareInputForAdd($input);
        $expected = ['is_update'             => 0,
            'softwarecategories_id' => 3,
            'softwares_id'          => 0
        ];

        $this->array($result)->isIdenticalTo($expected);
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
        $this->integer((int)$softwares_id)->isGreaterThan(0);

        $this->variable($software->fields['is_template'])->isEqualTo(0);
        $this->string($software->fields['name'])->isIdenticalTo('MySoft');

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
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

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
        $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(1);
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

        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id_2];
        $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(1);
        $this->integer((int)countElementsInTable('glpi_contracts_items', $query))->isIdenticalTo(1);
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
        $query = ['itemtype' => 'Software', 'items_id' => $softwares_id];
        $this->integer((int)countElementsInTable('glpi_infocoms', $query))->isIdenticalTo(0);
        $this->integer((int)countElementsInTable('glpi_contracts_items', $query))->isIdenticalTo(0);

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
        $this->integer((int)$softwares_id)->isGreaterThan(0);
        $this->boolean($software->getFromDB($softwares_id))->isTrue();

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
        $this->integer((int)$license_id)->isGreaterThan(0);

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

    public function testGetEmpty()
    {
        global $CFG_GLPI;

        $software = new \Software();
        $CFG_GLPI['default_software_helpdesk_visible'] = 0;
        $software->getEmpty();
        $this->variable($software->fields['is_helpdesk_visible'])->isEqualTo(0);

        $CFG_GLPI['default_software_helpdesk_visible'] = 1;

        $software->getEmpty();
        $this->variable($software->fields['is_helpdesk_visible'])->isEqualTo(1);
    }

    public function testGetSpecificMassiveActions()
    {
        $this->login();

        $software = new \Software();
        $result = $software->getSpecificMassiveActions();
        $this->array($result)->hasSize(5);

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
        $this->array($result)->hasSize(4);

        $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = 0;
        $result = $software->getSpecificMassiveActions();
        $this->array($result)->hasSize(4);
    }

    public function testGetSearchOptionsNew()
    {
        $software = new \Software();
        $result   = $software->rawSearchOptions();
        $this->array($result)->hasSize(45);

        $this->login();
        $result   = $software->rawSearchOptions();
        $this->array($result)->hasSize(61);
    }

    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $software = $this->createItem(\Software::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]);
        $softwares_id_1 = $software->fields['id'];
        $this->array($software->fields['groups_id'])->containsValues([1, 2]);
        $this->array($software->fields['groups_id_tech'])->containsValues([3, 4]);

        $software = $this->createItem(\Software::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => null,
            'groups_id_tech' => null,
        ]);
        $softwares_id_2 = $software->fields['id'];
        $this->array($software->fields['groups_id'])->isEmpty();
        $this->array($software->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $software->getFromDB($softwares_id_1);
        $this->boolean($software->update([
            'id' => $softwares_id_1,
            'groups_id' => null,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($software->fields['groups_id'])->isEmpty();
        $this->array($software->fields['groups_id_tech'])->isEmpty();

        $software->getFromDB($softwares_id_2);
        $this->boolean($software->update([
            'id' => $softwares_id_2,
            'groups_id' => [5, 6],
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($software->fields['groups_id'])->containsValues([5, 6]);
        $this->array($software->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($software->update([
            'id' => $softwares_id_2,
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($software->fields['groups_id'])->containsValues([1, 2]);
        $this->array($software->fields['groups_id_tech'])->containsValues([3, 4]);
    }

    /**
     * Test the loading asset which still have integer values for groups_id and groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     * @return void
     */
    public function testLoadOldItemsSingleGroup()
    {
        /** @var \DBmysql $DB */
        global $DB;
        $software = $this->createItem(\Software::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $softwares_id = $software->fields['id'];

        // Manually set the groups_id and groups_id_tech fields to an integer value
        $DB->update(
            'glpi_softwares',
            [
                'groups_id' => 1,
                'groups_id_tech' => 2,
            ],
            [
                'id' => $softwares_id,
            ]
        );
        $software->getFromDB($softwares_id);
        $this->array($software->fields['groups_id'])->containsValues([1]);
        $this->array($software->fields['groups_id_tech'])->containsValues([2]);

        // Manually set the groups_id and groups_id_tech fields to 0
        $DB->update(
            'glpi_softwares',
            [
                'groups_id' => 0,
                'groups_id_tech' => 0,
            ],
            [
                'id' => $softwares_id,
            ]
        );
        $software->getFromDB($softwares_id);
        $this->array($software->fields['groups_id'])->isEmpty();
        $this->array($software->fields['groups_id_tech'])->isEmpty();

        // Manually set the groups_id and groups_id_tech fields to NULL (allowed by the DB schema)
        $DB->update(
            'glpi_softwares',
            [
                'groups_id' => null,
                'groups_id_tech' => null,
            ],
            [
                'id' => $softwares_id,
            ]
        );
        $software->getFromDB($softwares_id);
        $this->array($software->fields['groups_id'])->isEmpty();
        $this->array($software->fields['groups_id_tech'])->isEmpty();
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $software = new \Software();
        $this->array($software->fields['groups_id'])->isEmpty();
        $this->array($software->fields['groups_id_tech'])->isEmpty();
    }
}
