<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use CommonGLPI;
use Html;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class RoutingExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('index', [$this, 'index']),
         new TwigFunction('path', [$this, 'path']),
         new TwigFunction('form_path', [$this, 'formPath']),
         new TwigFunction('url', [$this, 'url']),
         new TwigFunction('form_url', [$this, 'formUrl']),
      ];
   }


   /**
    * Return index path.
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function index(): string {
      $index = (Session::getCurrentInterface() == 'helpdesk')
         ? "helpdesk.public.php"
         : "central.php";
      return Html::getPrefixedUrl("front/$index");
   }


   /**
    * Return domain-relative path of a resource.
    *
    * @param string $resource
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function path(string $resource): string {
      return Html::getPrefixedUrl($resource);
   }

   /**
    * Return domain-relative path of itemtype form.
    *
    * @param string $itemtype
    * @param null|int $id
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function formPath(string $itemtype, ?int $id = null): string {
      if (!is_a($itemtype, CommonGLPI::class, true)) {
         throw new \Exception(sprintf('Unable to generate form path of item "%s".', $itemtype));
      }

      return $id !== null ? $itemtype::getFormURLWithID($id) : $itemtype::getFormURL();
   }

   /**
    * Return absolute URL of a resource.
    *
    * @param string $resource
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function url(string $resource): string {
      global $CFG_GLPI;

      $prefix = $CFG_GLPI['url_base'];
      if (substr($resource, 0, 1) != '/') {
         $prefix .= '/';
      }

      return $prefix . $resource;
   }

   /**
    * Return absolute URL of itemtype form.
    *
    * @param string $itemtype
    * @param null|int $id
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function formUrl(string $itemtype, ?int $id = null): string {
      if (!is_a($itemtype, CommonGLPI::class, true)) {
         throw new \Exception(sprintf('Unable to generate form path of item "%s".', $itemtype));
      }

      $resource = $id !== null ? $itemtype::getFormURLWithID($id, false) : $itemtype::getFormURL(false);

      return $this->url($resource);
   }
}
