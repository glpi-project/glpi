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

use Glpi\Discover\Discover;
use Glpi\Discover\Discover_User;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

Session::checkLoginUser();


if (isset($_GET['lessonId']) && isset($_GET['action'])) {
    $discover_user = Discover_User::getForUser(Session::getLoginUserID());
    $lesson = $discover_user->getLesson($_GET['lessonId']);

    if ($lesson) {
        switch ($_GET['action']) {
            case 'restart':
                $discover_user->setLessonUncompleted($lesson['id']);
                // no break - Continue to start the lesson
            case 'start':
                // Check if the lesson isn't already completed
                if (!$discover_user->hasCompletedLesson($lesson['id'])) {
                    if (
                        !isset($_SESSION['glpidiscover'])
                        || !isset($_SESSION['glpidiscover']['start'])
                        || !in_array($lesson['id'], $_SESSION['glpidiscover']['start'])
                    ) {
                        $_SESSION['glpidiscover']['start'][] = $lesson['id'];
                    }

                    header('Location: ' . $CFG_GLPI['root_doc'] . '/' . ($lesson['navigateTo'] ?? ''));
                } else {
                    Session::addMessageAfterRedirect(__('Lesson already completed', 'discover'), false, ERROR);
                    Html::back();
                }
                break;
        }
    }
} else {
    if (Session::getCurrentInterface() == "central") {
        Html::header(Discover::getTypeName(1), $_SERVER['PHP_SELF'], 'discover');
    } else {
        Html::helpHeader(Discover::getTypeName(1));
    }

    $discover = new Discover();
    $discover->display(['main_class' => 'tab_cadre_fixe']);

    if (Session::getCurrentInterface() == "central") {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
