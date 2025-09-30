<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* Test for inc/ruledictionnarysoftwarecollection.class.php */

class RuleDictionnarySoftwareCollectionTest extends DbTestCase
{
    public function testCleanTestOutputCriterias()
    {
        $collection = new \RuleDictionnarySoftwareCollection();
        $params     = ['manufacturers_id' => 1,
            '_bad'             => '_value2',
            '_ignore_import'   => '1',
        ];
        $result     = $collection->cleanTestOutputCriterias($params);
        $expected   = ['manufacturers_id' => 1,
            '_ignore_import'   => '1',
        ];
        $this->assertSame($expected, $result);
    }

    public function testVersionExists()
    {
        $soft    = getItemByTypeName('Software', '_test_soft');
        $version = getItemByTypeName('SoftwareVersion', '_test_softver_1');

        $collection = new \RuleDictionnarySoftwareCollection();
        $result     = $collection->versionExists($soft->getID(), '_test_softver_1');

        $this->assertSame($version->getID(), $result);

        $collection = new \RuleDictionnarySoftwareCollection();
        $result     = $collection->versionExists($soft->getID(), '_test_softver_111');

        $this->assertSame(-1, $result);
    }

    public function testMoveLicense()
    {
        $old_software = new \Software();
        $softwares_id = $old_software->add([
            'name'         => 'Software ' . $this->getUniqueString(),
            'is_template'  => 0,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, (int) $softwares_id);
        $this->assertTrue($old_software->getFromDB($softwares_id));

        //ad and link 5 licenses to new software
        for ($i = 0; $i < 5; ++$i) {
            $license = new \SoftwareLicense();
            $license_id = $license->add([
                'name'         => 'Software license ' . $this->getUniqueString(),
                'softwares_id' => $old_software->getID(),
                'entities_id'  => 0,
            ]);
            $this->assertGreaterThan(0, (int) $license_id);
            $this->assertTrue($license->getFromDB($license_id));
        }

        $new_software = new \Software();
        $softwares_id = $new_software->add([
            'name'         => 'Software ' . $this->getUniqueString(),
            'is_template'  => 0,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, (int) $softwares_id);
        $this->assertTrue($new_software->getFromDB($softwares_id));

        $collection = new \RuleDictionnarySoftwareCollection();
        $this->assertTrue(
            $collection->moveLicenses(
                $old_software->getID(),
                $new_software->getID()
            )
        );

        $this->assertSame(
            0,
            countElementsInTable(
                'glpi_softwarelicenses',
                ['softwares_id' => $old_software->getID()]
            )
        );
        $this->assertSame(
            5,
            countElementsInTable(
                'glpi_softwarelicenses',
                ['softwares_id' => $new_software->getID()]
            )
        );

        $this->assertFalse($collection->moveLicenses('100', $new_software->getID()));
        $this->assertFalse($collection->moveLicenses($old_software->getID(), '100'));
    }

    public function testPutOldSoftsInTrash()
    {
        $this->login();

        $collection = new \RuleDictionnarySoftwareCollection();
        $software   = new \Software();

        //Software with no version
        $soft_id_1  = $software->add(['name' => 'Soft1', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $soft_id_1);
        $soft_id_2  = $software->add(['name' => 'Soft2', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $soft_id_2);

        //Software with at least one version (from bootstrap)
        $soft3      = getItemByTypeName('Software', '_test_soft');

        //Software already deleted
        $soft_id_4  = $software->add(['name' => 'Soft4', 'is_deleted' => 1, 'entities_id' => 0]);
        $this->assertGreaterThan(0, $soft_id_4);

        //Template of software
        $soft_id_5  = $software->add(['name' => 'Soft5', 'is_template' => 1, 'entities_id' => 0]);
        $this->assertGreaterThan(0, $soft_id_5);

        $collection->putOldSoftsInTrash([
            $soft_id_1,
            $soft_id_2,
            $soft3->getID(),
            $soft_id_4,
            $soft_id_5,
        ]);

        //Softwares newly put in trash
        $this->assertSame(
            1,
            countElementsInTable('glpi_softwares', ['name' => 'Soft1', 'is_deleted' => 1])
        );
        $this->assertSame(
            1,
            countElementsInTable('glpi_softwares', ['name' => 'Soft2', 'is_deleted' => 1])
        );
        $this->assertSame(
            1,
            countElementsInTable('glpi_softwares', ['name' => 'Soft4', 'is_deleted' => 1])
        );

        //Software not affected
        $this->assertSame(
            0,
            countElementsInTable('glpi_softwares', ['name' => '_test_soft', 'is_deleted' => 1])
        );
        $this->assertSame(
            0,
            countElementsInTable('glpi_softwares', ['name' => 'Soft5', 'is_deleted' => 0])
        );
    }

    public function testIgnoreImport()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();
        $collection = new \RuleDictionnarySoftwareCollection();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 52',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => '1',
            ])
        );

        $input = ['name'             => 'Mozilla Firefox 52',
            'version'          => '52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];
        $result = $collection->processAllRules($input);
        $expected = ['_ignore_import' => '1', '_ruleid' => $rules_id];
        $this->assertSame($expected, $result);

        $input = ['name'             => 'Mozilla Firefox 53',
            'version'          => '52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];
        $result = $collection->processAllRules($input);
        $expected = ['_no_rule_matches' => true, '_rule_process' => false];
        $this->assertSame($expected, $result);
    }

    public function testSetSoftwareVersion()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();
        $collection = new \RuleDictionnarySoftwareCollection();

        $rules_id = $rule->add(['name'        => 'Set version',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::REGEX_MATCH,
                'pattern'   => '/Mozilla Firefox (.*)/',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'regex_result',
                'field'       => 'version',
                'value'       => '#0',
            ])
        );

        $input = ['name'             => 'Mozilla Firefox 52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];

        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = ['version' => '52', '_ruleid' => $rules_id];
        $this->assertSame($expected, $result);
    }

    public function testSetSoftwareNameAndVersion()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();
        $collection = new \RuleDictionnarySoftwareCollection();

        $rules_id = $rule->add(['name'        => 'Set version',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::REGEX_MATCH,
                'pattern'   => '/Mozilla Firefox (.*)/',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'regex_result',
                'field'       => 'version',
                'value'       => '#0',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'name',
                'value'       => 'Mozilla Firefox',
            ])
        );

        $input = ['name'             => 'Mozilla Firefox 52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];

        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = [
            'version' => '52',
            'name'    => 'Mozilla Firefox',
            '_ruleid' => $rules_id,
        ];
        $this->assertSame($expected, $result);
    }

    public function testSetSoftwareNameAndCategory()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();
        $collection = new \RuleDictionnarySoftwareCollection();
        $category   = new \SoftwareCategory();
        $categories_id = $category->importExternal('web');

        $rules_id = $rule->add(['name'        => 'Set version',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::REGEX_MATCH,
                'pattern'   => '/Mozilla Firefox (.*)/',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'softwarecategories_id',
                'value'       => $categories_id,
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'name',
                'value'       => 'Mozilla Firefox',
            ])
        );

        $input = ['name'             => 'Mozilla Firefox 52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];

        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = [
            'softwarecategories_id' => "$categories_id",
            'name'                  => 'Mozilla Firefox',
            '_ruleid'               => $rules_id,
        ];
        $this->assertSame($expected, $result);
    }

    public function testSetManufacturer()
    {
        $rule             = new \Rule();
        $criteria         = new \RuleCriteria();
        $action           = new \RuleAction();
        $collection       = new \RuleDictionnarySoftwareCollection();
        $manufacturer     = new \Manufacturer();
        $manufacturers_id = $manufacturer->importExternal('Mozilla');

        $rules_id = $rule->add(['name'        => 'Set manufacturer',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::REGEX_MATCH,
                'pattern'   => '/Mozilla Firefox (.*)/',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'manufacturers_id',
                'value'       => $manufacturers_id,
            ])
        );

        $input = ['name'             => 'Mozilla Firefox 52',
            'manufacturer'     => 'Mozilla',
            '_system_category' => 'web',
        ];

        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = ['manufacturers_id' => "$manufacturers_id",
            '_ruleid'          => $rules_id,
        ];
        $this->assertSame($expected, $result);
    }

    public function testSetSoftwareVersionAppend()
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();
        $collection = new \RuleDictionnarySoftwareCollection();

        $rules_id = $rule->add(['name'        => 'Test append',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::REGEX_MATCH,
                'pattern'   => '/^Soft (something|else)/',
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'append_regex_result',
                'field'       => 'version',
                'value'       => '#0',
            ])
        );

        $input = ['name'             => 'Soft something'];
        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = ['version_append' => 'something', 'version' => 'something', '_ruleid' => $rules_id];
        $this->assertSame($expected, $result);

        $input = ['name'             => 'Soft else'];
        $collection->RuleList = new \stdClass();
        $collection->RuleList->load = true;
        $result   = $collection->processAllRules($input);
        $expected = ['version_append' => 'else', 'version' => 'else', '_ruleid' => $rules_id];
        $this->assertSame($expected, $result);
    }
}
