<?php

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
