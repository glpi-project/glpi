<?php
/*
 * @version $Id$
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
class Framework_Dropdown_Import extends PHPUnit_Framework_TestCase {

   /**
    * Import of CommonDropdown - without dictionary
    */
   public function testImportSimple() {

      $input=array('name'=>'', 'comment'=>"test import");
      $id = Dropdown::import('DeviceCaseType', $input);
      $this->assertFalse($id>0);

      $input=array('name'=>'PHP Unit test 1', 'comment'=>"test import");
      $id1 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id1>0);

      $id2 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id2>0);
      $this->assertTrue($id1==$id2);

      $input=array('name'=>'PHP Unit test 2');
      $id3 = Dropdown::import('DeviceCaseType', $input);
      $this->assertTrue($id3>0);
      $this->assertTrue($id3!=$id2);

      $dct = new DeviceCaseType();
      $this->assertTrue($dct->getFromDB($id3));
      $this->assertTrue($dct->fields['name']==$input['name']);
   }

   /**
    * Import of CommonTreeDropdown
    */
   public function testImportTree() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $obj = new ITILCategory();
      $fk = 'itilcategories_id';

      // root entity - A - new
      $id[0] = $obj->import(array('name'         => 'A',
                                  'is_recursive' => 1,
                                  'entities_id'  => $ent0));
      $this->assertGreaterThan(0, $id[0]);
      $this->assertTrue($obj->getFromDB($id[0]));
      $this->assertEquals(1, $obj->fields['is_recursive']);

      // root entity - B - new
      $id[1] = $obj->import(array('name'         => 'B',
                                  'entities_id'  => $ent0));
      $this->assertGreaterThan(0, $id[1]);
      $this->assertTrue($obj->getFromDB($id[1]));
      $this->assertEquals(0, $obj->fields['is_recursive']);

      // child entity - A - existing
      $id[2] = $obj->import(array('name'         => 'A',
                                  'entities_id'  => $ent1));
      $this->assertEquals($id[0],$id[2]);

      // child entity - B - new
      $id[3] = $obj->import(array('name'         => 'B',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[1], $id[3]);

      // child entity - B > C - exiting B + new C
      $id[4] = $obj->import(array('completename' => 'B > C',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[3], $id[4]);
      $this->assertTrue($obj->getFromDB($id[4]));
      $this->assertEquals('C', $obj->fields['name']);
      $this->assertEquals($id[3], $obj->fields[$fk]);

      // child entity - >B>>C>D - clean completename
      $id[5] = $obj->import(array('completename' => '>B>> C>D',
                                  'entities_id'  => $ent1));
      $this->assertGreaterThan($id[4], $id[5]);
      $this->assertTrue($obj->getFromDB($id[5]));
      $this->assertEquals('D', $obj->fields['name']);
      $this->assertEquals($id[4], $obj->fields[$fk]);
      $this->assertEquals('B > C > D', $obj->fields['completename']);
   }

   /**
    * Import of Manufacturer (with Rule)
    */
   public function testManufacturer() {

      // Old counters
      $nbr = countElementsInTable('glpi_rules');
      $nba = countElementsInTable('glpi_ruleactions');
      $nbc = countElementsInTable('glpi_rulecriterias');

      // Create some rules
      $rule = new RuleDictionnaryDropdown('RuleDictionnaryManufacturer');
      $crit = new RuleCriteria();
      $acte = new RuleAction();

      $idr[0] = $rule->add(array('name'      => 'test1',
                                 'sub_type'  => 'RuleDictionnaryManufacturer',
                                 'match'     => 'AND',
                                 'is_active' => 1));
      $this->assertGreaterThan(0, $idr[0], "Fail: can't create rule 1");
      $this->assertTrue($rule->getFromDB($idr[0]));
      $this->assertEquals(1, $rule->fields['ranking'], "Fail: ranking not set");

      $idc[0] = $crit->add(array('rules_id'  => $idr[0],
                                 'criteria'  => 'name',
                                 'condition' => Rule::PATTERN_CONTAIN,
                                 'pattern'   => 'indepnet'));
      $this->assertGreaterThan(0, $idc[0], "Fail: can't create rule 1 criteria");

      $ida[0] = $acte->add(array('rules_id'    => $idr[0],
                                 'action_type' => 'assign',
                                 'field'       => 'name',
                                 'value'       => $out1='Indepnet'));
      $this->assertGreaterThan(0, $ida[0], "Fail: can't create rule 1 action");

      // Add another rule
      $idr[1] = $rule->add(array('name'      => 'test2',
                                 'sub_type'  => 'RuleDictionnaryManufacturer',
                                 'match'     => 'AND',
                                 'is_active' => 1));
      $this->assertGreaterThan(0, $idr[1], "Fail: can't create rule 2");
      $this->assertTrue($rule->getFromDB($idr[1]));
      $this->assertEquals(2, $rule->fields['ranking'], "Fail: ranking not set");

      $idc[1] = $crit->add(array('rules_id'  => $idr[1],
                                 'criteria'  => 'name',
                                 'condition' => Rule::PATTERN_BEGIN,
                                 'pattern'   => 'http:'));
      $this->assertGreaterThan(0, $idc[1], "Fail: can't create rule 2 criteria");

      $ida[1] = $acte->add(array('rules_id'    => $idr[1],
                                 'action_type' => 'assign',
                                 'field'       => 'name',
                                 'value'       => $out2='Web Site'));
      $this->assertGreaterThan(0, $ida[1], "Fail: can't create rule 2 action");


      $manu = new Manufacturer();

      // Import first and fill cache
      $id[0] = $manu->importExternal($in='the indepnet team');
      $this->assertGreaterThan(0, $id[0]);
      $this->assertTrue($manu->getFromDB($id[0]));
      $this->assertEquals($out1, $manu->fields['name'], "Fail: Rule::PATTERN_CONTAIN not match");
      $this->assertEquals(1, countElementsInTable($cache), "Fail: cache empty");

      // Import second and use cache
      $id[1] = $manu->importExternal($in='The INDEPNET Team');
      $this->assertGreaterThan(0, $id[1]);
      $this->assertEquals($id[0], $id[1]);
      $this->assertTrue($manu->getFromDB($id[1]));
      $this->assertEquals($out1, $manu->fields['name'], "Fail: Rule::PATTERN_CONTAIN not match");
      $this->assertEquals(1, countElementsInTable($cache), "Fail: cache not filled");

      // Import third not in cache
      $id[2] = $manu->importExternal($in='http://www.indepnet.net/');
      $this->assertGreaterThan(0, $id[2]);
      $this->assertEquals($id[0], $id[2]);
      $this->assertTrue($manu->getFromDB($id[2]));
      $this->assertEquals($out1, $manu->fields['name'], "Fail: Rule::PATTERN_CONTAIN not match");
      $this->assertEquals(2, countElementsInTable($cache), "Fail: cache not filled");

      // Set is_active=0, and clean cache
      $this->assertTrue($rule->update(array('id' => $idr[0],
                                            'is_active' => 0)), "Fail: update rule");
      $this->assertEquals(0, countElementsInTable($cache), "Fail: cache not empty");
      $this->assertTrue($rule->update(array('id' => $idr[0],
                                            'is_active' => 1)), "Fail: update rule");


      // Import again and fill cache
      $id[3] = $manu->importExternal($in='http://www.glpi-project.org/');
      $this->assertGreaterThan(0, $id[3]);
      $this->assertGreaterThan($id[0], $id[3]);
      $this->assertTrue($manu->getFromDB($id[3]));
      $this->assertEquals($out2, $manu->fields['name'], "Fail: Rule::PATTERN_BEGIN not match");
      $this->assertEquals(1, countElementsInTable($cache), "Fail: cache empty");

      $id[4] = $manu->importExternal($in='http://www.indepnet.net/');
      $this->assertGreaterThan(0, $id[4]);
      $this->assertEquals($id[0], $id[4]);
      $this->assertTrue($manu->getFromDB($id[4]));
      $this->assertEquals($out1, $manu->fields['name'], "Fail: Rule::PATTERN_CONTAIN not match");
      $this->assertEquals(2, countElementsInTable($cache), "Fail: cache not filled");

      // Hack : to disable preload done by Singleton
      $tmp = SingletonRuleList::getInstance('RuleDictionnaryManufacturer');
      $tmp->load=0;

      // Change rules order
      $collection = new RuleDictionnaryDropdownCollection('RuleDictionnaryManufacturer');
      // Move rule 1 after rule 2
      $this->assertTrue($collection->moveRule($idr[0], $idr[1]), "Fail: can't move rules");
      $this->assertEquals(0, countElementsInTable($cache), "Fail: cache not empty");

      $this->assertTrue($rule->getFromDB($idr[1]));
      $this->assertEquals(1, $rule->fields['ranking'], "Fail: ranking not change");
      $this->assertTrue($rule->getFromDB($idr[0]));
      $this->assertEquals(2, $rule->fields['ranking'], "Fail: ranking not change");

      // Import again and fill cache
      $id[5] = $manu->importExternal($in='http://www.glpi-project.org/');
      $this->assertGreaterThan(0, $id[5]);
      $this->assertTrue($manu->getFromDB($id[5]));
      $this->assertEquals($out2, $manu->fields['name'], "Fail: Rule::PATTERN_BEGIN not match");

      $id[6] = $manu->importExternal($in='http://www.indepnet.net/');
      $this->assertGreaterThan(0, $id[6]);
      $this->assertTrue($manu->getFromDB($id[6]));
      $this->assertEquals($out2, $manu->fields['name'], "Fail: Rule::PATTERN_BEGIN not match");
      $this->assertEquals($id[5], $id[6]);

      // Change rules orders again
      $tmp = SingletonRuleList::getInstance('RuleDictionnaryManufacturer');
      $tmp->load=0;
      // Move rule 1 up (before rule 2)
      $this->assertTrue($collection->changeRuleOrder($idr[0], 'up'), "Fail: can't move rules");
      $this->assertEquals(0, countElementsInTable($cache), "Fail: cache not empty");

      $this->assertTrue($rule->getFromDB($idr[0]));
      $this->assertEquals(1, $rule->fields['ranking'], "Fail: ranking not change");
      $this->assertTrue($rule->getFromDB($idr[1]));
      $this->assertEquals(2, $rule->fields['ranking'], "Fail: ranking not change");

      // Import again and fill cache
      $id[7] = $manu->importExternal($in='http://www.glpi-project.org/');
      $this->assertGreaterThan(0, $id[7]);
      $this->assertTrue($manu->getFromDB($id[7]));
      $this->assertEquals($out2, $manu->fields['name'], "Fail: Rule::PATTERN_BEGIN not match");

      $id[8] = $manu->importExternal($in='http://www.indepnet.net/');
      $this->assertGreaterThan(0, $id[8]);
      $this->assertTrue($manu->getFromDB($id[8]));
      $this->assertEquals($out1, $manu->fields['name'], "Fail: Rule::PATTERN_CONTAIN not match");
      $this->assertNotEquals($id[7], $id[8]);
      $this->assertEquals(2, countElementsInTable($cache), "Fail: cache not empty");

      // Clean
      $this->assertEquals($nbr+2, countElementsInTable('glpi_rules'), "Fail: glpi_rules content");
      $this->assertEquals($nba+2, countElementsInTable('glpi_ruleactions'), "Fail: glpi_ruleactions content");
      $this->assertEquals($nbc+2, countElementsInTable('glpi_rulecriterias'), "Fail: glpi_ruleactions content");

      $this->assertTrue($rule->delete(array('id'=>$idr[0])));
      $this->assertEquals(1, countElementsInTable($cache), "Fail: cache not empty");

      $this->assertTrue($rule->delete(array('id'=>$idr[1])));
      $this->assertEquals(0, countElementsInTable($cache), "Fail: cache not empty");
      $this->assertEquals($nbr, countElementsInTable('glpi_rules'), "Fail: glpi_rules not empty");
      $this->assertEquals($nba, countElementsInTable('glpi_ruleactions'), "Fail: glpi_ruleactions not empty");
      $this->assertEquals($nbc, countElementsInTable('glpi_rulecriterias'), "Fail: glpi_ruleactions not empty");
   }
   /**
    * Import of Software (with Rule)
    *
    * From OcsServer class
    */
   public function testSoftwareRule() {

      // Clean preload rules
      $tmp = SingletonRuleList::getInstance('RuleDictionnaryManufacturer');
      $tmp->load=0;
      $tmp = SingletonRuleList::getInstance('RuleDictionnarySoftware');
      $tmp->load=0;

      // Needed objetcs
      $rulem = new RuleDictionnaryDropdown('RuleDictionnaryManufacturer');
      $rules = new RuleDictionnaryDropdown('RuleDictionnarySoftware');
      $crit = new RuleCriteria();
      $acte = new RuleAction();

      // Rule for Manufacturer
      $idr[0] = $rulem->add(array('name'      => 'test1',
                                  'sub_type'  => 'RuleDictionnaryManufacturer',
                                  'match'     => 'AND',
                                  'is_active' => 1));
      $this->assertGreaterThan(0, $idr[0], "Fail: can't create manufacturer rule");
      $this->assertTrue($rulem->getFromDB($idr[0]));
      $this->assertEquals(1, $rulem->fields['ranking'], "Fail: ranking not set");

      $idc[0] = $crit->add(array('rules_id'  => $idr[0],
                                 'criteria'  => 'name',
                                 'condition' => Rule::PATTERN_CONTAIN,
                                 'pattern'   => 'indepnet'));
      $this->assertGreaterThan(0, $idc[0], "Fail: can't create manufacturer rule criteria");

      $ida[0] = $acte->add(array('rules_id'    => $idr[0],
                                 'action_type' => 'assign',
                                 'field'       => 'name',
                                 'value'       => $outm='Indepnet'));
      $this->assertGreaterThan(0, $ida[0], "Fail: can't create manufacturer rule action");

      // Rule for Software
      $idr[1] = $rules->add(array('name'      => 'test2',
                                 'sub_type'  => 'RuleDictionnarySoftware',
                                 'match'     => 'AND',
                                 'is_active' => 1));
      $this->assertGreaterThan(0, $idr[1], "Fail: can't create software rule");
      $this->assertTrue($rules->getFromDB($idr[1]));
      $this->assertEquals(1, $rules->fields['ranking'], "Fail: ranking not set");

      $idc[1] = $crit->add(array('rules_id'  => $idr[1],
                                 'criteria'  => 'name',
                                 'condition' => Rule::REGEX_MATCH,
                                 'pattern'   => '/^glpi (0\.[0-9]+)/'));
      $this->assertGreaterThan(0, $idc[1], "Fail: can't create software rule criteria");

      $ida[1] = $acte->add(array('rules_id'    => $idr[1],
                                 'action_type' => 'assign',
                                 'field'       => 'name',
                                 'value'       => $outs='GLPI'));
      $this->assertGreaterThan(0, $ida[1], "Fail: can't create software rule action");

      $ida[2] = $acte->add(array('rules_id'    => $idr[1],
                                 'action_type' => 'regex_result',
                                 'field'       => 'version',
                                 'value'       => $outv='#0'));
      $this->assertGreaterThan(0, $ida[2], "Fail: can't create software rule action");

      // Apply Rule to manufacturer
      $manu = Dropdown::import('Manufacturer','the indepnet team');
      $this->assertEquals('Indepnet', $manu, "Fail: manufacturer not altered");

      // Apply Rule to software
      $rulecollection = new RuleDictionnarySoftwareCollection();
      $res_rule = $rulecollection->processAllRules(array("name"         => 'glpi 0.78',
                                                         "manufacturer" => $manu,
                                                         "old_version"  => ''),
                                                   array(), array());
      $this->assertArrayHasKey('name', $res_rule, "Fail: name not altered");
      $this->assertEquals('GLPI', $res_rule['name'], "Fail: name not correct");

      $this->assertArrayHasKey('version', $res_rule, "Fail: name not altered");
      $this->assertEquals('0.78', $res_rule['version'], "Fail: version not correct");

      // Clean
      $this->assertTrue($rulem->delete(array('id'=>$idr[0])));
      $this->assertTrue($rules->delete(array('id'=>$idr[1])));
   }

   /**
    * test for addOrRestoreFromTrash
    */
   public function testSoftwareImport() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $soft = new Software();
      $manu = new Manufacturer();

      // Import Software
      $id[0] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent0);
      $this->assertGreaterThan(0, $id[0], "Fail: can't create software 1");
      // Check name
      $this->assertTrue($soft->getFromDB($id[0]), "Fail: can't read new soft");
      $this->assertEquals('GLPI', $soft->getField('name'), "Fail: name not set");
      // Check manufacturer
      $manid = $soft->getField('manufacturers_id');
      $this->assertGreaterThan(0, $manid, "Fail: manufacturer not set");
      $this->assertTrue($manu->getFromDB($manid), "Fail: can't manufacturer");
      $this->assertEquals('Indepnet', $manu->getField('name'));

      // Import again => same result
      $id[1] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent0);
      $this->assertGreaterThan(0, $id[1], "Fail: can't create software 2");
      $this->assertEquals($id[0],$id[1], "Fail: previous not found");

      // Import in another entity
      $id[2] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent1);
      $this->assertGreaterThan(0, $id[2], "Fail: can't create software 3");
      $this->assertNotEquals($id[0],$id[2], "Fail: previous used (from another entity)");

      // Delete
      $this->assertTrue($soft->delete(array('id'=>$id[2])), "Fail: can't delete software 3)");
      $this->assertTrue($soft->getFromDB($id[2]), "Fail: can't read new soft");
      $this->assertEquals(1, $soft->getField('is_deleted'), "Fail: soft not deleted");

      // Import again => restore
      $id[3] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent1);
      $this->assertEquals($id[2],$id[3], "Fail: previous not used");
      $this->assertTrue($soft->getFromDB($id[2]), "Fail: can't read new soft");
      $this->assertEquals(0, $soft->getField('is_deleted'), "Fail: soft not restored");

      // Import again => with recursive
      $this->assertTrue($soft->update(array('id'           => $id[0],
                                            'is_recursive' => 1)), "Fail: can't update software 1)");
      $id[4] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent2);
      $this->assertEquals($id[0],$id[4], "Fail: previous not used");

      // Clean
      $this->assertTrue($soft->delete(array('id'=>$id[0]),true), "Fail: can't delete software 1)");
      $this->assertTrue($soft->delete(array('id'=>$id[2]),true), "Fail: can't delete software 1)");
   }

   /**
    * Test software category Rule and putInTrash / removeFromTrash
    */
   public function testSoftwareCategory() {
      global $CFG_GLPI;

      $ent0 = $this->sharedFixture['entity'][0];

      // Clean preload rules
      $tmp = SingletonRuleList::getInstance('RuleSoftwareCategory');
      $tmp->load=0;

      $this->assertArrayHasKey('softwarecategories_id_ondelete', $CFG_GLPI, "Fail: no softwarecategories_id_ondelete");

      $idcat[0] = Dropdown::import('SoftwareCategory', array('name'=>'Trashed'));
      $this->assertGreaterThan(0,$idcat[0],"Fail: can't create SoftwareCategory");

      $idcat[1] = Dropdown::import('SoftwareCategory', array('name'=>'OpenSource'));
      $this->assertGreaterThan(0,$idcat[1],"Fail: can't create SoftwareCategory");

      $rule = new RuleSoftwareCategory();
      $crit = new RuleCriteria();
      $acte = new RuleAction();

      $idr[0] = $rule->add(array('name'      => 'OSS',
                                 'sub_type'  => 'RuleSoftwareCategory',
                                 'match'     => 'AND',
                                 'is_active' => 1));
      $this->assertGreaterThan(0, $idr[0], "Fail: can't create rule 1");
      $this->assertTrue($rule->getFromDB($idr[0]));
      $this->assertEquals(1, $rule->fields['ranking'], "Fail: ranking not set");

      $idc[0] = $crit->add(array('rules_id'  => $idr[0],
                                 'criteria'  => 'manufacturer',
                                 'condition' => Rule::PATTERN_IS,
                                 'pattern'   => 'Indepnet'));
      $this->assertGreaterThan(0, $idc[0], "Fail: can't create rule 1 criteria");

      $ida[0] = $acte->add(array('rules_id'    => $idr[0],
                                 'action_type' => 'assign',
                                 'field'       => 'softwarecategories_id',
                                 'value'       => $idcat[1]));
      $this->assertGreaterThan(0, $ida[0], "Fail: can't create rule 1 action");

      // Createthe software
      $soft = new Software();
      $id[0] = $soft->addOrRestoreFromTrash('GLPI', 'Indepnet', $ent0);
      $this->assertGreaterThan(0, $id[0], "Fail: can't create software 1");
      // Check name
      $this->assertTrue($soft->getFromDB($id[0]), "Fail: can't read new soft");
      $this->assertEquals('GLPI', $soft->getField('name'), "Fail: name not set");
      // Check category
      $catid = $soft->getField('softwarecategories_id');
      $this->assertEquals($idcat[1], $catid, "Fail: category not set");

      // Change configuration
      $CFG_GLPI["softwarecategories_id_ondelete"] = $idcat[0];

      // Delete
      $this->assertTrue($soft->putInTrash($id[0]), "Fail: can't put soft in trash");
      $this->assertTrue($soft->getFromDB($id[0]), "Fail: can't read new soft");
      $catid = $soft->getField('softwarecategories_id');
      $this->assertEquals($idcat[0], $catid, "Fail: category not set");
      $this->assertEquals(1, $soft->getField('is_deleted'), "Fail: soft not deleted");

      // Restore
      $this->assertTrue($soft->removeFromTrash($id[0]), "Fail: can't put soft in trash");
      $this->assertTrue($soft->getFromDB($id[0]), "Fail: can't read new soft");
      $catid = $soft->getField('softwarecategories_id');
      $this->assertEquals($idcat[1], $catid, "Fail: category not set");
      $this->assertEquals(0, $soft->getField('is_deleted'), "Fail: soft not restored");

      // Clean
      $this->assertTrue($soft->delete(array('id'=>$id[0]),true), "Fail: can't delete software 1)");
   }
}
?>
