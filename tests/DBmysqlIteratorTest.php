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

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIteratorTest extends PHPUnit_Framework_TestCase {

   public function testQuery() {
      $req = 'SELECT Something FROM Somewhere';
      $it = new DBmysqlIterator(NULL, $req);
      $this->assertEquals($req, $it->getSql());
   }

   public function testOnlyTable() {
      $it = new DBmysqlIterator(NULL, 'foo');
      $this->assertEquals('SELECT * FROM `foo`', $it->getSql());

      $it = new DBmysqlIterator(NULL, ['foo', 'bar']);
      $this->assertEquals('SELECT * FROM `foo`, `bar`', $it->getSql());
   }

   public function testFields() {
      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => 'bar']);
      $this->assertEquals('SELECT `bar` FROM `foo`', $it->getSql());

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['bar', 'baz']]);
      $this->assertEquals('SELECT bar,baz FROM `foo`', $it->getSql());

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['b' => 'bar']]);
      $this->assertEquals('SELECT b.bar FROM `foo`', $it->getSql());

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['a' => ['bar', 'baz']]]);
      $this->assertEquals('SELECT a.bar,a.baz FROM `foo`', $it->getSql());
   }
}
