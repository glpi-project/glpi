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

namespace Glpi\Form\Export\Result;

use Glpi\Form\Form;

final class ImportResult
{
    /** @var Form[] $imported_forms */
    private array $imported_forms = [];

    /** @var array<string, ImportError> $failed_forms */
    private array $failed_forms = [];

    public function addImportedForm(Form $form): void
    {
        $this->imported_forms[] = $form;
    }

    /** @return Form[] */
    public function getImportedForms(): array
    {
        return $this->imported_forms;
    }

    public function addFailedFormImport(
        string $form_name,
        ImportError $error,
    ): void {
        $this->failed_forms[$form_name] = $error;
    }

    /** @return array<string, ImportError> */
    public function getFailedFormImports(): array
    {
        return $this->failed_forms;
    }
}
