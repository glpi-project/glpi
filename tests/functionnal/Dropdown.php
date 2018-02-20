<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

namespace tests\units;

use \DbTestCase;

/* Test for inc/dropdown.class.php */

class Dropdown extends DbTestCase {

   public function testShowLanguages() {

      $opt = [ 'display_emptychoice' => true, 'display' => false ];
      $out = \Dropdown::showLanguages('dropfoo', $opt);
      $this->string($out)
         ->contains("name='dropfoo'")
         ->contains("value='' selected")
         ->notContains("value='0'")
         ->contains("value='fr_FR'");

      $opt = ['display' => false, 'value' => 'cs_CZ', 'rand' => '1234'];
      $out = \Dropdown::showLanguages('language', $opt);
      $this->string($out)
         ->notContains("value=''")
         ->notContains("value='0'")
         ->contains("name='language' id='dropdown_language1234")
         ->contains("value='cs_CZ' selected")
         ->contains("value='fr_FR'");
   }

   public function dataTestImport() {
      return [
            // input,             name,  message
            [ [ ],                '',    'missing name'],
            [ [ 'name' => ''],    '',    'empty name'],
            [ [ 'name' => ' '],   '',    'space name'],
            [ [ 'name' => ' a '], 'a',   'simple name'],
            [ [ 'name' => 'foo'], 'foo', 'simple name'],
      ];
   }

   /**
    * @dataProvider dataTestImport
    */
   public function testImport($input, $result, $msg) {
      $id = \Dropdown::import('UserTitle', $input);
      if ($result) {
         $this->integer((int)$id)->isGreaterThan(0);
         $ut = new \UserTitle();
         $this->boolean($ut->getFromDB($id))->isTrue();
         $this->string($ut->getField('name'))->isIdenticalTo($result);
      } else {
         $this->integer((int)$id)->isLessThan(0);
      }
   }

   public function dataTestTreeImport() {
      return [
            // input,                                  name,    completename, message
            [ [ ],                                     '',      '',           'missing name'],
            [ [ 'name' => ''],                          '',     '',           'empty name'],
            [ [ 'name' => ' '],                         '',     '',           'space name'],
            [ [ 'name' => ' a '],                       'a',    'a',          'simple name'],
            [ [ 'name' => 'foo'],                       'foo',  'foo',        'simple name'],
            [ [ 'completename' => 'foo > bar'],         'bar',  'foo > bar',  'two names'],
            [ [ 'completename' => ' '],                 '',     '',           'only space'],
            [ [ 'completename' => '>'],                 '',     '',           'only >'],
            [ [ 'completename' => ' > '],               '',     '',           'only > and spaces'],
            [ [ 'completename' => 'foo>bar'],           'bar',  'foo > bar',  'two names with no space'],
            [ [ 'completename' => '>foo>>bar>'],        'bar',  'foo > bar',  'two names with additional >'],
            [ [ 'completename' => ' foo >   > bar > '], 'bar',  'foo > bar',  'two names with garbage'],
      ];
   }

   /**
    * @dataProvider dataTestTreeImport
    */
   public function testTreeImport($input, $result, $complete, $msg) {
      $input['entities_id'] = getItemByTypeName('Entity', '_test_root_entity', true);
      $id = \Dropdown::import('Location', $input);
      if ($result) {
         $this->integer((int)$id, $msg)->isGreaterThan(0);
         $ut = new \Location();
         $this->boolean($ut->getFromDB($id))->isTrue();
         $this->string($ut->getField('name'))->isIdenticalTo($result);
         $this->string($ut->getField('completename'))->isIdenticalTo($complete);
      } else {
         $this->integer((int)$id)->isLessThanOrEqualTo(0);
      }
   }

   public function testGetDropdownName() {
      global $CFG_GLPI;

      $cat = getItemByTypeName('TaskCategory', '_cat_1');

      $subCat = getItemByTypeName( 'TaskCategory', '_subcat_1' );

      // basic test returns string only
      $expected = $cat->fields['name']." > ".$subCat->fields['name'];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID() );
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $cat->fields['name']." > ".$subCat->fields['name'],
                        'comment' => "<span class='b'>Complete name</span>: ".$cat->fields['name']." > "
                                    .$subCat->fields['name']."<br><span class='b'>&nbsp;Comments&nbsp;</span>"
                                    .$subCat->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $cat->fields['name']." > ".$subCat->fields['name'],
                        'comment' => $subCat->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return with translations
      $CFG_GLPI['translate_dropdowns'] = 1;
      $_SESSION["glpilanguage"] = \Session::loadLanguage( 'fr_FR' );
      $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
      $expected = ['name'    => 'FR - _cat_1 > FR - _subcat_1',
                        'comment' => 'FR - Commentaire pour sous-catégorie _subcat_1'];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);
      // switch back to default language
      $_SESSION["glpilanguage"] = \Session::loadLanguage('en_GB');

      ////////////////////////////////
      // test for other dropdown types
      ////////////////////////////////

      ///////////
      // Computer
      $computer = getItemByTypeName( 'Computer', '_test_pc01' );
      $ret = \Dropdown::getDropdownName( 'glpi_computers', $computer->getID());
      $this->string($ret)->isIdenticalTo($computer->getName());

      $expected = ['name'    => $computer->getName(),
                        'comment' => $computer->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_computers', $computer->getID(), true);
      $this->array($ret)->isIdenticalTo($expected);

      //////////
      // Contact
      $contact = getItemByTypeName( 'Contact', '_contact01_name' );
      $expected = $contact->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $contact->getName(),
                        'comment' => "Comment for contact _contact01_name<br><span class='b'>".
                                    "Phone: </span>0123456789<br><span class='b'>Phone 2: </span>0123456788<br><span class='b'>".
                                    "Mobile phone: </span>0623456789<br><span class='b'>Fax: </span>0123456787<br>".
                                    "<span class='b'>Email: </span>_contact01_firstname._contact01_name@glpi.com"];
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $contact->getName(),
                        'comment' => $contact->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Supplier
      $supplier = getItemByTypeName( 'Supplier', '_suplier01_name' );
      $expected = $supplier->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $supplier->getName(),
                        'comment' => "Comment for supplier _suplier01_name<br><span class='b'>Phone: </span>0123456789<br>".
                                     "<span class='b'>Fax: </span>0123456787<br><span class='b'>Email: </span>info@_supplier01_name.com"];
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $supplier->getName(),
                        'comment' => $supplier->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Netpoint
      $netpoint = getItemByTypeName( 'Netpoint', '_netpoint01' );
      $location = getItemByTypeName( 'Location', '_location01' );
      $expected = $netpoint->getName()." (".$location->getName().")";
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $expected,
                        'comment' => "Comment for netpoint _netpoint01"];
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Budget
      $budget = getItemByTypeName( 'Budget', '_budget01' );
      $expected = $budget->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    =>  $budget->getName(),
                        'comment' => "Comment for budget _budget01<br><span class='b'>Location</span>: ".
                                       "_location01<br><span class='b'>Type</span>: _budgettype01<br><span class='b'>".
                                       "Start date</span>: 2016-10-18 <br><span class='b'>End date</span>: 2016-12-31 "];
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $budget->getName(),
                        'comment' => $budget->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);
   }

   public function dataGetValueWithUnit() {
      return [
            [1,       'auto',              '1024 Kio'],
            [1025,    'auto',              '1 Gio'],
            ['1 025', 'auto',              '1 Gio'],
            [1,       'year',              '1 year'],
            [2,       'year',              '2 years'],
            [3,       '%',                 '3%'],
            ['foo',   'bar',               'foo bar'],
      ];
   }

   /**
    * @dataProvider dataGetValueWithUnit
    */
   public function testGetValueWithUnit($input, $unit, $expected) {
      $this->string(\Dropdown::getValueWithUnit($input, $unit))->isIdenticalTo($expected);
   }
}
