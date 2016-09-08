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

/** @file
* @brief
*/

if (strpos($_SERVER['PHP_SELF'],"dropdownRubDocument.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["rubdoc"])) {
   $used = array();

   // Clean used array
   if (isset($_POST['used']) && is_array($_POST['used']) && (count($_POST['used']) > 0)) {
      $used_qry = '';
      foreach ($_POST['used'] as $current_used) {
         if ($used_qry !== '') {
            $used_qry .= ', ';
         }
         $used_qry .= intval($current_used);
      }
      $query = "SELECT `id`
                FROM `glpi_documents`
                WHERE `id` IN (".$used_qry.")
                      AND `documentcategories_id` = '".intval($_POST["rubdoc"])."'";

      foreach ($DB->request($query) AS $data) {
         $used[$data['id']] = $data['id'];
      }
   }

   if (preg_match('/[^a-z_\-0-9]/i', $_POST['myname'])) {
      throw new \RuntimeException('Invalid name provided!');
   }

   if (!isset($_POST['entity']) || $_POST['entity'] === '') {
      $_POST['entity'] = $_SESSION['glpiactive_entity'];
   }

   Dropdown::show('Document',
                  array('name'      => $_POST['myname'],
                        'used'      => $used,
                        'width'     => '50%',
                        'entity'    => intval($_POST['entity']),
                        'rand'      => intval($_POST['rand']),
                        'condition' => "glpi_documents.documentcategories_id='".intval($_POST["rubdoc"])."'"));

}
?>
