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

namespace tests\units;

use \DbTestCase;

/* Test for inc/itiltemplate.class.php */
class ITILTemplate extends DbTestCase {

   protected function itilProvider() {
      return [
         ['Ticket'],
         ['Change'],
         ['Problem']
      ];
   }

   /**
    * @dataProvider itilProvider
    */
   public function testTemplateUsage($itiltype) {
      $this->login();

      //create template
      $tpl_class = '\\' . $itiltype . 'Template';
      $tpl = new $tpl_class;
      $tpl_id = (int)$tpl->add([
         'name'   => 'Template for ' . $itiltype
      ]);
      $this->integer($tpl_id)->isGreaterThan(0);

      //add a mandatory field
      $mandat_class = '\\' . $itiltype . 'TemplateMandatoryField';
      $mandat = new $mandat_class;
      $this->integer(
         (int)$mandat->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $mandat->getFieldNum($tpl, 'Title')
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$mandat->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $mandat->getFieldNum($tpl, 'Location')
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$mandat->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $mandat->getFieldNum($tpl, 'Description')
         ])
      )->isGreaterThan(0);

      //add a predefined field
      $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
      $predef = new $predef_class;
      $this->integer(
         (int)$predef->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $predef->getFieldNum($tpl, 'Description'), //Description
            'value'              => 'Description from template'
         ])
      )->isGreaterThan(0);

      $category = new \ITILCategory();
      $cat_field = strtolower($itiltype) . 'templates_id';
      if ($itiltype === \Ticket::getType()) {
         $cat_field .= '_demand';
      }
      $cat_id = (int)$category->add([
         'name'      => 'Category for a template',
         $cat_field  => $tpl_id
      ]);
      $this->integer($cat_id)->isGreaterThan(0);

      $object = new $itiltype;
      $tpl_key = $object->getTemplateFormFieldName();
      $content = [
         'name'                  => '',
         'content'               => '',
         'itilcategories_id'     => $cat_id,
         $tpl_key                => $tpl_id,
         'entities_id'           => 0,
         'locations_id'          => 'NULL'
      ];
      if ($itiltype === \Ticket::getType()) {
         $content['type'] = \Ticket::INCIDENT_TYPE;
      }
      $tid = (int)$object->add($content);
      $this->integer($tid)->isIdenticalTo(0);

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo(
         [ERROR => ['Mandatory fields are not filled. Please correct: Title, Location, Description']]
      );
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      $content['name']           = 'Title is required';
      $content['content']        = 'Description from template';
      $content['locations_id']   = getItemByTypeName('Location', '_location01', true);

      $tid = (int)$object->add($content);
      $this->integer($tid)->isIdenticalTo(0);

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo([
         ERROR => [
            'You cannot use predefined description verbatim',
            'Mandatory fields are not filled. Please correct: Description'
         ]
      ]);
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

      $content['content'] = 'A content for our ' . $itiltype;
      $tid = (int)$object->add($content);
      $this->integer($tid)->isGreaterThan(0);
   }
}
