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

use Glpi\Form\Dropdown\FormActorsDropdown;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeRequester;

include(__DIR__ . '/getAbstractRightDropdownValue.php');

Session::checkLoginUser();

if (Session::getCurrentInterface() !== 'central') {
    $questions = (new Question())->find([
        'type' => [
            QuestionTypeAssignee::class,
            QuestionTypeObserver::class,
            QuestionTypeRequester::class
        ]
    ]);

    // Check if the user can view at least one question
    if (array_reduce($questions, fn($acc, $question) => $acc || $question->canViewItem(), false) === false) {
        http_response_code(403);
        exit();
    }
}

show_rights_dropdown(FormActorsDropdown::class);
