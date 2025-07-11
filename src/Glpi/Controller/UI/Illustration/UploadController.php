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

namespace Glpi\Controller\UI\Illustration;

use Document;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\realpath;

final class UploadController extends AbstractController
{
    public function __construct(
        private IllustrationManager $illustration_manager
    ) {}

    #[Route(
        "/UI/Illustration/Upload",
        name: "glpi_ui_illustration_upload",
        methods: "POST",
    )]
    public function __invoke(Request $request): Response
    {
        // Read parameters
        $file_name = $request->request->getString('filename', "");
        $file_path = realpath(GLPI_TMP_DIR . "/$file_name");

        if (
            empty($file_name)
            || !str_starts_with($file_path, realpath(GLPI_TMP_DIR))
            || !file_exists($file_path)
            || !Document::isImage($file_path)
        ) {
            throw new BadRequestHttpException();
        }

        $this->illustration_manager->saveCustomIllustration($file_name, $file_path);
        return new JsonResponse(['file' => $file_name]);
    }
}
