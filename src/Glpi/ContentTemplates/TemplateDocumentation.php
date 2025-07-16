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

namespace Glpi\ContentTemplates;

use Glpi\ContentTemplates\Parameters\ParametersTypes\ParameterTypeInterface;
use Glpi\Toolbox\MarkdownBuilder;

/**
 * Class used to build the twig variable documentation for a given set of
 * parameters
 */
class TemplateDocumentation
{
    /**
     * Documentation summary, keep link to all the sections
     *
     * @var array
     */
    protected $summary;

    /**
     * Sections of the documentations.
     * The first section contains the available parameters, the followings
     * sections contains any extra references needed to work on this parameters
     *
     * @var array
     */
    protected $sections;

    /**
     * Context of the displayed variables
     *
     * @var string
     */
    protected $context;

    public function __construct(string $context)
    {
        $this->context = $context;
        $this->sections = [];
        $this->summary = [];
    }

    /**
     * Build the documentation as markdown
     *
     * @return string
     */
    public function build(): string
    {
        $content = "";

        // Build header
        $header = new MarkdownBuilder();
        $header->addH1(sprintf(__("Available variables (%s)"), $this->context));
        $content .= $header->getMarkdown();

        // Build main content
        foreach ($this->sections as $section) {
            $content .= $section->getMarkdown();
        }

        return $content;
    }

    /**
     * Add a section to the documentation.
     *
     * @param string $title              Section's title
     * @param array $parameters          Parameters to be displayed in this section
     * @param string|null $fields_prefix Prefix to happen to the parameters fields name
     */
    public function addSection(
        string $title,
        array $parameters,
        ?string $fields_prefix = null
    ) {
        // Check if this section is already defined, needed as some parameters
        // might have the same references
        if (isset($this->sections[$title])) {
            return;
        }

        // Keep track of this section in the summary
        $this->summary[] = $title;

        $new_section = new MarkdownBuilder();
        $new_section->addH2($title);

        // Set table header
        $new_section->addTableHeader([
            __("Variable"),
            __("Label"),
            __("Usage"),
            __("References"),
        ]);

        // Keep track of parameters needing aditionnal references
        $references = [];

        // Add a row for each parameters
        foreach ($parameters as $parameter) {
            /** @var ParameterTypeInterface $parameter */
            $row = [
                $parameter->getDocumentationField(),
                $parameter->getDocumentationLabel(),
                MarkdownBuilder::code($parameter->getDocumentationUsage($fields_prefix)),
            ];

            $ref = $parameter->getDocumentationReferences();
            if (!is_null($ref)) {
                $row[] = MarkdownBuilder::navigationLink($ref->getObjectLabel());
                $references[] = $ref;
            }

            $new_section->addTableRow($row);
        }

        $this->sections[$title] = $new_section;

        // Add sections for each references
        foreach ($references as $reference) {
            $this->addSection(
                $reference->getObjectLabel(),
                $reference->getAvailableParameters(),
                $reference->getDefaultNodeName()
            );
        }
    }
}
