<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\Tag;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;

final class FormTagsManager
{
    public function getTags(Form $form, string $filter = ""): array
    {
        $tags = [];
        foreach ($this->getTagProviders() as $provider) {
            $tags = array_merge($tags, $provider->getTags($form));
        }

        return $filter === '' ? $tags : $this->filterTags($tags, $filter);
    }

    public function insertTagsContent(
        string $content,
        AnswersSet $answers_set
    ): string {
        return preg_replace_callback(
            '/<span.*?data-form-tag="true".*?>.*?<\/span>/',
            function ($match) use ($answers_set) {
                $tag = $match[0];

                // Extract value.
                preg_match('/data-form-tag-value="([^"]+)"/', $tag, $value_match);
                if (empty($value_match)) {
                    return "";
                }

                // Extract provider.
                preg_match('/data-form-tag-provider="([^"]+)"/', $tag, $provider_match);
                if (
                    empty($provider_match)
                    || !is_a(
                        $provider_match[1],
                        TagProviderInterface::class,
                        true
                    )
                ) {
                    return "";
                }

                $provider = new $provider_match[1]();
                return $provider->getTagContentForValue(
                    $value_match[1],
                    $answers_set
                );
            },
            $content
        );
    }

    public function getTagProviders(): array
    {
        return $this->removeInvalidProviders([
            new QuestionTagProvider(),
            new AnswerTagProvider(),
        ]);
    }

    private function removeInvalidProviders(array $providers): array
    {
        return array_filter($providers, function ($provider) {
            if (!($provider instanceof TagProviderInterface)) {
                trigger_error(
                    "Provider must implement TagProviderInterface",
                    E_USER_WARNING
                );
                return false;
            }

            return true;
        });
    }

    private function filterTags(array $tags, string $filter): array
    {
        $filtered_tags = array_filter(
            $tags,
            fn($tag) => $tag instanceof Tag && str_contains(
                $tag->label,
                $filter
            )
        );

        // We must use array_values to ensure there is no gap in the array keys.
        // If there were gaps, the front end would receive an object instead of
        // an array which would lead to errors.
        return array_values($filtered_tags);
    }
}
