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

use Glpi\Toolbox\Sanitizer;
use Html;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class HtmlExtension extends AbstractExtension implements ExtensionInterface {

   public function getFilters() {
      return [
         new TwigFilter('conv_datetime', [$this, 'convDateTime'], ['is_safe' => ['html']]),
         new TwigFilter(
            'safe_value',
            [$this, 'getSafeValue'],
            ['needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe']
         ),
      ];
   }

   public function getFunctions() {
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
    * Return safe value for an itemtype field.
    * Value will be made safe, whenever it has been sanitize (value fetched from DB),
    * or not (value computed during runtime).
    * It will then be escaped like it would be by the `escape` Twig filter.
    *
    * @param Environment $env
    * @param mixed  $string     The value to be escaped
    * @param string $strategy   The escaping strategy
    * @param string $charset    The charset
    * @param bool   $autoescape Whether the function is called by the auto-escaping feature (true) or by the developer (false)
    *
    * @return mixed
    *
    * @see twig_escape_filter()
    */
   public function getSafeValue(Environment $env, $string, $strategy = 'html', $charset = null, $autoescape = false) {
      if (is_string($string) && Sanitizer::isSanitized($string)) {
         $string = Sanitizer::unsanitize($string);
      }

      return twig_escape_filter($env, $string, $strategy, $charset, $autoescape);
   }
}
