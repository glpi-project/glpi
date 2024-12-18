<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\Export\Context;

use Glpi\Form\Export\Specification\ContentSpecificationInterface;

/**
 * Must be implemented by all JsonFieldInterface objects that contains references
 * foreign keys.
 *
 * The method of this interface will be used by the form serializer to ensure
 * that form exports can be done correctly as an export can't contains hardcoded
 * database foreign keys.
 */
interface ConfigWithForeignKeysInterface
{
    /**
     * Must return one JsonConfigForeignKeyHandlerInterface per serialized key that
     * will contains foreign keys data.
     *
     * @param \Glpi\Form\Export\Specification\ContentSpecificationInterface $content_spec
     * @return \Glpi\Form\Export\Context\ForeignKey\JsonConfigForeignKeyHandlerInterface[]
     */
    public static function listForeignKeysHandlers(ContentSpecificationInterface $content_spec): array;
}
