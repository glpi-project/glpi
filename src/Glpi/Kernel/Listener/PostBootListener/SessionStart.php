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

namespace Glpi\Kernel\Listener\PostBootListener;

use Glpi\Debug\Profiler;
use Glpi\Http\RequestRouterTrait;
use Glpi\Http\SessionManager;
use Glpi\Kernel\KernelListenerTrait;
use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Session;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

use function Safe\ini_set;

/**
 * @final
 */
class SessionStart implements EventSubscriberInterface
{
    use KernelListenerTrait;
    use RequestRouterTrait;

    public function __construct(
        private SessionManager $session_manager,
        #[Autowire('%kernel.project_dir%')]
        string $glpi_root,
        array $plugin_directories = GLPI_PLUGINS_DIRECTORIES,
        private string $php_sapi = PHP_SAPI
    ) {
        $this->glpi_root = $glpi_root;
        $this->plugin_directories = $plugin_directories;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        global $CFG_GLPI;

        Profiler::getInstance()->start('SessionStart::execute', Profiler::CATEGORY_BOOT);

        // Always set the session files path, even when session is not started automatically here.
        // It can indeed be started later manually and the path must be correctly set when it is done.
        if (Session::canWriteSessionFiles()) {
            Session::setPath();
        } else {
            \trigger_error(
                sprintf('Unable to write session files on `%s`.', GLPI_SESSION_DIR),
                E_USER_WARNING
            );
        }

        if ($this->php_sapi === 'cli') {
            $is_stateless = true;
        } else {
            $request = Request::createFromGlobals();
            $is_stateless = $this->session_manager->isResourceStateless($request);
        }

        if (!$is_stateless) {
            Session::start();
        } else {
            if ($this->php_sapi !== 'cli') {
                // Stateless endpoints will often have to start their own PHP session (based on a token for instance).
                // Be sure to not use cookies defined in the request or to send a cookie in the response.
                ini_set('session.use_cookies', 0);
            }

            // The session base vars must always be defined.
            // Indeed, the GLPI code often refers to the `$_SESSION` variable
            // and we have to set them to prevent massive undefined array key access.
            Session::initVars();
        }

        // Copy the "preference" defaults to the session, if they are not already set.
        // They are set during the authentication but may be accessed in a sessionless context (cron, anonymous pages, ...).
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
                $_SESSION["glpi$field"] = $CFG_GLPI[$field];
            }
        }

        Profiler::getInstance()->stop('SessionStart::execute');
    }
}
