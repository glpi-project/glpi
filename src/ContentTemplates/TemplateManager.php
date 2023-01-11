<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use CommonITILObject;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

/**
 * Handle user defined twig templates :
 *  - followup templates
 *  - tasks templates
 *  - solutions templates.
 *
 * @since 10.0.0
 */
class TemplateManager
{
    /**
     * Boiler plate code to render a template
     *
     * @param string $content  Template content (html + twig)
     * @param array $params    Variables to be exposed to the templating engine
     *
     * @return string The rendered HTML
     */
    public static function render(
        string $content,
        array $params
    ): string {
        $content = Sanitizer::getVerbatimValue($content);

       // Init twig
        $loader = new ArrayLoader(['template' => $content]);
        $twig = new Environment($loader);

       // Use sandbox extension to restrict code execution
        $twig->addExtension(new SandboxExtension(self::getSecurityPolicy(), true));

       // Render the template
        $html = $twig->render('template', $params);

       // Clean generated HTML to ensure both template and values are cleaned.
        $html = RichText::getSafeHtml($html);

        return $html;
    }

    /**
     * Boiler plate for rendering a commonitilobject content from a template
     *
     * @param CommonITILObject $itil_item
     * @param string $template
     *
     * @return string|null
     */
    public static function renderContentForCommonITIL(
        CommonITILObject $itil_item,
        string $template
    ): ?string {
        $parameters_class = $itil_item->getContentTemplatesParametersClass();
        $parameters = new $parameters_class();

        try {
            $html = TemplateManager::render(
                $template,
                [
                    'itemtype' => $itil_item->getType(),
                    $parameters->getDefaultNodeName() => $parameters->getValues($itil_item),
                ]
            );
        } catch (\Twig\Error\Error $e) {
            global $GLPI;
            $GLPI->getErrorHandler()->handleException($e);
            return null;
        }
        return $html;
    }
    /**
     * Boiler plate code to validate a template that user is trying to submit
     *
     * @param string $content        Template content (html + twig)
     * @param null|string $err_msg   Reference to variable that will be filled by error message if validation fails
     *
     * @return bool
     */
    public static function validate(string $content, ?string &$err_msg = null): bool
    {
        $content = Sanitizer::getVerbatimValue($content);

        $twig = new Environment(new ArrayLoader(['template' => $content]));
        $twig->addExtension(new SandboxExtension(self::getSecurityPolicy(), true));

        try {
           // Test if template is valid
            $twig->parse($twig->tokenize(new Source($content, 'template')));

           // Security policies are not valided with the previous step so we
           // need to actually try to render the template to validate them
            $twig->render('template', []);
        } catch (\Twig\Sandbox\SecurityError $e) {
           // Security policy error: the template use a forbidden tag/function/...
            $err_msg = sprintf(__("Invalid twig template (%s)"), $e->getMessage());

            return false;
        } catch (\Twig\Error\SyntaxError $e) {
           // Syntax error, note that we do not show the exception message in the
           // error sent to the users as it not really helpful and is more likely
           // to confuse them that to help them fix the issue
            $err_msg = __("Invalid twig template syntax");

            return false;
        }

        return true;
    }

    /**
     * Define our security policies for the sandbox extension
     *
     * @return SecurityPolicy
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        $tags = ['if', 'for'];
        $filters = ['escape', 'upper', 'date', 'length', 'round', 'lower', 'trim', 'raw'];
        $methods = [];
        $properties = [];
        $functions = ['date', 'max', 'min','random', 'range'];
        return new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
    }

    /**
     * Generate the documentation of the given parameters
     *
     * @param string $preset_parameters_key
     *
     * @return string
     */
    public static function generateMarkdownDocumentation(
        string $preset_parameters_key
    ): string {
        $parameters = ParametersPreset::getByKey($preset_parameters_key);
        $context = ParametersPreset::getContextByKey($preset_parameters_key);

        $documentation = new TemplateDocumentation($context);
        $documentation->addSection(__("Root variables"), $parameters);
        return $documentation->build();
    }

    /**
     * Compute the given parameters
     *
     * @param array $parameters
     *
     * @return array
     */
    public static function computeParameters(array $parameters)
    {
        return array_map(function ($parameter) {
            return $parameter->compute();
        }, $parameters);
    }
}
