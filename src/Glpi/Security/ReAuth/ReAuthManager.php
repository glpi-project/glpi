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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

use Glpi\Application\Environment;
use Glpi\Exception\Http\ReAuthRequiredHttpException;
use Glpi\Exception\RedirectException;
use Glpi\Kernel\Kernel;
use Glpi\Kernel\Listener\ExceptionListener\AccessErrorListener;
use Glpi\Toolbox\SingletonTrait;
use Glpi\Toolbox\URL;
use InvalidArgumentException;
use RuntimeException;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\Request;

use function Safe\parse_url;

final class ReAuthManager
{
    use SingletonTrait;
    public const int REAUTH_DELAY_SECONDS = 15 * MINUTE_TIMESTAMP;

    private ?ReAuthStrategyInterface $strategy = null;

    /**
     * @var ReAuthStrategyInterface[]
     */
    private array $additional_strategies = [];

    /**
     * @throws RedirectException|ReAuthRequiredHttpException
     */
    public function checkReAuthenticationOrRedirect(): void
    {
        if ($this->isReAuthenticated()) {
            return;
        }

        $this->redirectToReauth();
    }

    /**
     * Redirect to reauth prompt and save current request data (url + post data)
     *
     * Requests that cannot display the prompt (AJAX, or a client expecting anything else than
     * HTML) get a ReAuthRequiredHttpException instead of the redirection.
     *
     * @throws RedirectException|ReAuthRequiredHttpException
     */
    public function redirectToReauth(): never
    {
        global $CFG_GLPI;

        if (!$this->canDisplayPrompt()) {
            $this->setCallerPageAsTarget();

            throw new ReAuthRequiredHttpException();
        }

        $this->setRequestedTarget();

        $referer = \Html::getRefererUrl();
        // Set Origin URL except if it's a path to the reauth controller, to prevent looping.
        $this->setOriginURL(
            ($referer !== null && !$this->isReAuthRoute($referer)) ? $referer : $CFG_GLPI["root_doc"]
        );

        throw new RedirectException($CFG_GLPI['root_doc'] . '/ReAuth/Prompt');
    }

    /**
     * User has a valid reauth session
     */
    public function isReAuthenticated(): bool
    {
        // bypass reauth - this constant will be removed in a next glpi 12.x release
        // do not rely on it
        if (GLPI_DISABLE_REAUTH === true) {
            return true;
        }

        $current_limit_timestamp = $_SESSION['glpi_reauth_until'] ?? null;
        $calculated_limit_timestamp = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp();

        return $current_limit_timestamp !== null && $current_limit_timestamp > $calculated_limit_timestamp;
    }

    /**
     * Delegate the prompt form submission to the selected strategy.
     *
     * The whole request is forwarded: extracting the relevant fields is the strategy's
     * responsibility, as their number and meaning depend on the verification method.
     */
    public function verify(Request $request): bool
    {
        $strategy = $this->getStrategy();

        return $strategy->verify($_SESSION['glpiID'], $request);
    }

    /**
     * Make the user reauthenticated
     *
     * Consider current user as reauthenticated
     * Set the reauth session validity to now + delay (self::REAUTH_DELAY_SECONDS).
     * Used to make user reauthenticated just after login.
     */
    public function authenticate(): void
    {
        $_SESSION['glpi_reauth_until'] = (new DateTime($_SESSION['glpi_currenttime']))
            ->modify('+' . self::REAUTH_DELAY_SECONDS . ' seconds')
            ->getTimestamp();
    }

    /**
     * Drop the current reauth session validity.
     *
     * After this call, the user is no longer considered reauthenticated and any
     * action requiring reauth will redirect to the prompt again.
     *
     * This is only meant to set up the "not reauthenticated" state in e2e tests
     * (Cypress/Playwright), so it is restricted to test environments and throws
     * anywhere else.
     */
    public function revoke(): void
    {
        $environment = Environment::get();
        if ($environment !== Environment::TESTING && $environment !== Environment::E2E) {
            throw new RuntimeException(
                'ReAuthManager::revoke() must not be called outside of the "testing" and "e2e_testing" environments.'
            );
        }

        unset($_SESSION['glpi_reauth_until']);
    }

    public function getLabel(): string
    {
        return $this->getStrategy()->getLabel();
    }

    public function getPromptTemplate(): string
    {
        return $this->getStrategy()->getPromptTemplate();
    }

    public function getRequestedURL(): string
    {
        return $_SESSION['glpi_reauth_requested_url'] ?? '/';
    }

    /**
     * URL of the page the user was on when the reauth-requiring action was triggered.
     */
    public function getOriginURL(): string
    {
        global $CFG_GLPI;

        return $_SESSION['glpi_reauth_origin_url'] ?? $CFG_GLPI["root_doc"];
    }

    public function setOriginURL(string $url): void
    {
        $_SESSION['glpi_reauth_origin_url'] = $url;
    }

    /**
     * query data + _glpi_http_referer (POST replays only)
     *
     * `_glpi_http_referer` is set to the origin page (the referer captured on
     * the first pass, @see getOriginURL()) so that Html::back()/getBackUrl() on
     * the replayed request returns the original page and not the reauth flow.
     * Html::getRefererUrl() only reads $_POST['_glpi_http_referer'], so it is
     * pointless to inject it for a replayed GET request (it would only ever
     * land in $_GET).
     *
     * @return array<array-key, mixed>
     */
    public function getRequestedPostData(): array
    {
        $reauth_data = $_SESSION['glpi_reauth_requested_post_data'] ?? [];

        if ($this->getRequestedMethod() !== 'POST') {
            return $reauth_data;
        }

        return $reauth_data + ['_glpi_http_referer' => $this->getOriginURL()];
    }

    /**
     * @return 'POST'|'GET'
     */
    public function getRequestedMethod(): string
    {
        return $_SESSION['glpi_reauth_requested_httpmethod'] ?? 'GET';
    }

    public function getVerifyUrl(): string
    {
        return $this->getStrategy()->getVerifyUrl();
    }

    public function getVerifyHttpMethod(): string
    {
        return $this->getStrategy()->getVerifyHttpMethod();
    }

    /**
     * returns true if at least one of the item_types require reauth
     *
     * @param array<int, class-string<\CommonGLPI>> $item_types item type to check
     */
    public function atLeastOneItemTypesRequiresReauthentication(array $item_types): bool
    {
        foreach ($item_types as $item_type) {
            if (!is_a($item_type, \CommonGLPI::class, true)) {
                throw new InvalidArgumentException(sprintf('Invalid item type "%s"', (string) $item_type));
            }
        }

        return array_reduce(
            $item_types,
            fn($carry, string $item_type) => $carry || $item_type::isUserReauthenticationNeeded(),
            false
        );
    }

    /**
     * Register a new strategy in available strategies
     */
    public function registerStrategy(ReAuthStrategyInterface $strategy): void
    {
        $this->additional_strategies[$strategy::class] = $strategy;
    }

    /**
     * Record the request that was requires reauthentication (url + POST/GET data)
     */
    private function setRequestedTarget(): void
    {
        /** @var Kernel $kernel */
        global $kernel;
        $request = $kernel->getMainRequest();
        $is_post = $request->isMethod('POST');

        // For POST requests, the GET query string in the action URL is preserved by the
        // browser on replay, so it must be stored. For GET requests, the browser rebuilds
        // the query string from the form fields on submit, so keeping it here would be
        // both useless and misleading.
        $current_url = $is_post
            ? $request->getUri()
            : $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();

        $this->setRequestedURL($current_url);
        $this->setRequestedMethod($is_post ? 'POST' : 'GET');
        $this->setRequestedData($is_post ? $request->request->all() : $request->query->all());
    }

    private function getStrategy(): ReAuthStrategyInterface
    {
        if ($this->strategy === null) {
            $this->strategy = $this->resolvePreferred($_SESSION['glpiID']);
        }

        return $this->strategy;
    }

    private function resolvePreferred(int $users_id): ReAuthStrategyInterface
    {
        $available = $this->getAvailableStrategies($users_id);

        if ($available === []) {
            throw new RuntimeException('No re-authentication strategy available for this user');
        }

        // Sort strategies by priority (descending): highest priority first
        usort($available, static fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $available[0];
    }

    private function isReAuthRoute(string $url): bool
    {
        global $CFG_GLPI;

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return false;
        }

        return str_starts_with($path, $CFG_GLPI['root_doc'] . '/ReAuth/');
    }

    /**
     * @return ReAuthStrategyInterface[]
     */
    private function getAvailableStrategies(int $users_id): array
    {
        $strategies = [];

        // native strategies
        foreach (ReAuthStrategyEnum::cases() as $case) {
            $strategy = $case->createStrategy();
            if ($strategy->isAvailable($users_id)) {
                $strategies[] = $strategy;
            }
        }

        // add registered strategies (from plugins)
        foreach ($this->additional_strategies as $strategy) {
            if ($strategy->isAvailable($users_id)) {
                $strategies[] = $strategy;
            }
        }

        return $strategies;
    }

    private function setRequestedURL(string $url): void
    {
        $_SESSION['glpi_reauth_requested_url'] = $url;
    }

    /**
     * Nested values are supported: query strings and POST bodies carry them, and the replay
     * template renders them recursively.
     *
     * @param array<array-key, mixed> $post
     */
    private function setRequestedData(array $post): void
    {
        $_SESSION['glpi_reauth_requested_post_data'] = $post;
    }

    /**
     * @param 'POST'|'GET' $http_method
     */
    private function setRequestedMethod(string $http_method): void
    {
        $_SESSION['glpi_reauth_requested_httpmethod'] = match ($http_method) {
            'GET'  => 'GET',
            'POST' => 'POST',
            default => throw new \LogicException(sprintf('Unsupported HTTP method for redirect: %s', $http_method)),
        };
    }

    /**
     * Can the current request be answered with the prompt page?
     *
     * Same discrimination as {@see AccessErrorListener}:
     * an AJAX caller would inject the prompt page in the current one, and a client expecting
     * anything else than HTML has nothing to do with it.
     */
    private function canDisplayPrompt(): bool
    {
        /** @var Kernel $kernel */
        global $kernel;

        $request = $kernel->getMainRequest();

        return !$request->isXmlHttpRequest() && $request->getPreferredFormat() === 'html';
    }

    /**
     * Record the page the current request was issued from as the target of the re-authentication.
     *
     * Used when the prompt cannot be served as the answer of the request: the client is expected to
     * send the browser to the prompt on its own, so the flow must already know where to land
     * afterwards. The rejected request itself is not a suitable target — it is an endpoint, not a
     * page, and replaying it would display its raw answer — so the user has to redo the action once
     * back on the page.
     */
    private function setCallerPageAsTarget(): void
    {
        $caller_page = $this->getCallerPageUrl();

        // The replay is submitted as a form, and the browser rebuilds the query string of a GET
        // from its fields: the query string must be handed over as data instead of being left in
        // the action URL, or it would be dropped. @see setRequestedTarget()
        $query_string = parse_url($caller_page, PHP_URL_QUERY);
        $query_params = [];
        if (is_string($query_string)) {
            parse_str($query_string, $query_params);
        }

        $this->setRequestedURL(strstr($caller_page, '?', true) ?: $caller_page);
        $this->setRequestedMethod('GET');
        $this->setRequestedData($query_params);
        // Cancelling the prompt leads back to where the user was, i.e. that same page. It is a
        // plain link, so it keeps the query string.
        $this->setOriginURL($caller_page);
    }

    /**
     * Absolute URL of the page the current request was issued from.
     *
     * The `Referer` header is the only hint available, as the request URL is the endpoint that was
     * called and not the page holding it. It cannot be trusted: it may be forged, dropped by a
     * referrer policy, or point outside of GLPI. Only its path and query string are kept and the
     * host is rebuilt from the current request, so an unusable value degrades into the GLPI home
     * page instead of turning the re-authentication into an open redirection.
     */
    private function getCallerPageUrl(): string
    {
        global $CFG_GLPI;

        /** @var Kernel $kernel */
        global $kernel;

        $request  = $kernel->getMainRequest();
        $home_url = $request->getSchemeAndHttpHost() . $CFG_GLPI['root_doc'] . '/';

        $referer = \Html::getRefererUrl();
        if ($referer === null) {
            return $home_url;
        }

        $path = parse_url($referer, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, $CFG_GLPI['root_doc'] . '/')) {
            return $home_url;
        }

        $query        = parse_url($referer, PHP_URL_QUERY);
        $relative_url = $path . (is_string($query) ? '?' . $query : '');

        // Landing on the re-authentication flow itself would loop.
        if (!URL::isGLPIRelativeUrl($relative_url) || $this->isReAuthRoute($relative_url)) {
            return $home_url;
        }

        return $request->getSchemeAndHttpHost() . $relative_url;
    }
}
