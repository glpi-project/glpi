<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Features;

use Session;
use Toolbox;

/**
 * Objects that can have asset pictures (dropdowns and asset itemtypes directly).
 **/
trait AssetImage
{
    /**
     * Add/remove front, rear, and miscellaneous images
     * @param  array $input the form input
     * @return array        the altered input
     */
    public function managePictures($input)
    {
        foreach (['picture_front', 'picture_rear'] as $name) {
            if (
                isset($input["_blank_$name"])
                && $input["_blank_$name"]
            ) {
                $input[$name] = '';

                if (array_key_exists($name, $this->fields)) {
                    Toolbox::deletePicture($this->fields[$name]);
                }
            }

            if (isset($input["_$name"])) {
                $filename = array_shift($input["_$name"]);
                $src      = GLPI_TMP_DIR . '/' . $filename;

                $prefix   = '';
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
        $pictures_removed = false;
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
                $pictures_removed = true;
            }
        }

        $new_pictures = [];
        if (isset($input['_pictures'])) {
            $input_keys = array_keys($input['_pictures']);
            if (!isset($input['pictures'])) {
                $input['pictures'] = [];
            }
            foreach ($input_keys as $input_key) {
                $filename = $input["_pictures"][$input_key];
                $src = GLPI_TMP_DIR . '/' . $filename;

                $prefix = $input["_prefix_pictures"][$input_key] ?? '';

                if ($dest = Toolbox::savePicture($src, $prefix)) {
                    $new_pictures[$input_key] = $dest;
                } else {
                    Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
                }
            }
        }

        if ($pictures_removed || count($pictures) || count($new_pictures)) {
            $input['pictures'] = exportArrayToDB(array_merge($pictures, $new_pictures));
        }

        return $input;
    }
}
