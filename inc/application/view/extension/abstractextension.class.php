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

use Glpi\Application\View\Expression\FilterExpression;
use Glpi\Application\View\Expression\FunctionExpression;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\ExtensionInterface;

/**
 * @since 10.0.0
 */
class AbstractExtension implements ExtensionInterface {

   /**
    * @var \Twig\TwigFilter[]
    */
   protected $filters = [];

   /**
    * @var \Twig\TwigFunction[]
    */
   protected $functions = [];

   /**
    * Registers a filter.
    *
    * @param string $name
    * @param callable $callable
    * @param array $options
    * @param bool $exposed_to_plugins
    *
    * @return void
    */
   protected function registerFilter(
      string $name,
      ?callable $callable = null,
      array $options = [],
      bool $exposed_to_plugins = false
   ): void {
      $options['node_class'] = FilterExpression::class;

      $this->filters[$name] = $this->getCallableWrapper(
         TwigFilter::class,
         $name,
         $callable,
         $options,
         $exposed_to_plugins
      );
   }

   /**
    * Registers a function.
    *
    * @param string $name
    * @param callable $callable
    * @param array $options
    * @param bool $exposed_to_plugins
    */
   protected function registerFunction(
      string $name,
      callable $callable,
      array $options = [],
      bool $exposed_to_plugins = false
   ): void {
      $options['node_class'] = FunctionExpression::class;

      $this->functions[$name] = $this->getCallableWrapper(
         TwigFunction::class,
         $name,
         $callable,
         $options,
         $exposed_to_plugins
      );
   }

   public function getFilters() {
      return $this->filters;
   }

   public function getFunctions() {
      return $this->functions;
   }

   public function getTokenParsers() {
      return [];
   }

   public function getNodeVisitors() {
      return [];
   }

   public function getTests() {
      return [];
   }

   public function getOperators() {
      return [];
   }

   private function getCallableWrapper(
      string $type,
      string $name,
      callable $callable,
      array $options,
      bool $exposed_to_plugins
   ) /*: TwigFilter|TwigFunction */ {
      return new $type(
         $name,
         function (Source $source, ...$params) use ($type, $name, $callable, $exposed_to_plugins) {
            // Check availability in source context.
            if (!$exposed_to_plugins && preg_match('/@[a-z]+\//', $source->getName())) {
               trigger_error(
                  sprintf(
                     'Usage of "%s" %s is not allowed in plugins (used in %s)',
                     $name,
                     strtolower(preg_replace('/^Twig\\\Twig/', '', $type)),
                     $source->getName()
                  ),
                  E_USER_ERROR
               );
            }

            // Forward to registered callable.
            return call_user_func_array($callable, $params);
         },
         $options
      );
   }
}
