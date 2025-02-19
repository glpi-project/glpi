<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

declare(strict_types=1);

namespace Glpi\Controller\Rule;

use Glpi\Exception\Http\BadRequestHttpException;

trait RuleControllerTrait
{
    /**
     * @param class-string<\Rule> $item_subtype
     * @param int $entity
     * @return \RuleCollection
     * @throw BadRequestHttpException
     */
    private function getRuleCollectionInstanceFromRuleSubtype(string $item_subtype, int $entity): \RuleCollection
    {
        if (class_exists($item_subtype) === false) {
            throw new BadRequestHttpException(sprintf('Invalid rule subtype "%s"', htmlescape($item_subtype)));
        }
        $rule = new $item_subtype();
        $collection_classname = $rule->getCollectionClassName();

        /**
         * Not all classes extendending RuleCollection have a constructor.
         * Only \RuleCommonITILObjectCollection instances, so we can really pass an entity parameter to the constructor.
         */
        /* @phpstan-ignore-next-line */
        return new $collection_classname($entity);
    }
}
