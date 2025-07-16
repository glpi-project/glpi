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

namespace GlpiPlugin\Tester\Form;

use Computer;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationInterface;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Form;
use Override;

final class ComputerDestination implements FormDestinationInterface
{
    #[Override]
    public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config,
    ): array {
        $computer = new Computer();
        $computer->add([
            'name'         => $config['name'] ?? "",
            'entities_id'  => $form->fields['entities_id'],
            'is_recursive' => $form->fields['is_recursive'],
        ]);
        return [$computer];
    }

    #[Override]
    public function postCreateDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        FormDestination $destination,
        array $created_items,
    ): void {
        // No post-creation processing needed for this destination
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        array $config
    ): string {
        $twig = TemplateRenderer::getInstance();
        return $twig->render("@tester/computer_destination.html.twig", [
            'config' => $config,
        ]);
    }

    #[Override]
    public function useDefaultConfigLayout(): bool
    {
        return true;
    }

    #[Override]
    public function getWeight(): int
    {
        return 40;
    }

    #[Override]
    public function getLabel(): string
    {
        return Computer::getTypeName(1);
    }

    #[Override]
    public function getIcon(): string
    {
        return Computer::getIcon();
    }

    #[Override]
    public function exportDynamicConfig(array $config): DynamicExportDataField
    {
        return new DynamicExportDataField($config, []);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array {
        return $config;
    }
}
