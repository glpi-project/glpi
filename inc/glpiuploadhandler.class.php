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
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

include_once(GLPI_JQUERY_UPLOADHANDLER);

/** GLPIUploadHandler class
 *
 * @since version 9.2
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
}