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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\VersionParser;

use function Safe\json_decode;
use function Safe\preg_replace;

class GLPINetwork extends CommonGLPI
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry('GLPI Network');
    }

    public static function getIcon()
    {
        return 'ti ti-headset';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === Config::class) {
            self::showForConfig();
        }
        return true;
    }

    public static function showForConfig()
    {
        if (!Config::canView()) {
            return;
        }

        $registration_key = self::getRegistrationKey();

        $canedit = Config::canUpdate();

        $curl_error = null;
        $informations = [];
        if ($registration_key !== "") {
            $informations = self::getRegistrationInformations(true);
        }

        $services_available = self::isServicesAvailable($curl_error);
        TemplateRenderer::getInstance()->display('pages/setup/general/glpinetwork_setup.html.twig', [
            'registration_key' => $registration_key,
            'informations'     => $informations,
            'canedit' => $canedit,
            'services_available' => $services_available,
            'curl_error'       => $curl_error,
        ]);
    }

    /**
     * Get GLPI User Agent in expected format from GLPI Network services.
     *
     * @return string
     */
    public static function getGlpiUserAgent(): string
    {
        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        $comments = sprintf('installation-mode:%s', GLPI_INSTALL_MODE);
        if (!empty(GLPI_USER_AGENT_EXTRA_COMMENTS)) {
            // append extra comments (remove '(' and ')' chars to not break UA string)
            $comments .= '; ' . (string) preg_replace('/\(\)/', ' ', GLPI_USER_AGENT_EXTRA_COMMENTS);
        }
        return sprintf('GLPI/%s (%s)', $version, $comments);
    }

    /**
     * Get GLPI Network UID to pass in requests to GLPI Network Services.
     *
     * @return string
     */
    public static function getGlpiNetworkUid(): string
    {
        return Config::getUuid('glpi_network');
    }

    /**
     * Get GLPI Network registration key.
     *
     * A registration key is a base64 encoded JSON string with a key 'signature' containing the binary
     * signature of the whole.
     *
     * @return string
     */
    public static function getRegistrationKey(): string
    {
        global $CFG_GLPI;
        return (new GLPIKey())->decrypt($CFG_GLPI['glpinetwork_registration_key'] ?? '');
    }

    /**
     * Get GLPI Network registration information.
     *
     * @param bool $force_refresh
     *
     * @return array  Registration data:
     *    - is_valid (boolean):          indicates if key is valid;
     *    - validation_message (string): message related to validation state;
     *    - owner (array):               owner attributes;
     *    - subscription (array):        subscription attributes.
     */
    public static function getRegistrationInformations(bool $force_refresh = false)
    {
        global $GLPI_CACHE;

        $registration_key = self::getRegistrationKey();
        $lang = preg_replace('/^([a-z]+)_.+$/', '$1', $_SESSION["glpilanguage"]);

        $cache_key = sprintf('registration_%s_%s_informations', sha1($registration_key), $lang);
        if (!$force_refresh && ($informations = $GLPI_CACHE->get($cache_key)) !== null) {
            return $informations;
        }

        $informations = [
            'is_valid'           => false,
            'validation_message' => null,
            'owner'              => null,
            'subscription'       => null,
        ];

        if ($registration_key === '') {
            return $informations;
        }

        // Verify registration from registration API
        $error_message = null;
        $registration_response = Toolbox::callCurl(
            rtrim(GLPI_NETWORK_REGISTRATION_API_URL, '/') . '/info',
            [
                CURLOPT_HTTPHEADER => [
                    'Accept:application/json',
                    'Accept-Language: ' . $lang,
                    'Content-Type:application/json',
                    'User-Agent:' . self::getGlpiUserAgent(),
                    'X-Registration-Key:' . $registration_key,
                    'X-Glpi-Network-Uid:' . self::getGlpiNetworkUid(),
                ],
            ],
            $error_message
        );

        $valid_json = false;
        $registration_data = null;
        if ($error_message === null) {
            if (Toolbox::isJSON($registration_response)) {
                $valid_json = true;
                $registration_data = json_decode($registration_response, true);
            }
        }

        if (
            $error_message !== null || !$valid_json
            || !is_array($registration_data) || !array_key_exists('is_valid', $registration_data)
        ) {
            $informations['validation_message'] = __('Unable to fetch registration information.');
            trigger_error(
                sprintf(
                    "Unable to fetch registration information.\nError message:%s\nResponse:\n%s",
                    $error_message,
                    $registration_response
                ),
                E_USER_WARNING
            );
            return $informations;
        }

        $informations['is_valid']           = $registration_data['is_valid'];
        if (array_key_exists('validation_message', $registration_data)) {
            $informations['validation_message'] = $registration_data['validation_message'];
        } elseif (!$registration_data['is_valid']) {
            $informations['validation_message'] = __('The registration key is invalid.');
        } elseif (!$registration_data['subscription']['is_running']) {
            $informations['validation_message'] = __('The registration key refers to a terminated subscription.');
        } else {
            $informations['validation_message'] = __('The registration key is valid.');
        }
        $informations['owner']              = $registration_data['owner'];
        $informations['subscription']       = $registration_data['subscription'];

        $GLPI_CACHE->set($cache_key, $informations, new DateInterval('P1D')); // Cache for one day

        return $informations;
    }

    /**
     * Check if GLPI Network registration is existing and valid.
     *
     * @return boolean
     */
    public static function isRegistered(): bool
    {
        return self::getRegistrationInformations()['is_valid'];
    }

    public static function showInstallMessage(): string
    {
        $url = htmlescape(GLPI_NETWORK_SERVICES);

        return nl2br(
            sprintf(
                __s(
                    "You need help to integrate GLPI in your IT, have a bug fixed or benefit from pre-configured rules or dictionaries?\n\n"
                    . "We provide the %s space for you.\n"
                    . "GLPI-Network is a commercial service that includes a subscription for tier 3 support, ensuring the correction of bugs encountered with a commitment time.\n\n"
                    . "In this same space, you will be able to contact an official partner to help you with your GLPI integration."
                ),
                "<a href='" . $url . "' target='_blank'>" . $url . "</a>"
            )
        );
    }

    public static function getSupportPromoteMessage(): string
    {
        $url = htmlescape(GLPI_NETWORK_SERVICES);

        return nl2br(sprintf(
            __s("Having troubles setting up an advanced GLPI module?\n"
            . "We can help you solve them. Sign up for support on %s."),
            "<a href='" . $url . "' target='_blank'>" . $url . "</a>"
        ));
    }

    public static function addErrorMessageAfterRedirect(): void
    {
        Session::addMessageAfterRedirect(self::getSupportPromoteMessage(), false, ERROR);
    }

    /**
     * Executes a curl call
     *
     * @param string $curl_error  will contains original curl error string if an error occurs
     *
     * @return boolean
     */
    public static function isServicesAvailable(&$curl_error = null): bool
    {
        $error_msg = null;
        $content = Toolbox::callCurl(GLPI_NETWORK_REGISTRATION_API_URL, [], $error_msg, $curl_error);
        return $content !== '';
    }

    public static function getOffers(bool $force_refresh = false): array
    {
        global $GLPI_CACHE;

        $lang = preg_replace('/^([a-z]+)_.+$/', '$1', $_SESSION["glpilanguage"]);
        $cache_key = 'glpi_network_offers_' . $lang;

        if (!$force_refresh && ($offers = $GLPI_CACHE->get($cache_key)) !== null) {
            return $offers;
        }

        $error_message = null;
        $response = Toolbox::callCurl(
            rtrim(GLPI_NETWORK_REGISTRATION_API_URL, '/') . '/offers',
            [
                CURLOPT_HTTPHEADER => [
                    'Accept:application/json',
                    'Accept-Language: ' . $lang,
                ],
            ],
            $error_message
        );

        $valid_json = false;
        $offers = null;
        if ($error_message === null) {
            if (Toolbox::isJSON($response)) {
                $valid_json = true;
                $offers = json_decode($response);
            }
        }

        if ($error_message !== null || !$valid_json || !is_array($offers)) {
            trigger_error(
                sprintf(
                    "Unable to fetch offers information.\nError message:%s\nResponse:\n%s",
                    $error_message,
                    $response
                ),
                E_USER_WARNING
            );
            return [];
        }

        $GLPI_CACHE->set($cache_key, $offers, HOUR_TIMESTAMP);

        return $offers;
    }
}
