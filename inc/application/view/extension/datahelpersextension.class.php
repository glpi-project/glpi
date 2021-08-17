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
use CommonDropdown;
use CommonGLPI;
use Dropdown;
use Glpi\Toolbox\RichText;
use Glpi\Toolbox\Sanitizer;
use Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class DataHelpersExtension extends AbstractExtension {

   public function getFilters(): array {
      return [
         new TwigFilter('formatted_datetime', [$this, 'getFormattedDatetime']),
         new TwigFilter('formatted_number', [$this, 'getFormattedNumber']),
         new TwigFilter('html_to_text', [$this, 'getTextFromHtml']),
         new TwigFilter('itemtype_name', [$this, 'getItemtypeName']),
         new TwigFilter('safe_html', [$this, 'getSafeHtml'], ['is_safe' => ['html']]),
         new TwigFilter('verbatim_value', [$this, 'getVerbatimValue']),
      ];
   }

   public function getFunctions(): array {
      return [
         new TwigFunction('get_item_comment', [$this, 'getItemComment']),
         new TwigFunction('get_item_link', [$this, 'getItemLink'], ['is_safe' => ['html']]),
         new TwigFunction('get_item_name', [$this, 'getItemName']),
      ];
   }

   /**
    * Return date formatted to user preferred format.
    *
    * @param mixed $datetime
    *
    * @return string|null
    */
   public function getFormattedDatetime($datetime): ?string {
      if (!is_string($datetime)) {
         return null;
      }
      return Html::convDateTime($datetime);
   }

   /**
    * Return number formatted to user preferred format.
    *
    * @param mixed $number
    *
    * @return string
    */
   public function getFormattedNumber($number): string {
      return Html::formatNumber($number);
   }

   /**
    * Return plain text from HTML (rich text).
    *
    * @param mixed $string             HTML string to be made safe
    * @param bool  $keep_presentation  Indicates whether the presentation elements have to be replaced by plaintext equivalents
    * @param bool  $compact            Indicates whether the output should be compact (limited line length, no links URL, ...)
    *
    * @return mixed
    */
   public function getTextFromHtml($string, bool $keep_presentation = true, bool $compact = false) {
      if (!is_string($string)) {
         return $string;
      }

      if (Sanitizer::isSanitized($string)) {
         $string = Sanitizer::unsanitize($string);
      }

      return RichText::getTextFromHtml($string, $keep_presentation, $compact);
   }

   /**
    * Returns typename of given itemtype.
    *
    * @param mixed $itemtype
    * @param number $count
    *
    * @return string|null
    */
   public function getItemtypeName($itemtype, $count = 1): ?string {
      if ($itemtype instanceof CommonGLPI || is_a($itemtype, CommonGLPI::class, true)) {
         return $itemtype::getTypeName($count);
      }
      return null;
   }

   /**
    * Return safe HTML (rich text).
    * Value will be made safe, whenever it has been sanitize (value fetched from DB),
    * or not (value computed during runtime).
    * Result will not be escaped, to prevent having to use `|raw` filter.
    *
    * @param mixed $string
    *
    * @return mixed
    */
   public function getSafeHtml($string) {
      if (!is_string($string)) {
         return $string;
      }

      if (Sanitizer::isSanitized($string)) {
         $string = Sanitizer::unsanitize($string);
      }

      return RichText::getSafeHtml($string);
   }

   /**
    * Return verbatim value for an itemtype field.
    * Returned value will be unsanitized if it has been transformed by GLPI sanitizing process (value fetched from DB).
    * Twig autoescaping system will then ensure that value is correctly escaped in redered HTML.
    *
    * @param mixed  $string
    *
    * @return mixed
    */
   public function getVerbatimValue($string) {
      if (is_string($string) && Sanitizer::isSanitized($string)) {
         $string = Sanitizer::unsanitize($string);
      }

      return $string;
   }

   /**
    * Returns name of the given item.
    * In case of a dropdown, it returns the translated name, otherwise, it returns the friendly name.
    *
    * @param CommonDBTM|string $item   Item instance of itemtype of the item.
    * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
    *
    * @return string|null
    */
   public function getItemName($item, ?int $id = null): ?string {
      if (is_a($item, CommonDropdown::class, true)) {
         $items_id = $item instanceof CommonDBTM ? $item->fields[$item->getIndexName()] : $id;
         $name = Dropdown::getDropdownName($item::getTable(), $items_id, false, true, false, '');
         return $this->getVerbatimValue($name);
      }

      if (($instance = $this->getItemInstance($item, $id)) === null) {
         return null;
      }

      return $this->getVerbatimValue($instance->getFriendlyName());
   }

   /**
    * Returns comment of the given item.
    * In case of a dropdown, it returns the translated comment.
    *
    * @param CommonDBTM|string $item   Item instance of itemtype of the item.
    * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
    *
    * @return string|null
    */
   public function getItemComment($item, ?int $id = null): ?string {
      if (is_a($item, CommonDropdown::class, true)) {
         $items_id = $item instanceof CommonDBTM ? $item->fields[$item->getIndexName()] : $id;
         $texts = Dropdown::getDropdownName($item::getTable(), $items_id, true, true, false, '');
         return $this->getVerbatimValue($texts['comment']);
      }

      if (($instance = $this->getItemInstance($item, $id)) === null) {
         return null;
      }

      return $instance->isField('comment') ? $this->getVerbatimValue($instance->fields['comment']) : null;
   }

   /**
    * Returns link of the given item.
    *
    * @param CommonDBTM|string $item   Item instance of itemtype of the item.
    * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
    *
    * @return string|null
    */
   public function getItemLink($item, ?int $id = null): ?string {
      if (($instance = $this->getItemInstance($item, $id)) === null) {
         return null;
      }

      return $instance->getLink();
   }

   /**
    * Returns instance of item with given ID.
    *
    * @param CommonDBTM|string $item   Item instance of itemtype of the item.
    * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
    *
    * @return CommonDBTM|null
    */
   private function getItemInstance($item, ?int $id = null): ?CommonDBTM {
      if (!is_a($item, CommonDBTM::class, true)) {
         return null;
      }

      if ($item instanceof CommonDBTM && ($id === null || $item->fields[$item->getIndexName()] === $id)) {
         return $item;
      }

      $instance = $id !== null ? $item::getById($id) : null;
      return $instance ?: null;
   }
}
