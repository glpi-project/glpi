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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class ModelExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('getmodelPicture', [$this, 'getmodelPicture']),
         new TwigFunction('getItemtypeOrModelPicture', [$this, 'getItemtypeOrModelPicture'])
      ];
   }

   public function getmodelPicture(CommonDBTM $item, string $picture_field = "picture_front") {
      $itemtype  = $item->getType();
      $modeltype = $itemtype."Model";
      if (class_exists($modeltype)) {
         $model = new $modeltype;
         if (!$model->isField($picture_field)) {
            return "";
         }

         $fk = getForeignKeyFieldForItemType($modeltype);
         if ($model->getFromDB(($item->fields[$fk]) ?? 0)) {
            if ($picture_field === 'pictures') {
               return importArrayFromDB($model->fields[$picture_field]);
            }
            return $model->fields[$picture_field];
         }
      }

      return "";
   }

   public function getItemtypeOrModelPicture(CommonDBTM $item, string $picture_field = "picture_front", $params = []): array {
      $p = [
         'thumbnail_w'  => 'auto',
         'thumbnail_h'  => 'auto'
      ];
      $p = array_replace($p, $params);

      $urls = [];
      $itemtype = $item->getType();
      $pictures = [];
      $clearable = false;

      if ($item->isField($picture_field)) {
         if ($picture_field === 'pictures') {
            $urls = importArrayFromDB($item->fields[$picture_field]);
         } else {
            $urls = [$item->fields[$picture_field]];
         }
         $clearable = $item::canUpdate();
      } else {
         $modeltype = $itemtype . "Model";
         if (class_exists($modeltype)) {
            /** @var CommonDBTM $model */
            $model = new $modeltype;
            if (!$model->isField($picture_field)) {
               return [];
            }

            $fk = getForeignKeyFieldForItemType($modeltype);
            if ($model->getFromDB(($item->fields[$fk]) ?? 0)) {
               if ($picture_field === 'pictures') {
                  $urls = importArrayFromDB($model->fields[$picture_field]);
               } else {
                  $urls = [$model->fields[$picture_field]];
               }
            }
         }
      }

      foreach ($urls as $url) {
         if (!empty($url)) {
            $resolved_url = \Toolbox::getPictureUrl($url);
            $src_file = GLPI_DOC_DIR . '/_pictures/' . '/' . $url;
            if (file_exists($src_file)) {
               $size = getimagesize($src_file);
               $pictures[] = [
                     'src'             => $resolved_url,
                     'w'               => $size[0],
                     'h'               => $size[1],
                     'clearable'       => $clearable,
                     '_is_model_img'   => isset($model)
                  ] + $p;
            } else {
               $owner_type = isset($model) ? $model::getType() : $itemtype;
               $owner_id = isset($model) ? $model->getID() : $item->getID();
               \Toolbox::logWarning("The picture '{$src_file}' referenced by the {$owner_type} with ID {$owner_id} does not exist");
            }
         }
      }

      return $pictures;
   }
}
