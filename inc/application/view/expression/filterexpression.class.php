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

namespace Glpi\Application\View\Expression;

use Twig\Compiler;
use Twig\Node\Expression\FilterExpression as BaseExpression;

class FilterExpression extends BaseExpression
{
   protected function compileArguments(Compiler $compiler, $isArray = false): void {

      $compiler->raw($isArray ? '[' : '(');

      // Add source as first argument
      $compiler->raw('$this->source');

      // Compile arguments in a dedicated compiler and print them after removing their surrounding `()` or `[]`
      $args_compiler = new Compiler($compiler->getEnvironment());
      parent::compileArguments($args_compiler);
      if (strlen($args_source = substr($args_compiler->getSource(), 1, -1)) > 0) {
         $compiler->raw(', ');
         $compiler->raw($args_source);
      }

      $compiler->raw($isArray ? ']' : ')');
   }
}
