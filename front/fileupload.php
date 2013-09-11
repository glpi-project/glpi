<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(__DIR__));
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/config/config.php");

Session::checkLoginUser();
// Load Language file
Session::loadLanguage();

require_once (GLPI_ROOT.'/lib/jqueryplugins/jquery-file-upload/server/php/UploadHandler.php');

$upload_handler = new UploadHandler(array('upload_dir'        => GLPI_ROOT.'/files/_tmp/',
                                          'param_name'        => $_GET['name'],
                                          'orient_image'      => false,
                                          'image_versions'    => array()), false);
$response = $upload_handler->post(false);

// clean compute display filesize
if (isset($response[$_GET['name']]) && is_array($response[$_GET['name']])) {
   foreach ($response[$_GET['name']] as $key => &$val) {
      if (isset($val->name)) {
         $val->display = $val->name;
      }
      if (isset($val->size)) {
         $val->filesize = Toolbox::getSize($val->size);
         if (isset($_GET['showfilesize']) && $_GET['showfilesize']) {
            $val->display = sprintf('%1$s %2$s', $val->display, $val->filesize);
         }
      }
      $val->id = 'doc'.$_GET['name'].mt_rand();
   }
}

// Ajout du Doc + generation tag + autre traitement


$upload_handler->generate_response($response);
?>