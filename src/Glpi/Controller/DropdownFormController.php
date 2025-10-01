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

use CommonDevice;
use CommonDropdown;
use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RedirectResponse;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Toolbox;

use function Safe\ob_get_clean;
use function Safe\ob_start;

class DropdownFormController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        if (!$class) {
            throw new BadRequestHttpException('The "class" attribute is mandatory for dropdown form routes.');
        }

        if (!\is_subclass_of($class, CommonDropdown::class)) {
            throw new BadRequestHttpException('The "class" attribute must be a valid dropdown class.');
        }

        $dropdown = new $class();

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
                    return new RedirectResponse($url);
                }
            }

            return new RedirectResponse(Html::getBackUrl());
        }

        if (isset($input["purge"])) {
            $dropdown->check($input["id"], PURGE);
            if (
                $dropdown->isUsed()
                && empty($input["forcepurge"])
            ) {
                ob_start();
                Html::header(
                    ...$dropdown->getHeaderParameters()
                );
                $dropdown->showDeleteConfirmForm();
                Html::footer();
                $content = ob_get_clean();

                return new Response($content);
            } else {
                $dropdown->delete($input, true);

                Event::log(
                    $input["id"],
                    \get_class($dropdown),
                    4,
                    "setup",
                    //TRANS: %s is the user login
                    \sprintf(\__('%s purges an item'), $_SESSION["glpiname"])
                );

                return new RedirectResponse($dropdown->getRedirectToListUrl());
            }
        }

        if (isset($input["replace"])) {
            $dropdown->check($input["id"], PURGE);
            $dropdown->delete($input, true);

            Event::log(
                $input["id"],
                \get_class($dropdown),
                4,
                "setup",
                //TRANS: %s is the user login
                \sprintf(\__('%s replaces an item'), $_SESSION["glpiname"])
            );

            return new RedirectResponse($dropdown->getRedirectToListUrl());
        }

        if (isset($input["update"])) {
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

            return new RedirectResponse(Html::getBackUrl());
        }

        if (isset($input['execute']) && isset($input['_method'])) {
            $method = 'execute' . $input['_method'];
            if (method_exists($dropdown, $method)) {
                Toolbox::deprecated('Defining method to execute throught `execute` and `_method` inputs is deprecated for security reasons. Please use a dedicated controller action instead.');
                \call_user_func([&$dropdown, $method], $input);

                return new RedirectResponse(Html::getBackUrl());
            } else {
                throw new BadRequestHttpException();
            }
        }

        if ($in_modal) {
            ob_start();
            Html::popHeader(
                $dropdown->getTypeName(Session::getPluralNumber()),
                '',
                true,
                ...$dropdown->getSectorizedDetails()
            );
            $dropdown->showForm($id);
            Html::popFooter();
            $content = ob_get_clean();

            return new Response($content);
        }

        ob_start();
        $options = $request->attributes->get('options');
        if ($options !== null) {
            Toolbox::deprecated('Usage of `$options` parameter in DropdownFormController is deprecated.');
        } else {
            $options = [];
        }
        $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';
        $options['id'] = $id;

        $dropdown::displayFullPageForItem($id, $dropdown->getSectorizedDetails(), $options);
        $content = ob_get_clean();

        return new Response($content);
    }
}
