<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Features;

use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Objects that can have asset pictures (dropdowns and asset itemtypes directly).
 **/
trait AssetImage {

   /**
    * Add/remove front, rear, and miscellaneous images
    * @param  array $input the form input
    * @return array        the altered input
    */
   function managePictures($input) {
      foreach (['picture_front', 'picture_rear'] as $name) {
         if (isset($input["_blank_$name"])
            && $input["_blank_$name"]) {
            $input[$name] = '';

            if (array_key_exists($name, $this->fields)) {
               Toolbox::deletePicture($this->fields[$name]);
            }
         }

         if (isset($input["_$name"])) {
            $filename = array_shift($input["_$name"]);
            $src      = GLPI_TMP_DIR . '/' . $filename;

            $prefix   = null;
            if (isset($input["_prefix_$name"])) {
               $prefix = array_shift($input["_prefix_$name"]);
            }

            if ($dest = Toolbox::savePicture($src, $prefix)) {
               $input[$name] = $dest;
            } else {
               Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
            }

            if (array_key_exists($name, $this->fields)) {
               Toolbox::deletePicture($this->fields[$name]);
            }
         }
      }

      $pictures = [];
      if ($this->isField('pictures')) {
         $input_keys = array_keys($input);
         $pictures = importArrayFromDB($this->fields['pictures']);
         $to_remove = [];
         foreach ($input_keys as $input_key) {
            if (strpos($input_key, '_blank_pictures_') === 0 && $input[$input_key]) {
               $i = (int)str_replace('_blank_pictures_', '', $input_key);
               if (isset($pictures[$i])) {
                  Toolbox::deletePicture($pictures[$i]);
                  $to_remove[] = $i;
               }
            }
         }
         $to_remove = array_reverse($to_remove);
         foreach ($to_remove as $i) {
            unset($pictures[$i]);
         }
      }

      $new_pictures = [];
      if (isset($input['_pictures'])) {
         $pic_count = count($input['_pictures']);
         if (!isset($input['pictures'])) {
            $input['pictures'] = [];
         }
         for ($i = 0; $i < $pic_count; $i++) {
            $filename = $input["_pictures"][$i];
            $src = GLPI_TMP_DIR . '/' . $filename;

            $prefix = $input["_prefix_pictures"][$i] ?? null;

            if ($dest = Toolbox::savePicture($src, $prefix)) {
               $new_pictures[$i] = $dest;
            } else {
               Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
            }
         }
      }

      if (count($pictures) || count($new_pictures)) {
         $input['pictures'] = exportArrayToDB(array_merge($pictures, $new_pictures));
      }

      return $input;
   }
}
