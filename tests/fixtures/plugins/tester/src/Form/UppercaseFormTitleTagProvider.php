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

namespace GlpiPlugin\Tester\Form;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\Tag\Tag;
use Glpi\Form\Tag\TagProviderInterface;
use Glpi\Form\Tag\TagWithIdValueInterface;
use Override;

/**
 * Tag provider registered by the tester plugin.
 *
 * It requires a constructor argument on purpose, to make sure that the tags
 * manager relies on the registered instance instead of instantiating the
 * provider by itself.
 */
final class UppercaseFormTitleTagProvider implements TagProviderInterface, TagWithIdValueInterface
{
    public function __construct(
        private string $color,
    ) {}

    #[Override]
    public function getTagColor(): string
    {
        return $this->color;
    }

    #[Override]
    public function getTags(Form $form): array
    {
        return [$this->getTagForForm($form)];
    }

    #[Override]
    public function getTagContentForValue(
        string $value,
        AnswersSet $answers_set
    ): string {
        $form = Form::getById((int) $value);
        if ($form === false) {
            return '';
        }

        return mb_strtoupper($form->fields['name']);
    }

    #[Override]
    public function getTagFromRawValue(string $value): ?Tag
    {
        $form = Form::getById((int) $value);
        if ($form === false) {
            return null;
        }

        return $this->getTagForForm($form);
    }

    #[Override]
    public function getItemtype(): string
    {
        return Form::class;
    }

    private function getTagForForm(Form $form): Tag
    {
        return new Tag(
            label: 'Tester: uppercase form title',
            value: $form->getId(),
            provider: $this,
        );
    }
}
