<?php

namespace Glpi\Http\Listener;

use Glpi\Http\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SessionStartListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        // The session must be started even in CLI context.
        // The GLPI code refers to the session in many places
        // and we cannot safely remove its initialization in the CLI context.
        $start_session = true;

        if (isset($_SERVER['REQUEST_URI'])) {
            // Specific configuration related to web context

            $request = Request::createFromGlobals();
            $path = $request->getPathInfo();

            $use_cookies = true;
            if (\str_starts_with($path, '/api.php') || \str_starts_with($path, '/apirest.php')) {
                // API clients must not use cookies, as the session token is expected to be passed in headers.
                $use_cookies = false;
                // The API endpoint is strating the session manually.
                $start_session = false;
            } elseif (\str_starts_with($path, '/caldav.php')) {
                // CalDAV clients must not use cookies, as the authentication is expected to be passed in headers.
                $use_cookies = false;
            } elseif (\str_starts_with($path, '/front/cron.php')) {
                // The cron endpoint is not expected to use the authenticated user session.
                $use_cookies = false;
            } elseif (\str_starts_with($path, '/front/planning.php') && $request->query->has('genical')) {
                // The `genical` endpoint must not use cookies, as the authentication is expected to be passed in the query parameters.
                $use_cookies = false;
            }

            if (!$use_cookies) {
                ini_set('session.use_cookies', 0);
            }
        }

        if ($start_session) {
            if (Session::canWriteSessionFiles()) {
                Session::setPath();
            } else {
                \trigger_error(
                    sprintf('Unable to write session files on `%s`.', GLPI_SESSION_DIR),
                    E_USER_WARNING
                );
            }

            Session::start();
        }
    }
}
