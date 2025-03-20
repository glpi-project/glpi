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
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UploadController extends AbstractController
{
    #[Route(
        "/UI/Illustration/Upload",
        name: "glpi_ui_illustration_upload",
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

        if (!file_exists(GLPI_PICTURE_DIR . "/illustrations")) {
            mkdir(GLPI_PICTURE_DIR . "/illustrations");
        }

        // Move file to custom illustration dir
        $dest_path = GLPI_PICTURE_DIR . "/illustrations/$file_name";
        if (!rename($file_path, $dest_path)) {
            throw new RuntimeException();
        }

        return new JsonResponse(['file' => $file_name]);
    }
}
