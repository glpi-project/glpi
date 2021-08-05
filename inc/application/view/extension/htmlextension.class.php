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

use Glpi\Toolbox\RichText;
use Glpi\Toolbox\Sanitizer;
use Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class HtmlExtension extends AbstractExtension {

   public function getFilters(): array {
      return [
         new TwigFilter('conv_datetime', [$this, 'convDateTime'], ['is_safe' => ['html']]),
         new TwigFilter('html_to_text', [$this, 'getTextFromHtml']),
         new TwigFilter('safe_html', [$this, 'getSafeHtml'], ['is_safe' => ['html']]),
         new TwigFilter('verbatim_value', [$this, 'getVerbatimValue']),
      ];
   }

   public function getFunctions(): array {
      return [
         new TwigFunction('showMassiveActions', [$this, 'showMassiveActions']),
         new TwigFunction('isMassiveActionchecked', [$this, 'isMassiveActionchecked']),
         new TwigFunction('formatNumber', [Html::class, 'formatNumber'], ['is_safe' => ['html']]),
         new TwigFunction('time2str', [Html::class, 'time2str'], ['is_safe' => ['html']]),
         new TwigFunction('timestampToString', [Html::class, 'timestampToString'], ['is_safe' => ['html']]),
         new TwigFunction('convDateTime', [Html::class , 'convDateTime']),
         new TwigFunction('Html__uploadedFiles', [Html::class , 'uploadedFiles'], ['is_safe' => ['html']]),
         new TwigFunction('Html__initEditorSystem', [Html::class , 'initEditorSystem'], ['is_safe' => ['html']]),
         new TwigFunction('Html__cleanId', [Html::class , 'cleanId'], ['is_safe' => ['html']]),
         new TwigFunction('Html__file', [Html::class , 'file'], ['is_safe' => ['html']]),
         new TwigFunction('Html__getCheckbox', [Html::class , 'getCheckbox'], ['is_safe' => ['html']]),
         new TwigFunction('Html__showToolTip', [Html::class , 'showToolTip'], ['is_safe' => ['html']]),
         new TwigFunction('Html__showCheckbox', [Html::class , 'showCheckbox'], ['is_safe' => ['html']]),
         new TwigFunction('Html__parseAttributes', [Html::class, 'parseAttributes'], ['is_safe' => ['html']]),
      ];
   }

   public function convDateTime(string $datetime, string $format = null): string {
      return Html::convDateTime($datetime, $format);
   }

   public function showMassiveActions(array $params = []): string {
      return Html::showMassiveActions($params + ['display' => false]);
   }

   public function isMassiveActionchecked(string $itemtype = "", int $id = 0): bool {
      return isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]);
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
}
