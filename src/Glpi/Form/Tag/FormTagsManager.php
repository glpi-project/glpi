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

namespace Glpi\Form\Tag;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Toolbox\MapperInterface;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_replace_callback;

final class FormTagsManager
{
    /**
     * Singleton instance.
     */
    private static ?FormTagsManager $instance = null;

    /**
     * Providers registered by plugins, indexed by their class name.
     *
     * This property is static to make sure that registered providers are also
     * available to the instances that are not retrieved using `getInstance()`.
     *
     * @var array<class-string<TagProviderInterface>, TagProviderInterface>
     */
    private static array $plugins_providers = [];

    /**
     * Singleton access method.
     *
     * @return FormTagsManager
     */
    public static function getInstance(): FormTagsManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a tag provider from a plugin.
     * Its tags will be available in the form tags autocompletion and their
     * content will be computed using the given provider instance.
     *
     * @param TagProviderInterface $provider
     * @return void
     */
    public function registerPluginTagProvider(
        TagProviderInterface $provider
    ): void {
        self::$plugins_providers[$provider::class] = $provider;
    }

    public function getTags(Form $form, string $filter = ""): array
    {
        $tags = [];
        foreach ($this->getTagProviders() as $provider) {
            $tags = array_merge($tags, $provider->getTags($form));
        }

        return $filter === '' ? $tags : $this->filterTags($tags, $filter);
    }

    public function refreshTagsContent(string $content): string
    {
        $providers = $this->getTagProvidersByClass();

        return preg_replace_callback(
            '/<span(?=[^>]*\bdata-form-tag="true")[^>]*>.*?<\/span>/',
            function ($match) use ($providers) {
                $tag = $match[0];

                // Extract value.
                preg_match('/data-form-tag-value="([^"]+)"/', $tag, $value_match);
                if ($value_match === []) {
                    return "";
                }

                // Extract provider.
                preg_match('/data-form-tag-provider="([^"]+)"/', $tag, $provider_match);
                $provider = $providers[$provider_match[1] ?? ''] ?? null;
                if ($provider === null) {
                    return "";
                }

                $tag = $provider->getTagFromRawValue($value_match[1]);
                if ($tag === null) {
                    return "";
                }
                return $tag->html;
            },
            $content
        );
    }

    public function insertTagsContent(
        string $content,
        AnswersSet $answers_set
    ): string {
        $providers = $this->getTagProvidersByClass();

        return preg_replace_callback(
            '/<span(?=[^>]*\bdata-form-tag="true")[^>]*>.*?<\/span>/',
            function ($match) use ($answers_set, $providers) {
                $tag = $match[0];

                // Extract value.
                preg_match('/data-form-tag-value="([^"]+)"/', $tag, $value_match);
                if ($value_match === []) {
                    return "";
                }

                // Extract provider.
                preg_match('/data-form-tag-provider="([^"]+)"/', $tag, $provider_match);
                $provider = $providers[$provider_match[1] ?? ''] ?? null;
                if ($provider === null) {
                    return "";
                }

                return $provider->getTagContentForValue(
                    $value_match[1],
                    $answers_set
                );
            },
            $content
        );
    }

    /**
     * Get all available tag providers (core providers and providers
     * registered by plugins).
     *
     * @return TagProviderInterface[]
     */
    public function getTagProviders(): array
    {
        return $this->removeInvalidProviders([
            new FormTagProvider(),
            new SectionTagProvider(),
            new QuestionTagProvider(),
            new AnswerTagProvider(),
            new CommentTitleTagProvider(),
            new CommentDescriptionTagProvider(),
            new FullFormTagProvider(),
            ...array_values(self::$plugins_providers),
        ]);
    }

    public function replaceIdsInTags(
        string $subject,
        MapperInterface $mapper
    ): string {
        foreach ($this->getTagProviders() as $tag_provider) {
            if (!$tag_provider instanceof TagWithIdValueInterface) {
                // This tag value do not reference and ID, we can skip it
                continue;
            }
            $provider_class = preg_quote($tag_provider::class);
            $mapped_class = $tag_provider->getItemtype();

            $subject = preg_replace_callback(
                // Look for the value + provider properties
                "/data-form-tag-value=\"(\d+)\" "
                . "data-form-tag-provider=\"$provider_class\"/",
                fn($match) => preg_replace(
                    // We do another preg on a small subset to avoid having to
                    // manually rewrite the whole string
                    "/value=\"(\d+)/",
                    "value=\"" . $mapper->getItemId($mapped_class, $match[1]),
                    $match[0]
                ),
                $subject,
            );
        }

        return $subject;
    }

    /**
     * @return array<class-string<TagProviderInterface>, TagProviderInterface>
     */
    private function getTagProvidersByClass(): array
    {
        $providers = [];
        foreach ($this->getTagProviders() as $provider) {
            $providers[$provider::class] = $provider;
        }

        return $providers;
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
                strtoupper($tag->label),
                strtoupper($filter)
            )
        );

        // We must use array_values to ensure there is no gap in the array keys.
        // If there were gaps, the front end would receive an object instead of
        // an array which would lead to errors.
        return array_values($filtered_tags);
    }
}
