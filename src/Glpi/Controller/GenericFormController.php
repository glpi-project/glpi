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

namespace Glpi\Controller;

use CommonDBTM;
use Glpi\Exception\Http\NotFoundHttpException;
use Html;
use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;

class GenericFormController extends AbstractController
{
    public const ACTIONS_AND_CHECKS = [
        'get' => ['permission' => READ],
        'add' => ['permission' => CREATE],
        'delete' => ['permission' => DELETE],
        'restore' => ['permission' => DELETE],
        'purge' => ['permission' => PURGE],
        'update' => ['permission' => UPDATE],
        'unglobalize' => ['permission' => UPDATE],
    ];

    #[Route("/{class}/Form", name: "glpi_itemtype_form", priority: -1)]
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        $this->checkIsValidClass($class);

        /** @var class-string<CommonDBTM> $class */

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }

        $form_action = $this->getCurrentAllowedAction($request, $class);

        if (!$form_action) {
            throw new AccessDeniedHttpException();
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
        $id = $request->query->get('id', -1);
        $object = new $class();
        $post_data = $request->request->all();
        $isTemplateForm = ($object->maybeTemplate() && $object->isTemplate()) || $request->query->get('withtemplate');

        // Permissions
        $object->check($id, self::ACTIONS_AND_CHECKS[$form_action]['permission'] ?? READ, $post_data);

        // Special case for GET
        if ($form_action === 'get' && $request->getMethod() === 'GET') {
            return $this->displayForm($object, $request, $isTemplateForm);
        }

        // POST action execution
        $action_result = match ($form_action) {
            'add' => $object->add($post_data),
            'delete' => $object->delete($post_data),
            'restore' => $object->restore($post_data),
            'purge' => $object->delete($post_data, 1),
            'update' => $object->update($post_data),
            'unglobalize' => $object->unglobalize(),
            default => throw new \RuntimeException(\sprintf("Unsupported object action \"%s\".", $form_action)),
        };

        if ($action_result) {
            Event::log(
                $action_result,
                \strtolower(\basename($class)),
                $object::getLogLevel(),
                $object::getLogServiceName(),
                sprintf(__('%1$s executes the "%2$s" action on the item %3$s'), $_SESSION["glpiname"], $form_action, $post_data["name"])
            );
        }

        // Specific case for "add"
        if ($action_result && $form_action === 'add' && $_SESSION['glpibackcreated']) {
            return new RedirectResponse($object->getLinkURL());
        }

        $post_action = $object::getPostFormAction($form_action) ?? 'list';
        if (!\in_array($post_action, ['back', 'list', 'form'])) {
            $post_action = 'list';
        }

        return match ($post_action) {
            'back' => new RedirectResponse(Html::getBackUrl()),
            'form' => new RedirectResponse($object::getFormURLWithID($id)),
            'list' => new RedirectResponse($object->getRedirectToListUrl()),
        };
    }

    /**
     * @param class-string<CommonDBTM> $class
     */
    private function getCurrentAllowedAction(Request $request, string $class): ?string
    {
        if ($request->getMethod() === 'POST') {
            foreach (\array_keys(self::ACTIONS_AND_CHECKS) as $action) {
                if (
                    $request->request->has($action)
                    && \method_exists($class, $action)
                ) {
                    return $action;
                }
            }
        }

        return $request->getMethod() === 'GET' ? 'get' : null;
    }

    public function displayForm(CommonDBTM $object, Request $request, bool $isTemplateForm): Response
    {
        $form_options = $object->getFormOptionsFromUrl($request->query->all());
        $form_options['formoptions'] = 'data-track-changes=true';
        if ($isTemplateForm) {
            $form_options['withtemplate'] = $request->query->get('withtemplate', '');
        }

        return $this->render('pages/generic_form.html.twig', [
            'id' => $request->query->get('id', 0),
            'object_class' => $object::class,
            'form_options' => $form_options,
        ]);
    }
}
