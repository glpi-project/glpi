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

use CommonDevice;
use CommonDropdown;
use Html;
use Glpi\Event;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;

final class DropdownFormController extends AbstractController
{
    #[Route("/Dropdown/Form/{class}", name: "glpi_dropdown_form")]
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        if (!$class) {
            throw new BadRequestException('The "class" attribute is mandatory for dropdown form routes.');
        }

        if (!\is_subclass_of($class, CommonDropdown::class)) {
            throw new BadRequestException('The "class" attribute must be a valid dropdown class.');
        }

        return new StreamedResponse(function () use ($class, $request) {
            $dropdown = new $class();
            $this->loadDropdownForm($request, $dropdown);
        });
    }

    public static function loadDropdownForm(Request $request, CommonDropdown $dropdown, ?array $options = null): void
    {
        if ($options !== null) {
            Toolbox::deprecated('Usage of `$options` parameter in DropdownFormController is deprecated.');
        }

        if (!$dropdown->canView()) {
            throw new AccessDeniedHttpException();
        }

        $input = $request->request->all();
        $id = (int) ($request->get('id') ?? -1);
        $in_modal = (bool) $request->get('_in_modal');

        if (isset($input["add"])) {
            $dropdown->check(-1, CREATE, $input);

            if ($newID = $dropdown->add($input)) {
                if ($dropdown instanceof CommonDevice) {
                    Event::log(
                        $newID,
                        $dropdown::class,
                        4,
                        "inventory",
                        \sprintf(
                            \__('%1$s adds the item %2$s'),
                            $_SESSION["glpiname"],
                            $input["designation"]
                        )
                    );
                } else {
                    Event::log(
                        $newID,
                        $dropdown::class,
                        4,
                        "setup",
                        \sprintf(\__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $input["name"])
                    );
                }
                if ($_SESSION['glpibackcreated']) {
                    $url = $dropdown->getLinkURL();
                    if ($in_modal) {
                        $url .= "&_in_modal=1";
                    }
                    Html::redirect($url);
                }
            }
            Html::back();
        } else if (isset($input["purge"])) {
            $dropdown->check($input["id"], PURGE);
            if (
                $dropdown->isUsed()
                && empty($input["forcepurge"])
            ) {
                Html::header(
                    $dropdown->getTypeName(1),
                    '',
                    "config",
                    $dropdown->second_level_menu,
                    \str_replace('glpi_', '', $dropdown->getTable())
                );
                $dropdown->showDeleteConfirmForm($request->getPathInfo());
                Html::footer();
            } else {
                $dropdown->delete($input, 1);

                Event::log(
                    $input["id"],
                    \get_class($dropdown),
                    4,
                    "setup",
                    //TRANS: %s is the user login
                    \sprintf(\__('%s purges an item'), $_SESSION["glpiname"])
                );
                $dropdown->redirectToList();
            }
        } else if (isset($input["replace"])) {
            $dropdown->check($input["id"], PURGE);
            $dropdown->delete($input, 1);

            Event::log(
                $input["id"],
                \get_class($dropdown),
                4,
                "setup",
                //TRANS: %s is the user login
                \sprintf(\__('%s replaces an item'), $_SESSION["glpiname"])
            );
            $dropdown->redirectToList();
        } else if (isset($input["update"])) {
            $dropdown->check($input["id"], UPDATE);
            $dropdown->update($input);

            Event::log(
                $input["id"],
                \get_class($dropdown),
                4,
                "setup",
                //TRANS: %s is the user login
                \sprintf(\__('%s updates an item'), $_SESSION["glpiname"])
            );
            Html::back();
        } else if (
            isset($input['execute'])
            && isset($input['_method'])
        ) {
            $method = 'execute' . $input['_method'];
            if (method_exists($dropdown, $method)) {
                \call_user_func([&$dropdown, $method], $input);
                Html::back();
            } else {
                throw new BadRequestHttpException();
            }
        } else if ($in_modal) {
            Html::popHeader(
                $dropdown->getTypeName(1),
                '',
                true,
                $dropdown->first_level_menu,
                $dropdown->second_level_menu,
                $dropdown->getType()
            );
            $dropdown->showForm($id);
            Html::popFooter();
        } else {
            if ($options === null) {
                $options = [];
            }
            $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';
            $options['id'] = $id;

            $dropdown::displayFullPageForItem($id, null, $options);
        }
    }
}
