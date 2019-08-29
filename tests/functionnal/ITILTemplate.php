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

      $err_msg = 'Mandatory fields are not filled. Please correct: Title' .
         ($itiltype === \Ticket::getType() ? ', Location' : '') . ', Description';
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo(
         [ERROR => [$err_msg]]
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

   /**
    * @dataProvider itilProvider
    */
   public function testGetAllowedFields($itiltype) {
      $class = $itiltype.'Template';
      $fields = $class::getAllowedFields();
      foreach ($fields as $field) {
         if (is_array($field)) {
            foreach ($field as $sfield) {
               $this->checkField($itiltype, $sfield);
            }
         } else {
            $this->checkField($itiltype, $field);
         }
      }
   }

   /**
    * Check one field
    *
    * @param string $itiltype ITIL type
    * @param string $field    Field name
    *
    * @return void
    */
   private function checkField($itiltype, $field) {
      global $DB;

      if (!\Toolbox::startsWith($field, '_') && 'items_id' != $field) {
         $this->boolean(
            $DB->fieldExists($itiltype::getTable(), $field)
         )->isTrue("$field in $itiltype");
      } else {
         //howto test dynamic fields (those wich names begin with a "_")?
         //howto test items_id (from Ticket at least)?
         $empty = true;
      }
   }

   protected function fieldsProvider() {
      return [
         [
            'Ticket',
            [
               1 => 'Title',
               21 => 'Description',
               12 => 'Status',
               10 => 'Urgency',
               11 => 'Impact',
               3 => 'Priority',
               15 => 'Opening date',
               4 => 'Requester',
               71 => 'Requester group',
               5 => 'Technician',
               8 => 'Technician group',
               6 => 'Assigned to a supplier',
               66 => 'Watcher',
               65 => 'Watcher group',
               7 => 'Category',
               13 => 'Associated elements',
               -2 => 'Approval request',
               142 => 'Documents',
               9 => 'Request source',
               83 => 'Location',
               37 => 'SLA&nbsp;Time to own',
               30 => 'SLA&nbsp;Time to resolve',
               190 => 'OLA&nbsp;Internal time to own',
               191 => 'OLA&nbsp;Internal time to resolve',
               18 => 'Time to resolve',
               155 => 'Time to own',
               180 => 'Internal time to resolve',
               185 => 'Internal time to own',
               45 => 'Total duration',
               52 => 'Approval',
               14 => 'Type',
            ]
         ], [
            'Change',
            [
               1 => 'Title',
               21 => 'Description',
               12 => 'Status',
               10 => 'Urgency',
               11 => 'Impact',
               3 => 'Priority',
               15 => 'Opening date',
               4 => 'Requester',
               71 => 'Requester group',
               5 => 'Technician',
               8 => 'Technician group',
               6 => 'Assigned to a supplier',
               66 => 'Watcher',
               65 => 'Watcher group',
               7 => 'Category',
               13 => 'Associated elements',
               -2 => 'Approval request',
               142 => 'Documents',
               60 => 'Analysis impact',
               61 => 'Control list',
               62 => 'Deployment plan',
               63 => 'Backup plan',
               67 => 'Checklist'
            ]
         ], [
            'Problem',
            [
               1 => 'Title',
               21 => 'Description',
               12 => 'Status',
               10 => 'Urgency',
               11 => 'Impact',
               3 => 'Priority',
               15 => 'Opening date',
               4 => 'Requester',
               71 => 'Requester group',
               5 => 'Technician',
               8 => 'Technician group',
               6 => 'Assigned to a supplier',
               66 => 'Watcher',
               65 => 'Watcher group',
               7 => 'Category',
               13 => 'Associated elements',
               -2 => 'Approval request',
               142 => 'Documents',
            ]
         ]
      ];
   }

   /**
    * @dataProvider fieldsProvider
    * */
   public function testGetFields($itemtype, $fields) {
      $tpl_class = '\\' . $itemtype . 'Template';
      $tpl = new $tpl_class;
      $class = $tpl_class . 'MandatoryField';
      $tpl_field = new $class();
      $result = $tpl_field->getAllFields($tpl);
      $this->array($result)->isIdenticalTo($fields);
   }

   /**
    * @dataProvider itilProvider
    */
   public function testGetTabNameForItem($itiltype) {
      $this->login();

      $tpl_class = '\\' . $itiltype . 'Template';
      $tpl = new $tpl_class;

      switch ($itiltype) {
         case 'Ticket':
            $expected = [
               1 => 'Standard interface',
               2 => 'Simplified interface'
            ];
            break;
         default:
            $expected = [
               1 => 'Preview'
            ];
            break;
      }
      $this->array($tpl->getTabNameForItem($tpl))->isIdenticalTo($expected);
   }

   /**
    * @dataProvider itilProvider
    */
   public function testTasks($itiltype) {
      $this->login();

      //create template
      $tpl_class = '\\' . $itiltype . 'Template';
      $tpl = new $tpl_class;

      $mandat_class = '\\' . $itiltype . 'TemplateMandatoryField';
      $mandat = new $mandat_class;

      $tpl_id = (int)$tpl->add([
         'name'   => 'Template for ' . $itiltype
      ]);
      $this->integer($tpl_id)->isGreaterThan(0);

      $task_tpl = new \TaskTemplate();
      $tid1 = (int)$task_tpl->add([
         'name'         => 'First task template',
         'content'      => 'First task content',
         'is_recursive' => 1
      ]);
      $this->integer($tid1)->isGreaterThan(0);
      $this->boolean($task_tpl->getFromDB($tid1))->isTrue();

      $tid2 = (int)$task_tpl->add([
         'name'         => 'Second task template',
         'content'      => 'Second task content',
         'is_recursive' => 1
      ]);
      $this->integer($tid1)->isGreaterThan(0);

      //add predefined tasks
      $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
      $predef = new $predef_class;
      $puid = (int)$predef->add([
         $mandat::$items_id   => $tpl_id,
         'num'                => $predef->getFieldNum($tpl, 'Tasks'),
         'value'              => $tid1,
         'is_recursive'       => 1
      ]);
      $this->integer($puid)->isGreaterThan(0);
      $this->boolean($predef->getFromDB($puid))->isTrue();

      $puid = (int)$predef->add([
         $mandat::$items_id   => $tpl_id,
         'num'                => $predef->getFieldNum($tpl, 'Tasks'),
         'value'              => $tid2,
         'is_recursive'       => 1
      ]);
      $this->integer($puid)->isGreaterThan(0);
      $this->boolean($predef->getFromDB($puid))->isTrue();

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
         'name'                  => 'Title is required',
         'content'               => 'A content for our ' . $itiltype,
         'itilcategories_id'     => $cat_id,
         $tpl_key                => $tpl_id,
         'entities_id'           => 0,
         '_tasktemplates_id'     => [
            $tid1,
            $tid2
         ]
      ];
      if ($itiltype === \Ticket::getType()) {
         $content['type'] = \Ticket::INCIDENT_TYPE;
      }

      $tid = (int)$object->add($content);
      $this->integer($tid)->isGreaterThan(0);

      global $DB;
      $task_class = $itiltype . 'Task';
      $iterator = $DB->request([
         'FROM'   => $task_class::getTable(),
         'WHERE'  => [
            $object->getForeignKeyField() => $tid
         ]
      ]);
      $this->integer(count($iterator))->isIdenticalTo(2);
   }
}
