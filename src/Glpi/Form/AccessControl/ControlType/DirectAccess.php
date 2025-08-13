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

namespace Glpi\Form\AccessControl\ControlType;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Form;
use InvalidArgumentException;
use Override;

final class DirectAccess implements ControlTypeInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Allow direct access");
    }

    #[Override]
    public function getIcon(): string
    {
        return "ti ti-link";
    }

    #[Override]
    public function getConfig(): JsonFieldInterface
    {
        return new DirectAccessConfig();
    }

    #[Override]
    public function getWarnings(Form $form): array
    {
        return $this->getWarningIfFormHasBlacklistedQuestionTypes($form);
    }

    private function getWarningIfFormHasBlacklistedQuestionTypes(
        Form $form,
    ): array {
        $warnings = [];

        if (
            FormAccessControlManager::getInstance()->allowUnauthenticatedAccess($form)
            && array_reduce(
                $form->getQuestions(),
                fn($carry, $question) => $carry || !$question->getQuestionType()->isAllowedForUnauthenticatedAccess()
            )
        ) {
            $warnings[] = __('This form contains question types that are not allowed for unauthenticated access. These questions will be hidden from unauthenticated users.');
        }

        return $warnings;
    }

    #[Override]
    public function renderConfigForm(FormAccessControl $access_control): string
    {
        global $CFG_GLPI;

        $config = $access_control->getConfig();
        if (!$config instanceof DirectAccessConfig) {
            throw new InvalidArgumentException("Invalid config class");
        }

        // Build form URL with integrated token parameter
        $url = $CFG_GLPI['url_base'];
        $url .= "/Form/Render/{$_GET['id']}?";
        $url .= http_build_query([
            'token' => $config->getToken(),
        ]);

        $twig = TemplateRenderer::getInstance();
        return $twig->render("pages/admin/form/access_control/direct_access.html.twig", [
            'access_control' => $access_control,
            'config' => $config,
            'url'    => $url,
        ]);
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function createConfigFromUserInput(array $input): DirectAccessConfig
    {
        return DirectAccessConfig::jsonDeserialize([
            'token'                 => $input['_token'] ?? null,
            'allow_unauthenticated' => $input['_allow_unauthenticated'] ?? false,
        ]);
    }

    #[Override]
    public function canAnswer(
        Form $form,
        JsonFieldInterface $config,
        FormAccessParameters $parameters
    ): AccessVote {
        if (!$config instanceof DirectAccessConfig) {
            throw new InvalidArgumentException("Invalid config class");
        }

        if (!$this->validateSession($config, $parameters)) {
            return AccessVote::Abstain;
        }

        if (!$this->validateToken($form, $config, $parameters)) {
            return AccessVote::Abstain;
        };

        return AccessVote::Grant;
    }

    private function validateSession(
        DirectAccessConfig $config,
        FormAccessParameters $parameters,
    ): bool {
        if (!$config->allowUnauthenticated() && !$parameters->isAuthenticated()) {
            return false;
        }

        return true;
    }

    private function validateToken(
        Form $form,
        DirectAccessConfig $config,
        FormAccessParameters $parameters,
    ): bool {
        // Note: it is easy to validate the token when an user is accesing the
        // form for the first time through the /Form/Render/{id} page as the
        // link will contain the token as a GET parameter.
        // However, for any subsequent AJAX requests, the token is not present
        // in the URL. Therefore, we must rely on the session to store the token
        // and validate it on each request.
        $token = $parameters->getUrlParameters()['token'] ?? null;
        if ($token === null) {
            $session_token = $_SESSION['helpdesk_form_access_control'][$form->getId()] ?? null;
            if ($session_token === null) {
                return false;
            } else {
                $token = $session_token;
            }
        } else {
            $_SESSION['helpdesk_form_access_control'][$form->getId()] = $token;
        }

        return hash_equals($config->getToken(), $token);
    }

    public function allowUnauthenticated(JsonFieldInterface $config): bool
    {
        if (!$config instanceof DirectAccessConfig) {
            throw new InvalidArgumentException("Invalid config class");
        }

        return $config->allowUnauthenticated();
    }

    #[Override]
    public function exportDynamicConfig(
        JsonFieldInterface $config
    ): DynamicExportDataField {
        return new DynamicExportDataField($config->jsonSerialize(), []);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array {
        return $config;
    }
}
