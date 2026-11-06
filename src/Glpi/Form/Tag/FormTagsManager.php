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

use CommonDBTM;
use Glpi\Features\CloneMapper;
use Glpi\Form\AnswersSet;
use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Toolbox\MapperInterface;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_replace_callback;

final class FormTagsManager
{
    /**
     * Attribute added to the exported tags whose value is the name of a data
     * requirement instead of an id.
     */
    public const REQUIREMENT_ATTRIBUTE = 'data-form-tag-requirement';

    private const TAG_PATTERN = '/<span(?=[^>]*\bdata-form-tag="true")[^>]*>.*?<\/span>/';

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
     * If the provider implements `TagWithIdValueInterface`, the IDs referenced
     * by its tags are handled like the ones of the core providers when a form
     * is exported, imported or cloned.
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
            self::TAG_PATTERN,
            function ($match) use ($providers) {
                $tag = $match[0];

                $value = $this->getTagValue($tag);
                $provider = $this->getTagProvider($tag, $providers);
                if ($value === null || $provider === null) {
                    return "";
                }

                $tag = $provider->getTagFromRawValue($value);
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
            self::TAG_PATTERN,
            function ($match) use ($answers_set, $providers) {
                $tag = $match[0];

                $value = $this->getTagValue($tag);
                $provider = $this->getTagProvider($tag, $providers);
                if ($value === null || $provider === null) {
                    return "";
                }

                return $provider->getTagContentForValue(
                    $value,
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

    /**
     * Replace the ids referenced by the tags of the given content, using the
     * given mapper (form import or clone).
     *
     * Tags exported as data requirements (see `prepareTagsForExport()`) are
     * resolved using the name of their requirement.
     * Ids that are unknown to the mapper are kept as is.
     */
    public function replaceIdsInTags(
        string $subject,
        MapperInterface $mapper
    ): string {
        $providers = $this->getTagProvidersByClass();

        return preg_replace_callback(
            self::TAG_PATTERN,
            function ($match) use ($providers, $mapper) {
                $tag = $match[0];

                $value = $this->getTagValue($tag);
                $provider = $this->getTagProvider($tag, $providers);
                if ($value === null || !$provider instanceof TagWithIdValueInterface) {
                    // This tag value do not reference and ID, we can skip it
                    return $tag;
                }
                $itemtype = $provider->getItemtype();

                // Tags exported as data requirements are mapped by name
                $is_requirement = $this->isRequirementTag($tag);
                $key = $is_requirement ? htmlspecialchars_decode($value) : $value;
                if (
                    (!$is_requirement && !ctype_digit($key))
                    || !$this->hasMappedId($mapper, $itemtype, $key)
                ) {
                    return $tag;
                }

                $tag = $this->setTagValue(
                    $tag,
                    (string) $mapper->getItemId($itemtype, $key)
                );
                return preg_replace(
                    '/\s' . self::REQUIREMENT_ATTRIBUTE . '="true"/',
                    '',
                    $tag
                );
            },
            $subject,
        );
    }

    /**
     * Prepare the tags of the given content for an export.
     *
     * Tags referencing an item (see `TagWithIdValueInterface`) that does not
     * belong to one of the exported forms are converted into data
     * requirements: their value is replaced by the name of the requirement,
     * which will be used to find the item when the form is imported.
     *
     * @param int[] $exported_forms_ids
     */
    public function prepareTagsForExport(
        string $content,
        array $exported_forms_ids
    ): DynamicExportDataField {
        return $this->convertTagsToRequirements(
            $content,
            function (string $itemtype, int $id) use ($exported_forms_ids) {
                $item = getItemForItemtype($itemtype);
                if (
                    !$item instanceof CommonDBTM
                    || !$item->getFromDB($id)
                    || $this->isItemOfForms($item, $exported_forms_ids)
                ) {
                    return null;
                }

                return DataRequirementSpecification::fromItem($item);
            }
        );
    }

    /**
     * Convert the tags referencing an item (see `TagWithIdValueInterface`) into
     * data requirements: their value is replaced by the name of the requirement
     * returned by the given callable, which will be used to find the item when
     * the form is imported.
     * Tags for which the callable returns `null` are left as is.
     *
     * @param callable(class-string<CommonDBTM>, int): ?DataRequirementSpecification $get_requirement
     */
    public function convertTagsToRequirements(
        string $content,
        callable $get_requirement
    ): DynamicExportDataField {
        $providers = $this->getTagProvidersByClass();
        $requirements = [];

        $content = preg_replace_callback(
            self::TAG_PATTERN,
            function ($match) use ($providers, $get_requirement, &$requirements) {
                $tag = $match[0];

                $value = $this->getTagValue($tag);
                $provider = $this->getTagProvider($tag, $providers);
                if (
                    $value === null
                    || !ctype_digit($value)
                    || !$provider instanceof TagWithIdValueInterface
                    || $this->isRequirementTag($tag)
                ) {
                    return $tag;
                }

                $requirement = $get_requirement($provider->getItemtype(), (int) $value);
                if ($requirement === null) {
                    return $tag;
                }
                $requirements[] = $requirement;

                $tag = $this->setTagValue($tag, htmlescape($requirement->name));
                return preg_replace(
                    '/\bdata-form-tag="true"/',
                    'data-form-tag="true" ' . self::REQUIREMENT_ATTRIBUTE . '="true"',
                    $tag,
                    1
                );
            },
            $content
        );

        return new DynamicExportDataField($content, $requirements);
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

    private function getTagValue(string $tag): ?string
    {
        preg_match('/data-form-tag-value="([^"]+)"/', $tag, $value_match);
        return $value_match[1] ?? null;
    }

    /**
     * @param array<class-string<TagProviderInterface>, TagProviderInterface> $providers
     */
    private function getTagProvider(
        string $tag,
        array $providers
    ): ?TagProviderInterface {
        preg_match('/data-form-tag-provider="([^"]+)"/', $tag, $provider_match);
        return $providers[$provider_match[1] ?? ''] ?? null;
    }

    private function setTagValue(string $tag, string $value): string
    {
        return preg_replace_callback(
            '/data-form-tag-value="[^"]+"/',
            fn() => sprintf('data-form-tag-value="%s"', $value),
            $tag,
            1
        );
    }

    private function isRequirementTag(string $tag): bool
    {
        return str_contains($tag, self::REQUIREMENT_ATTRIBUTE . '="true"');
    }

    /**
     * @param int[] $forms_ids
     */
    private function isItemOfForms(CommonDBTM $item, array $forms_ids): bool
    {
        if ($item instanceof Form) {
            return in_array($item->getID(), $forms_ids);
        }

        if (
            $item instanceof Question
            || $item instanceof Section
            || $item instanceof Comment
        ) {
            return in_array($item->getForm()->getID(), $forms_ids);
        }

        return false;
    }

    private function hasMappedId(
        MapperInterface $mapper,
        string $itemtype,
        string|int $key
    ): bool {
        if ($mapper instanceof CloneMapper || $mapper instanceof DatabaseMapper) {
            return $mapper->hasItemId($itemtype, $key);
        }

        // Unknown mapper implementation: let it handle the missing values.
        return true;
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
