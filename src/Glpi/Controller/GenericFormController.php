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

namespace Glpi\Controller;

use CommonDBTM;
use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Http\RedirectResponse;
use Html;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display a form for a given item type or handle form submission.
 *
 * - Use GET method to display a form.
 * - Use POST method to submit a form.
 */
class GenericFormController extends AbstractController
{
    private const SUPPORTED_ACTIONS = [
        'add',
        'delete',
        'restore',
        'purge',
        'update',
        'unglobalize',
    ];

    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        $this->checkIsValidClass($class);

        /** @var class-string<CommonDBTM> $class */

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }

        $form_action = $this->getFormAction($request);

        if (!$form_action) {
            throw new BadRequestHttpException();
        }

        return $this->handleFormAction($request, $form_action, $class);
    }

    private function checkIsValidClass(string $class): void
    {
        if (!$class) {
            throw new BadRequestHttpException('The "class" attribute is mandatory for dropdown routes.');
        }

        if (!\class_exists($class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" does not exist.", $class));
        }

        if (!\is_subclass_of($class, CommonDBTM::class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" is not a DB object.", $class));
        }
    }

    /**
     * @param class-string<CommonDBTM> $class
     */
    private function handleFormAction(Request $request, string $form_action, string $class): Response
    {
        $id = $request->isMethod('GET') ? $request->query->get('id', -1) : $request->request->get('id', -1);
        $post_data = $request->request->all();

        /* @var CommonDBTM $object */
        $object = getItemForItemtype($class);

        if (!$object::isNewID($id) && !$object->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        // Special case for get
        if ($form_action === 'get') {
            if (!$object->can($id, READ)) {
                throw new AccessDeniedHttpException();
            }
            return $this->displayForm($object, $request);
        }
        // Special case for modals
        if ($form_action === '_in_modal') {
            return $this->displayModal($object, $request);
        }

        // Permissions
        $can_do_action = match ($form_action) {
            'add' => $object->can($id, CREATE, $post_data),
            'delete', 'restore' => $object->can($id, DELETE, $post_data),
            'purge' => $object->can($id, PURGE, $post_data),
            'update', 'unglobalize' => $object->can($id, UPDATE, $post_data),
            default => throw new RuntimeException(\sprintf("Unsupported object action \"%s\".", $form_action)),
        };

        if (!$can_do_action) {
            throw new AccessDeniedHttpException();
        }

        // POST action execution
        $action_result = match ($form_action) {
            'add' => $object->add($post_data),
            'delete' => $object->delete($post_data),
            'restore' => $object->restore($post_data),
            'purge' => $object->delete($post_data, true),
            'update' => $object->update($post_data),
            'unglobalize' => $object->unglobalize(),
            default => throw new RuntimeException(\sprintf("Unsupported object action \"%s\".", $form_action)),
        };

        if ($action_result) {
            Event::log(
                $action_result,
                $class,
                $object::getLogDefaultLevel(),
                $object::getLogDefaultServiceName(),
                sprintf(
                    __('%1$s executes the "%2$s" action on the item %3$s'),
                    $_SESSION["glpiname"],
                    $form_action,
                    $post_data["name"] ?? NOT_AVAILABLE
                )
            );
        }

        $post_action = $object::getPostFormAction($form_action, $action_result);

        return match ($post_action) {
            'backcreated' => $_SESSION['glpibackcreated']
                ? new RedirectResponse($object->getLinkURL())
                : new RedirectResponse(Html::getBackUrl()),
            'back' => new RedirectResponse(Html::getBackUrl()),
            'form' => new RedirectResponse($object->getLinkURL()),
            'list' => new RedirectResponse($object->getRedirectToListUrl()),
            default => new RedirectResponse($object->getLinkURL()),
        };
    }

    private function getFormAction(Request $request): ?string
    {
        if ($request->getMethod() === 'POST') {
            foreach (self::SUPPORTED_ACTIONS as $action) {
                if ($request->request->has($action)) {
                    return $action;
                }
            }
        }

        if ($request->getMethod() === 'GET' && $request->query->get('_in_modal')) {
            return '_in_modal';
        }

        return $request->getMethod() === 'GET' ? 'get' : null;
    }

    /**
     * Display a full form page
     */
    private function displayForm(CommonDBTM $object, Request $request): Response
    {
        $form_options = $object->getFormOptionsFromUrl($request->query->all());
        $form_options['formoptions'] = 'data-track-changes=true';
        if ($object->maybeTemplate()) {
            $form_options['withtemplate'] = $request->query->get('withtemplate', '');
        }

        return $this->render('pages/generic_form.html.twig', [
            'id' => $request->query->get('id', -1),
            'object_class' => $object::class,
            'form_options' => $form_options,
        ]);
    }

    /**
     * Display a modal form
     */
    private function displayModal(mixed $object, Request $request): Response
    {
        $form_options = [];
        if ($object->maybeTemplate()) {
            $form_options['withtemplate'] = $request->query->get('withtemplate', '');
        }

        return $this->render('pages/generic_in_modal.html.twig', [
            'id' => $request->query->get('id', -1),
            'object' => $object,
            'form_options' => $form_options,
        ]);
    }
}
