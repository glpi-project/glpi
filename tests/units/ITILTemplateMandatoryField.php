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
class ITILTemplateMandatoryField extends DbTestCase {
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
}
