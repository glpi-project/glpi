<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Application\View\Extension;

use Glpi\UI\IllustrationManager;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IllustrationExtension extends AbstractExtension
{
    private IllustrationManager $illustration_manager;

    public function __construct()
    {
        $this->illustration_manager = new IllustrationManager();
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_illustration', [$this, 'renderIllustration'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('render_scene', [$this, 'renderScene'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction(
                'searchIcons',
                [$this->illustration_manager, 'searchIcons'],
            ),
            new TwigFunction(
                'countIcons',
                [$this->illustration_manager, 'countIcons'],
            ),
        ];
    }

    public function renderIllustration(string $filename, ?int $size = null): string
    {
        return $this->illustration_manager->renderIcon($filename, $size);
    }

    public function renderScene(
        string $filename,
        ?int $height = null,
        ?int $width = null,
    ): string {
        return $this->illustration_manager->renderScene($filename, $height, $width);
    }
}
