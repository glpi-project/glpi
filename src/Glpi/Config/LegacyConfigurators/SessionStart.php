<?php

namespace Glpi\Config\LegacyConfigurators;

use Glpi\Config\ConfigProviderHasRequestTrait;
use Session;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviderInterface;

final class SessionStart implements LegacyConfigProviderInterface, ConfigProviderWithRequestInterface
{
    use ConfigProviderHasRequestTrait;

    /**
     * An array of regular expressions of the paths that disable the Session.
     */
    private const NO_SESSION_PATHS = [
        '/api(rest)?\.php.*',
        '/caldav\.php.*',
    ];

    public function execute(): void
    {
        $path = $this->getRequest()->getRequestUri();
        $path = '/' . ltrim($path, '/');

        $noSessionPathsRegexes = \array_map(static fn ($regex) => '(?:' . $regex . ')', self::NO_SESSION_PATHS);

        $fullRegex = '~^' . implode('|', $noSessionPathsRegexes) . '$~sUu';

        Session::setPath();

        if (
            \preg_match($fullRegex, $path)
            || (\preg_match('~^/front/planning\.php~Uu', $path) && $this->getRequest()->query->has('genical'))
        ) {
            // Disable session cookie for these paths
            ini_set('session.use_cookies', 0);
        }

        Session::start();

        // Default Use mode
        if (!isset($_SESSION['glpi_use_mode'])) {
            $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        }
    }
}
