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

namespace Glpi\Altcha;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeOptions;
use DateInterval;
use Glpi\Toolbox\SingletonTrait;
use GLPIKey;
use JsonException;
use RuntimeException;
use Safe\DateTimeImmutable;
use Safe\Exceptions\UrlException;

use function Safe\base64_decode;

/**
 * To protect an endpoint with an altcha, the client code must load the altcha.js
 * script and render the "components/altcha/widget.html.twig" template.
 *
 * Behind the scene, this will render the official altcha widget which will:
 * - Request a challenge by calling the `/Altcha/Challenge` endpoint.
 * - Solve the challenge locally using a lot of hash computations, this is
 *   called a "proof of work".
 * - Include the proof of work in an hidden `altcha` input.
 * - Request and solve a new challenge if the current proof of work expires.
 *
 * This is all done in the background, the altcha is never displayed to the
 * user.
 * The server will keep track of ongoing challenges using the session.
 *
 * Then, when the form is submitted, the controller must validate the hidden
 * input and remove the ongoing challenge.
 *
 * Here is an example:
 * ```php
 * $altcha = $request->request->getString('altcha');
 * $manager = AltchaManager::getInstance();
 * if (!$manager->verifySolution($altcha)) {
 *    throw new BadRequestHttpException();
 * }
 * $some_service->doSomething();
 * $manager->removeChallenge($altcha);
 * ```
 *
 * See: https://altcha.org/docs/v2/server-verification/#2-custom-server-verification.
 */
final class AltchaManager
{
    use SingletonTrait;

    /** @var AltchaMode */
    public const DEFAULT_MODE = AltchaMode::INTERACTIVE;

    /** @var int */
    public const DEFAULT_COMPLEXITY = 50000;

    /** @var string */
    public const DEFAULT_EXPIRATION_INTERVAL = 'PT20M';

    /** @var string */
    private const SESSION_STORAGE_KEY = 'altcha_challenges';

    private Altcha $altcha;

    private function __construct()
    {
        $this->altcha = new Altcha($this->computeHmacKey());
        $this->initSessionStorage();
    }

    public function isEnabled(): bool
    {
        return $this->getMode()->isEnabled();
    }

    public function shouldStartWidgetOnLoad(): bool
    {
        return $this->getMode()->shouldStartOnLoad();
    }

    public function shouldHideWidget(): bool
    {
        return !$this->getMode()->isVisible();
    }

    public function generateChallenge(): Challenge
    {
        // Create challenge
        $options = new ChallengeOptions(
            maxNumber: $this->getComplexity(),
            expires: (new DateTimeImmutable())->add($this->getExpiresAtInterval()),
        );
        $challenge = $this->altcha->createChallenge($options);
        $this->storeOngoingChallenge($challenge->challenge);

        return $challenge;
    }

    public function verifySolution(string $raw_payload): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        try {
            $payload = $this->decodePayload($raw_payload);
            $challenge = $this->getChallengeHashFromPayload($payload);
        } catch (InvalidPayloadException) {
            return false;
        }

        if (!$this->hasOngoingChallenge($challenge)) {
            return false;
        }

        return $this->altcha->verifySolution($payload, true);
    }

    /**
     * This method must be called once the action tied to a specific challenge
     * has been succesfully executed.
     *
     * See https://altcha.org/docs/v2/security-recommendations#replay-attack.
     *
     * To defend against replay attacks, where a client resubmits a previously
     * valid solution, the server must ensure that each challenge is single-use.
     * We must thus maintain a registry of solved challenges and reject any
     * attempt to reuse a challenge that has already been accepted.
     */
    public function removeChallenge(string $raw_payload): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $payload = $this->decodePayload($raw_payload);
            $challenge = $this->getChallengeHashFromPayload($payload);
        } catch (InvalidPayloadException) {
            throw new RuntimeException("No challenge found");
        }

        $this->removeOngoingChallenge($challenge);
    }

    /**
     * Get the hmac key used to generate and validate challenge.
     * The ideal key size depends on the hash algorithm used, by default
     * the ChallengeOptions object specify the SHA-256 algorithm.
     *
     * SHA-256 has a 512 bits block size and a 256 bits output length.
     * In general, we need a key smaller or equals to the block size and higher
     * or equals to the output size (so <= 512 and >= 256 in our case).
     *
     * The existing glpicrypt.key will work fine for this as it is has a length
     * of 256 bits at the time this comment was written.
     */
    private function computeHmacKey(): string
    {
        return (new GLPIKey())->get();
    }

    private function initSessionStorage(): void
    {
        if (!isset($_SESSION[self::SESSION_STORAGE_KEY])) {
            $_SESSION[self::SESSION_STORAGE_KEY] = [];
        }
    }

    private function storeOngoingChallenge(string $hash): void
    {
        $_SESSION[self::SESSION_STORAGE_KEY][$hash] = true;
    }

    private function hasOngoingChallenge(string $hash): bool
    {
        return $_SESSION[self::SESSION_STORAGE_KEY][$hash] ?? false;
    }

    private function removeOngoingChallenge(string $hash): void
    {
        unset($_SESSION[self::SESSION_STORAGE_KEY][$hash]);
    }

    /** @throws InvalidPayloadException */
    private function decodePayload(string $payload): array
    {
        try {
            $decoded = base64_decode($payload, true);
            $data = json_decode($decoded, true, 2, JSON_THROW_ON_ERROR);
        } catch (UrlException|JsonException) {
            throw new InvalidPayloadException();
        }

        if (!is_array($data) || $data === []) {
            throw new InvalidPayloadException();
        }

        return $data;
    }

    /** @throws InvalidPayloadException */
    private function getChallengeHashFromPayload(array $payload): string
    {
        if (!isset($payload['challenge'])) {
            throw new InvalidPayloadException();
        }

        return $payload['challenge'];
    }

    private function getMode(): AltchaMode
    {
        // Should never happens as it is defined by SystemConfigurator.php
        // but it it still another safety to avoid failure.
        if (!defined('GLPI_ALTCHA_MODE')) {
            // @phpstan-ignore theCodingMachineSafe.function (we checked just above if it is defined)
            define('GLPI_ALTCHA_MODE', self::DEFAULT_MODE);
        }

        $mode = GLPI_ALTCHA_MODE;
        if ($mode instanceof AltchaMode) {
            return $mode;
        } elseif (is_string($mode) && AltchaMode::tryFrom($mode) !== null) {
            return AltchaMode::from($mode);
        }

        throw new RuntimeException();
    }

    /**
     * Define the complexity of the proof-of-work task.
     *
     * See: https://altcha.org/docs/v2/complexity/.
     *
     * Higher complexity may significantly increase the computational load on
     * client devices, potentially impacting user experience.
     *
     * Lower complexity might reduce security against automated attacks but can
     * enhance user accessibility.
     *
     * To increase security, we could implements in the future a "dynamic
     * complexity" mecanism.
     * This mecanism would adapts complexity based on server load and/or user
     * behavior, ensuring a balance between security and usability.
     */
    private function getComplexity(): int
    {
        // Should never happens as it is defined by SystemConfigurator.php
        // but it it still another safety to avoid failure.
        if (!defined('GLPI_ALTCHA_MAX_NUMBER')) {
            // @phpstan-ignore theCodingMachineSafe.function (we checked just above if it is defined)
            define('GLPI_ALTCHA_MAX_NUMBER', self::DEFAULT_COMPLEXITY);
        }

        $complexity = GLPI_ALTCHA_MAX_NUMBER;
        if (!is_int($complexity)) {
            throw new RuntimeException();
        }

        return $complexity;
    }

    /**
     * Define the expiration time for the challenge.
     *
     * See: https://altcha.org/docs/v2/security-recommendations.
     *
     * It is recommended to use a short challenge expiration to ensure that
     * challenges are invalidated after they expire. As a general guideline,
     * set the expiration time between 20 minutes and 1 hour.
     */
    private function getExpiresAtInterval(): DateInterval
    {
        // Should never happens as it is defined by SystemConfigurator.php
        // but it it still another safety to avoid failure.
        if (!defined('GLPI_ALTCHA_EXPIRATION_INTERVAL')) {
            // @phpstan-ignore theCodingMachineSafe.function (we checked just above if it is defined)
            define('GLPI_ALTCHA_EXPIRATION_INTERVAL', self::DEFAULT_EXPIRATION_INTERVAL);
        }

        $interval = GLPI_ALTCHA_EXPIRATION_INTERVAL;
        if ($interval instanceof DateInterval) {
            return $interval;
        } elseif (is_string($interval)) {
            return new DateInterval($interval);
        }

        throw new RuntimeException();
    }
}
