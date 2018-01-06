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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

include_once(GLPI_JQUERY_UPLOADHANDLER);

/** GLPIUploadHandler class
 *
 * @since 9.2
**/
class GLPIUploadHandler extends UploadHandler {

   protected function get_error_message($error) {
      switch ($error) {
         case UPLOAD_ERR_INI_SIZE:
            return __('The uploaded file exceeds the upload_max_filesize directive in php.ini');
            break;

         case UPLOAD_ERR_FORM_SIZE:
            return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
            break;

         case UPLOAD_ERR_PARTIAL:
            return __('The uploaded file was only partially uploaded');
            break;

         case UPLOAD_ERR_NO_FILE:
            return __('No file was uploaded');
            break;

         case UPLOAD_ERR_NO_TMP_DIR:
            return __('Missing a temporary folder');
            break;

         case UPLOAD_ERR_CANT_WRITE:
            return __('Failed to write file to disk');
            break;

         case UPLOAD_ERR_EXTENSION:
            return __('A PHP extension stopped the file upload');
            break;

         case 'post_max_size':
            return __('The uploaded file exceeds the post_max_size directive in php.ini');
            break;

         case 'max_file_size':
            return __('File is too big');
            break;

         case 'min_file_size':
            return __('File is too small');
            break;

         case 'max_number_of_files':
            return __('Maximum number of files exceeded');
            break;

         case 'max_width':
            return __('Image exceeds maximum width');
            break;

         case 'min_width':
            return __('Image requires a minimum width');
            break;

         case 'max_height':
            return __('Image exceeds maximum height');
            break;

         case 'min_height':
            return __('Image requires a minimum height');
            break;

      }

      return false;
   }

   static function uploadFiles($params = []) {
      $default_params = [
         'name'           => '',
         'showfilesize'   => false,
         'print_response' => true
      ];
      $params = array_merge($default_params, $params);

      $pname = $params['name'];
      $rand_name = uniqid('', true);
      foreach ($_FILES[$pname]['name'] as &$name) {
         $name = $rand_name . $name;
      }

      $upload_dir     = GLPI_TMP_DIR.'/';
      $upload_handler = new self(['upload_dir'     => $upload_dir,
                                  'param_name'     => $pname,
                                  'orient_image'   => false,
                                  'image_versions' => []],
                                 false);
      $response       = $upload_handler->post(false);

      // clean compute display filesize
      if (isset($response[$pname]) && is_array($response[$pname])) {
         foreach ($response[$pname] as $key => &$val) {
            if (Document::isValidDoc(addslashes($val->name))) {
               $val->prefix = $rand_name;
               if (isset($val->name)) {
                  $val->display = str_replace($rand_name, '', $val->name);
               }
               if (isset($val->size)) {
                  $val->filesize = Toolbox::getSize($val->size);
                  if (isset($params['showfilesize']) && $params['showfilesize']) {
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
            $val->id = 'doc'.$params['name'].mt_rand();
         }
      }

      // send answer
      return $upload_handler->generate_response($response, $params['print_response']);
   }
}
