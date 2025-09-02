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

namespace Glpi\Altcha;

use LogicException;

enum AltchaMode: string
{
    case DISABLED = "disabled";
    case HIDDEN = "hidden";
    case AUTO = "auto";
    case INTERACTIVE = "interactive";

    public function isEnabled(): bool
    {
        return match ($this) {
            self::DISABLED    => false,
            self::HIDDEN      => true,
            self::AUTO        => true,
            self::INTERACTIVE => true,
        };
    }

    public function isVisible(): bool
    {
        return match ($this) {
            self::DISABLED    => throw new LogicException(),
            self::HIDDEN      => false,
            self::AUTO        => true,
            self::INTERACTIVE => true,
        };
    }

    public function shouldStartOnLoad(): bool
    {
        return match ($this) {
            self::DISABLED    => throw new LogicException(),
            self::HIDDEN      => true,
            self::AUTO        => true,
            self::INTERACTIVE => false,
        };
    }
}
