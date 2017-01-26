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

/** @file
* @brief
* @since version 9.2
*/

include ('../inc/includes.php');

$upload_dir     = GLPI_TMP_DIR.'/';
$upload_handler = new GLPIUploadHandler(['upload_dir'      => $upload_dir,
                                          'param_name'     => $_REQUEST['name'] ,
                                          'orient_image'   => false,
                                          'image_versions' => []],
                                        false);
$response       = $upload_handler->post(false);

// clean compute display filesize
if (isset($response[$_REQUEST['name']]) && is_array($response[$_REQUEST['name']])) {
   foreach ($response[$_REQUEST['name']] as $key => &$val) {
      if (Document::isValidDoc(addslashes($val->name))) {
         if (isset($val->name)) {
            $val->display = $val->name;
         }
         if (isset($val->size)) {
            $val->filesize = Toolbox::getSize($val->size);
            if (isset($_REQUEST['showfilesize']) && $_REQUEST['showfilesize']) {
               $val->display = sprintf('%1$s %2$s', $val->display, $val->filesize);
            }
         }
      } else {
         // Unlink file
         $val->error = __('Filetype not allowed');
         if (file_exists($upload_dir.$val->name)) {
            unlink($upload_dir.$val->name);
         }
      }
      $val->id = 'doc'.$_REQUEST['name'].mt_rand();
   }
}

// send answer
$upload_handler->generate_response($response);
