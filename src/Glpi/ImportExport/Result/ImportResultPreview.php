<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\ImportExport\Result;

final class ImportResultPreview
{
    /** @var array<int, string> $valid_items */
    private array $valid_items = [];

    /** @var array<int, string> $invalid_items */
    private array $invalid_items = [];

    /** @var array<int, string> $skipped_items */
    private array $skipped_items = [];

    /**
     * Store error messages for a given item ID.
     * Unlike $invalid_items, this represent errors that aren't recoverable.
     *
     *  @var array<int, array{name: string, errors: array<string>}> $fatal_errors
     **/
    private array $fatal_errors = [];

    public function addValidItem(int $id, string $name): void
    {
        $this->valid_items[$id] = $name;
    }

    /** @return array<int, string> */
    public function getValidItems(): array
    {
        return $this->valid_items;
    }

    public function addInvalidItem(int $id, string $name): void
    {
        $this->invalid_items[$id] = $name;
    }

    /** @return array<int, string> */
    public function getInvalidItems(): array
    {
        return $this->invalid_items;
    }

    public function addSkippedItem(int $id, string $name): void
    {
        $this->skipped_items[$id] = $name;
    }

    /** @return array<int, string> */
    public function getSkippedItems(): array
    {
        return $this->skipped_items;
    }

    public function addFatalError(int $id, string $name, string $error): void
    {
        if (!isset($this->fatal_errors[$id])) {
            $this->fatal_errors[$id] = [
                'name' => $name,
                'errors' => [],
            ];
        }

        $this->fatal_errors[$id]['errors'][] = $error;
    }

    public function hasFatalError(int $id): bool
    {
        $errors = $this->fatal_errors[$id]['errors'] ?? [];
        return count($errors) > 0;
    }

    /** @return array<int, array{name: string, errors: array<string>}> */
    public function getItemsWithFatalErrors(): array
    {
        return $this->fatal_errors;
    }

    /** @deprecated 11.0.14 Use {@see addValidItem()} */
    public function addValidForm(int $form_id, string $form_name): void
    {
        $this->addValidItem($form_id, $form_name);
    }

    /**
     * @deprecated 11.0.14 Use {@see getValidItems()}
     * @return array<int, string>
     */
    public function getValidForms(): array
    {
        return $this->getValidItems();
    }

    /** @deprecated 11.0.14 Use {@see addInvalidItem()} */
    public function addInvalidForm(int $form_id, string $form_name): void
    {
        $this->addInvalidItem($form_id, $form_name);
    }

    /**
     * @deprecated 11.0.14 Use {@see getInvalidItems()}
     * @return array<int, string>
     */
    public function getInvalidForms(): array
    {
        return $this->getInvalidItems();
    }

    /** @deprecated 11.0.14 Use {@see addSkippedItem()} */
    public function addSkippedForm(int $form_id, string $form_name): void
    {
        $this->addSkippedItem($form_id, $form_name);
    }

    /**
     * @deprecated 11.0.14 Use {@see getSkippedItems()}
     * @return array<int, string>
     */
    public function getSkippedForms(): array
    {
        return $this->getSkippedItems();
    }

    /** @deprecated 11.0.14 Use {@see addFatalError()} */
    public function addFatalErrorForForm(int $form_id, string $form_name, string $error): void
    {
        $this->addFatalError($form_id, $form_name, $error);
    }

    /** @deprecated 11.0.14 Use {@see hasFatalError()} */
    public function hasFatalErrorForForm(int $form_id): bool
    {
        return $this->hasFatalError($form_id);
    }

    /**
     * @deprecated 11.0.14 Use {@see getItemsWithFatalErrors()}
     * @return array<int, array{name: string, errors: array<string>}>
     */
    public function getFormsWithFatalErrors(): array
    {
        return $this->getItemsWithFatalErrors();
    }
}
