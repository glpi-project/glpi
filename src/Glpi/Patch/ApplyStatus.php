<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Patch;

enum ApplyStatus
{
    /** Changes were applied successfully. */
    case Applied;

    /** All changes were already present in the file — nothing to do. */
    case AlreadyApplied;

    /** File was created (new file in the patch). */
    case Created;

    /** File was deleted (removed file in the patch). */
    case Deleted;

    /** File was skipped intentionally (test file, .vue source, etc.). */
    case Skipped;

    /** Changes were successfully reverted (inverse of Applied). */
    case Reverted;

    /**
     * The patch context does not match the file content —
     * the patch is outdated, the file was already modified differently,
     * or this is not the right target.
     */
    case Conflict;

    /** Dry-run mode: the change would have been applied but nothing was written. */
    case DryRun;
}
