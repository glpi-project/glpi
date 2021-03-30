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
use Plugin;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class PluginExtension extends AbstractExtension implements ExtensionInterface {
   public function getFunctions() {
      return [
         new TwigFunction('displayLogin', [$this, 'displayLogin'], ['is_safe' => ['html']]),
         new TwigFunction('AutoInventoryInformation', [$this, 'AutoInventoryInformation'], ['is_safe' => ['html']]),
         new TwigFunction('preItemForm', [$this, 'preItemForm'], ['is_safe' => ['html']]),
         new TwigFunction('postItemForm', [$this, 'postItemForm'], ['is_safe' => ['html']]),
         new TwigFunction('hook_infocom', [$this, 'hook_infocom'], ['is_safe' => ['html']]),
      ];
   }

   /**
    * call display_login plugin hook to display aditionnal html on login page
    *
    * @return void
    */
   public function displayLogin() {
      Plugin::doHook('display_login');
   }


   public function AutoInventoryInformation() {
      Plugin::doHook('autoinventory_information');
   }

   public function preItemForm(CommonDBTM $item, array $params = []) {
      Plugin::doHook('pre_item_form', ['item' => $item, 'options' => $params]);
   }

   public function postItemForm(CommonDBTM $item, array $params = []) {
      Plugin::doHook('post_item_form', ['item' => $item, 'options' => $params]);
   }

   public function hook_infocom(CommonDBTM $item) {
      Plugin::doHookFunction("infocom", $item);
   }
}
