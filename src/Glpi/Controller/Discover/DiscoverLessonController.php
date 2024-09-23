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

namespace Glpi\Controller\Discover;

use Glpi\Controller\AbstractController;
use Glpi\Discover\Discover_User;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DiscoverLessonController extends AbstractController
{
    #[Route(
        "/Discover/Lesson/{lessonId}/start",
        name: "glpi_discover_lesson_start",
        methods: "GET"
    )]
    public function start(Request $request): Response
    {
        $lesson_id = $request->attributes->getString('lessonId');

        if (!$lesson_id) {
            throw new BadRequestException('The "lessonId" attribute is mandatory for lesson routes.');
        }

        $discover_user = Discover_User::getForUser(Session::getLoginUserID());
        $lesson = $discover_user->getLesson($lesson_id);

        if (!$lesson) {
            throw new BadRequestException('The lesson does not exist.');
        }

        return $this->startLesson($discover_user, $lesson);
    }

    #[Route(
        "/Discover/Lesson/{lessonId}/restart",
        name: "glpi_discover_lesson_restart",
        methods: "GET"
    )]
    public function restart(Request $request): Response
    {
        $lesson_id = $request->attributes->getString('lessonId');

        if (!$lesson_id) {
            throw new BadRequestException('The "lessonId" attribute is mandatory for lesson routes.');
        }

        $discover_user = Discover_User::getForUser(Session::getLoginUserID());
        $lesson = $discover_user->getLesson($lesson_id);

        if (!$lesson) {
            throw new BadRequestException('The lesson does not exist.');
        }

        // Uncomplete the lesson
        $discover_user->setLessonUncompleted($lesson['id']);

        // Start the lesson
        return $this->startLesson($discover_user, $lesson);
    }

    #[Route(
        "/Discover/Lesson/{lessonId}/complete",
        name: "glpi_discover_lesson_complete",
        methods: "POST"
    )]
    public function complete(Request $request): Response
    {
        $lesson_id = $request->attributes->getString('lessonId');

        if (!$lesson_id) {
            throw new BadRequestException('The "lessonId" attribute is mandatory for lesson routes.');
        }

        $discover_user = Discover_User::getForUser(Session::getLoginUserID());
        $lesson = $discover_user->getLesson($lesson_id);

        if (!$lesson) {
            throw new BadRequestException('The lesson does not exist.');
        }

        // Complete the lesson
        $discover_user->setLessonCompleted($lesson['id']);

        return new Response('Lesson completed', Response::HTTP_CREATED);
    }

    /**
     * Start the lesson
     *
     * @param Discover_User $discover_user
     * @param array $lesson
     * @return RedirectResponse
     */
    private function startLesson(Discover_User $discover_user, array $lesson): RedirectResponse
    {
        // Dosn't start the lesson if it's already completed
        if ($discover_user->hasCompletedLesson($lesson['id'])) {
            Session::addMessageAfterRedirect(__('Lesson already completed', 'discover'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        if (
            !isset($_SESSION['glpidiscover'])
            || !isset($_SESSION['glpidiscover']['start'])
            || !in_array($lesson['id'], $_SESSION['glpidiscover']['start'])
        ) {
            $_SESSION['glpidiscover']['start'][] = $lesson['id'];
        }

        return new RedirectResponse($lesson['navigateTo'] ?? '/');
    }
}
