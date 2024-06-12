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

namespace Glpi\Discover;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;
use Update;

final class Discover extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        // Always plural
        return __('Lessons');
    }

    /**
     * Load all the discover stuff if needed
     *
     * @return void
     */
    public static function loadDiscover(): void
    {
        // Don't load discover if we are on the update page
        if (!isset($_GET["donotcheckversion"]) && !Update::isDbUpToDate() && !defined('SKIP_UPDATES')) {
            return;
        }

        $discover_user = Discover_User::getForUser(Session::getLoginUserID());
        $lesson = $discover_user->getLessonToStart();

        if ($lesson && count($lesson['steps']) > 0) {
            echo Html::css('public/lib/introjs.css');
            echo Html::scss('css/standalone/introjs.scss');
            echo Html::script('public/lib/introjs.js');
            echo Html::script('js/discover.js');

            TemplateRenderer::getInstance()->display('discover/lessons.js.twig', [
                'lesson' => $lesson,
                'steps' => $lesson['steps'],
                'endingSteps' => isset($lesson['showEndingLesson']) && $lesson['showEndingLesson']
                    ? $discover_user->getEndingLesson()['steps']
                    : [],
            ]);
        }
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);

        $ong['no_all_tab'] = true;

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return __('Lessons');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $discover_user = Discover_User::getForUser(Session::getLoginUserID());
        $lessons = array_filter(
            $discover_user->getLessons(),
            fn ($lesson) => $lesson['id'] !== $discover_user->getEndingLesson()['id']
        );
        $progression = array_combine(
            array_column($lessons, 'id'),
            array_map(fn ($lesson) => $discover_user->getLessonProgression($lesson['id']), $lessons)
        );

        TemplateRenderer::getInstance()->display('discover/lessons.html.twig', [
            'completed_lessons' => $discover_user->getCompletedLessons(),
            'progressions' => $progression,
            'lessons' => $lessons,
        ]);

        return true;
    }
}
