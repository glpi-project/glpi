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

/** @file
 * @brief
 * @since version 0.85
 **/


if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(__DIR__));
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/config/config.php");

Session::checkLoginUser();
// Load Language file
Session::loadLanguage();

include_once(GLPI_ROOT.'/lib/jqueryplugins/jquery-file-upload/server/php/UploadHandler.php');

$errors =  array(
        1 => __('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
        2 => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
        3 => __('The uploaded file was only partially uploaded'),
        4 => __('No file was uploaded'),
        6 => __('Missing a temporary folder'),
        7 => __('Failed to write file to disk'),
        8 => __('A PHP extension stopped the file upload'),
        'post_max_size'       => __('The uploaded file exceeds the post_max_size directive in php.ini'),
        'max_file_size'       => __('File is too big'),
        'min_file_size'       => __('File is too small'),
        'accept_file_types'   => __('Filetype not allowed'),
        'max_number_of_files' => __('Maximum number of files exceeded'),
        'max_width'           => __('Image exceeds maximum width'),
        'min_width'           => __('Image requires a minimum width'),
        'max_height'          => __('Image exceeds maximum height'),
        'min_height'          => __('Image requires a minimum height')
    );

$upload_dir = GLPI_TMP_DIR.'/';

$upload_handler = new UploadHandler(array('upload_dir'        => $upload_dir,
                                          'param_name'        => $_GET['name'],
                                          'orient_image'      => false,
                                          'image_versions'    => array()),
                                    false, $errors);
$response = $upload_handler->post(false);


// clean compute display filesize
if (isset($response[$_GET['name']]) && is_array($response[$_GET['name']])) {


   foreach ($response[$_GET['name']] as $key => &$val) {
      if (Document::isValidDoc(addslashes($val->name))) {
         if (isset($val->name)) {
            $val->display = $val->name;
         }
         if (isset($val->size)) {
            $val->filesize = Toolbox::getSize($val->size);
            if (isset($_GET['showfilesize']) && $_GET['showfilesize']) {
               $val->display = sprintf('%1$s %2$s', $val->display, $val->filesize);
            }
         }
      } else { // Unlink file
         $val->error = $errors['accept_file_types'];
	 if (file_exists($upload_dir.$val->name)) {
            @unlink($upload_dir.$val->name);
         }
      }
      $val->id = 'doc'.$_GET['name'].mt_rand();
   }
}

// Ajout du Doc + generation tag + autre traitement


$upload_handler->generate_response($response);
