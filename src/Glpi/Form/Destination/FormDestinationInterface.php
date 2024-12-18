<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Form\Destination;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;

interface FormDestinationInterface
{
    /**
     * Create one or multiple items for a given form and its answers
     *
     * @param Form       $form
     * @param AnswersSet $answers_set
     * @param array      $config
     *
     * @return \CommonDBTM[]
     *
     * @throws \Exception Must be thrown if the item can't be created
     */
    public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config
    ): array;


    /**
     * Render the configuration form for this destination type.
     *
     * @param Form  $form
     * @param array $config
     * @return string The rendered HTML content
     */
    public function renderConfigForm(Form $form, array $config): string;

    /**
     * Get itemtype to create
     *
     * @return string (Must be a valid CommonDBTM class name)
     */
    public static function getTargetItemtype(): string;

    /**
     * Get the search option used to filter the target itemtype by answers set.
     *
     * @return int
     */
    public static function getFilterByAnswsersSetSearchOptionID(): int;
}
