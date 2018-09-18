<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (version_compare(PHP_VERSION, '5.6') < 0) {
   die('PHP >= 5.6 required');
}

use Glpi\Event;
use Tracy\Debugger;

//Load GLPI constants
define('GLPI_ROOT', __DIR__ . '/..');
include_once GLPI_ROOT . "/inc/based_config.php";
include_once GLPI_ROOT . "/inc/define.php";
include_once GLPI_ROOT . "/inc/autoload.function.php";

//define('DO_NOT_CHECK_HTTP_REFERER', 1);

RunTracy\Helpers\Profiler\Profiler::enable();
Debugger::enable(Debugger::DEVELOPMENT, GLPI_LOG_DIR);
Debugger::timer();

// If config_db doesn't exist -> start installation
if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   Html::redirect("install/install.php");
   die();
} else {
   $TRY_OLD_CONFIG_FIRST = true;
   include (GLPI_ROOT . "/inc/includes.php");

   // Create app
   $app_settings = [
      'settings' => [
         'displayErrorDetails'               => true,
         'determineRouteBeforeAppMiddleware' => true,
         'addContentLengthHeader'            => false,
         'tracy'                             => [
            'showPhpInfoPanel'          => 1,
            'showSlimRouterPanel'       => 1,
            'showSlimEnvironmentPanel'  => 1,
            'showSlimRequestPanel'      => 1,
            'showSlimResponsePanel'     => 1,
            'showSlimContainer'         => 1,
            'showTwigPanel'             => 1,
            'showProfilerPanel'         => 0,
            'showVendorVersionsPanel'   => 0,
            'showIncludedFiles'         => 0,
            'configs' => [
               'ProfilerPanel' => [
                  // Memory usage 'primaryValue' set as Profiler::enable() or Profiler::enable(1)
                  // 'primaryValue' =>                   'effective',    // or 'absolute'
                  'show' => [
                     'memoryUsageChart' => 1, // or false
                     'shortProfiles' => true, // or false
                     'timeLines' => true // or false
                  ]
               ]
            ]
         ]
      ],
      'logger' => [
         'name'   => 'GLPI',
         'level'  => Monolog\Logger::DEBUG,
         'path'   => GLPI_LOG_DIR . '/php-errors.log',
      ],
   ];
   RunTracy\Helpers\Profiler\Profiler::start('initApp');
   $app = new \Slim\App($app_settings);
   RunTracy\Helpers\Profiler\Profiler::finish('initApp');

   // Get container
   $container = $app->getContainer();
   $router = $container['router'];

   $container['flash'] = function() {
      return new \Slim\Flash\Messages();
   };

   // Register component on container
   $container['view'] = function ($container) {
      $view = new \Slim\Views\Twig(__DIR__ .'/../templates', [
         'cache'              => ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) ? GLPI_CACHE_DIR : false,
         'debug'              => ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE),
         'strict_variables'   => true,
         'auto_reload'        => ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
      ]);

      $view->getEnvironment()->addGlobal(
         "current_path",
         $container["request"]->getUri()->getPath()
      );

      global $CFG_GLPI;
      $view->getEnvironment()->addGlobal(
         "CFG_GLPI",
         $CFG_GLPI
      );

      $view->getEnvironment()->addGlobal(
         'glpi_layout',
         $_SESSION['glpilayout']
      );

      $view->getEnvironment()->addGlobal(
         'glpi_debug',
         $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
      );

      $view->getEnvironment()->addGlobal(
         'glpi_lang',
         $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]
      );

      $view->getEnvironment()->addGlobal(
         'glpi_lang_name',
         Dropdown::getLanguageName($_SESSION['glpilanguage'])
      );

      //TODO: use a conf
      $view->getEnvironment()->addGlobal(
        'glpi_skin',
        'blue'
      );

      //TODO: change that!
      if (Session::getLoginUserID()) {
         ob_start();
         \Html::showProfileSelecter($container['router']->pathFor('slash'));
         $selecter = ob_get_contents();
         ob_end_clean();

         $view->getEnvironment()->addGlobal(
            'glpi_profile_selecter',
            $selecter
         );
      }

      $logged = false;
      if (Session::getLoginUserID()) {
         $logged = true;
         $view->getEnvironment()->addGlobal(
            'user_name',
            formatUserName(
               0,
               $_SESSION["glpiname"],
               $_SESSION["glpirealname"],
               $_SESSION["glpifirstname"],
               0,
               20
            )
         );
      }
      $view->getEnvironment()->addGlobal('is_logged', $logged);

      $menus = Html::generateMenuSession($container['router'], ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE));
      $view->getEnvironment()->addGlobal(
         'glpi_menus',
         $menus
      );

      // Instantiate and add Slim specific extension
      $basePath = str_replace(
         ['index.php', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']],
         ['', ''],
         $container['request']->getUri()->getBaseUrl()
      );
      $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
      $view->addExtension(new \Twig_Extensions_Extension_I18n());
      $view->addExtension(new Twig_Extension_Profiler($container['twig_profile']));
      $view->addExtension(new \Twig_Extension_Debug());
      include_once __DIR__ . '/../twig_extensions/Reflection.php';
      $view->addExtension(new \Twig\Glpi\Extensions\Reflection());
      include_once __DIR__ . '/../twig_extensions/Locales.php';
      $view->addExtension(new \Twig\Glpi\Extensions\Locales());
      include_once __DIR__ . '/../twig_extensions/GlpiDebug.php';
      $view->addExtension(new \Twig\Glpi\Extensions\GlpiDebug());
      $view->addExtension(new Knlv\Slim\Views\TwigMessages(
         new Slim\Flash\Messages()
      ));

      return $view;
   };

   $container['twig_profile'] = function () {
      return new Twig_Profiler_Profile();
   };

   RunTracy\Helpers\Profiler\Profiler::start('Register Middlewares');

   /**
    * Switch middleware (to get UI reloaded after switching
    * debug mode, language, ...
    */
   $app->add(function ($request, $response, $next) {
      $get = $request->getQueryParams();

      $route = $request->getAttribute('route');
      if (!$route) {
         return $next($request, $response);
      }
      $arguments = $route->getArguments();
      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         isset($arguments['itemtype']) ? $arguments['itemtype'] : ''
      );

      if (isset($get['switch'])) {
         $switch_route = $get['switch'];
         $uri = $request->getUri();
         $route_name = $route->getName();

         $_SESSION['glpi_switch_route'] = [
            'name'      => $route_name,
            'arguments' => $arguments
         ];

         return $response->withRedirect($this->router->pathFor($switch_route), 301);
      }
      return $next($request, $response);
   });

   /**
    * Detect javascript dependencies
    */
   $app->add(function ($request, $response, $next) use ($CFG_GLPI) {
      $get = $request->getQueryParams();
      $route = $request->getAttribute('route');
      $arguments = [];
      if ($route) {
         $arguments = $route->getArguments();
         $key = $route->getName();
      }

      if (isset($arguments['itemtype'])) {
         $key = mb_strtolower($arguments['itemtype']);
      }

      if (!isset($key)) {
         return $next($request, $response);
      }

      $requirements = Html::getJsRequirements($CFG_GLPI['javascript'], $key);

      $css_paths = [];
      $js_paths = [];

      $js_libs = 'public/bower_components';
      if (in_array('fullcalendar', $requirements)) {
         $css_paths = array_merge($css_paths, [
            $js_libs . '/fullcalendar/dist/fullcalendar.css',
            [
               'path'   => $js_libs . '/fullcalendar/dist/fullcalendar.print.css',
               'media'  => 'print'
            ]
         ]);
         $js_paths = array_merge($js_paths, [
            $js_libs . '/moment/min/moment-with-locales.min.js',
            $js_libs . '/fullcalendar/dist/fullcalendar.min.js',
         ]);

         if (isset($_SESSION['glpilanguage'])) {
            foreach ([2, 3] as $loc) {
               $filename = $js_libs . "/fullcalendar/dist/locale/".
                  strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][$loc]).".js";
               if (file_exists(GLPI_ROOT . '/' . $filename)) {
                  $js_paths[] = $filename;
                  break;
               }
            }
         }
      }

      if (in_array('gantt', $requirements)) {
         $css_paths[] = 'lib/jqueryplugins/jquery-gantt/css/style.css';
         $js_paths[] = 'lib/jqueryplugins/jquery-gantt/js/jquery.fn.gantt.js';
      }

      if (in_array('rateit', $requirements)) {
         $css_paths[] = 'lib/jqueryplugins/rateit/rateit.css';
         $js_paths[] = 'lib/jqueryplugins/rateit/jquery.rateit.js';
      }

      if (in_array('colorpicker', $requirements)) {
         $css_paths[] = 'lib/jqueryplugins/spectrum-colorpicker/spectrum.min.css';
         $js_paths[] = 'lib/jqueryplugins/spectrum-colorpicker/spectrum-min.js';
      }

      if (in_array('gridstack', $requirements)) {
         $css_paths = array_merge($css_paths, [
            'lib/gridstack/src/gridstack.css',
            'lib/gridstack/src/gridstack-extra.css'
         ]);
         $js_paths = array_merge($js_paths, [
            'lib/lodash.min.js',
            'lib/gridstack/src/gridstack.js',
            'lib/gridstack/src/gridstack.jQueryUI.js',
            'js/rack.js'
         ]);
      }

      if (in_array('tinymce', $requirements)) {
         //$js_paths[] = 'lib/tiny_mce/tinymce.js';
         $js_paths[] = 'lib/tiny_mce/lib/tinymce.min.js';
      }

      if (in_array('clipboard', $requirements)) {
         $js_paths[] = 'js/clipboard.js';
      }

      if (in_array('charts', $requirements)) {
         $css_paths = array_merge($css_paths, [
            'lib/chartist-js-0.10.1/chartist.css',
            'css/chartists-glpi.css',
            'lib/chartist-plugin-tooltip-0.0.17/chartist-plugin-tooltip.css'
         ]);
         $js_paths = array_merge($js_paths, [
            'lib/chartist-js-0.10.1/chartist.js',
            'lib/chartist-plugin-legend-0.6.0/chartist-plugin-legend.js',
            'lib/chartist-plugin-tooltip-0.0.17/chartist-plugin-tooltip.js'
         ]);
      }

      $this->view->getEnvironment()->addGlobal(
         "css_paths",
         $css_paths
      );
      $this->view->getEnvironment()->addGlobal(
         "js_paths",
         $js_paths
      );

      return $next($request, $response);
   });

   $app->add(new RunTracy\Middlewares\TracyMiddleware($app));

   RunTracy\Helpers\Profiler\Profiler::finish('Register Middlewares');

   RunTracy\Helpers\Profiler\Profiler::start('Register routes');

   $app->get('/', function ($request, $response, $args) {
      //TODO: do some checks and redirect the the right URL
      $redirect_uri = $this->router->pathFor('login');
      if (Session::getLoginUserID()) {
         $redirect_uri = $this->router->pathFor('central');
      }
      return $response->withRedirect($redirect_uri, 301);
   })->setName('slash');

   $app->get('/login', function ($request, $response, $args) use ($CFG_GLPI) {
      //if user is logged in, redirect to /central
      if (Session::getLoginUserID()) {
         $redirect_uri = $this->router->pathFor('central');
         return $response->withRedirect($redirect_uri, 301);
      }

      $glpi_form = [
         'header'       => false,
         'columns'      => 1,
         'boxed'        => false,
         'submit_label' => __('Login'),
         'elements'  => [
            'login'     => [
               'type'         => 'text',
               'name'         => 'login_name',
               'required'     => true,
               'placeholder'  => __('Login'),
               'autofocus'    => true,
               'preicons'     => ['user-circle']
            ],
            'password'  => [
               'type'         => 'password',
               'name'         => 'login_password',
               'required'     => true,
               'placeholder'  => __('Password'),
               'preicons'     => ['unlock']
            ]
         ]
      ];

      if (GLPI_DEMO_MODE) {
         //lang selector
         $glpi_form['elements']['language'] = [
            'empty_value'  => true,
            'empty_text'   => __('Default (from user profile)'),
            'type'         => 'select',
            'name'         => 'language',
            'values'       => Dropdown::getLanguages(),
            'listicon'     => false,
            'addicon'      => false
         ];
      }

      $auth_methods = \Auth::getLoginAuthMethods();
      $default = $auth_methods['_default'];
      unset($auth_methods['_default']);
      if (count($auth_methods) > 1) {
         $glpi_form['elements']['auth'] = [
            'type'   => 'select',
            'name'   => 'auth',
            'value'  => $default,
            'values' => $auth_methods,
            'listicon'  => false,
            'addicon'   => false
         ];
      } else {
         $glpi_form['elements']['auth'] = [
            'type'   => 'hidden',
            'name'   => 'auth',
            'value'  => key($auth_methods)
         ];
      }

      if ($CFG_GLPI['login_remember_time']) {
         $glpi_form['elements']['remember'] = [
            'type'      => 'checkbox',
            'name'      => 'login_remember',
            'label'     => __('Remember me')
         ];
      }

      if (isset($_GET["noAUTO"])) {
         // Other CAS
         $glpi_form['elements']['noAUTO'] = [
            'type'   => 'hidden',
            'name'   => 'noAUTO',
            'value'  => 1
         ];
      }

      $show_lostpass = false;
      if ($CFG_GLPI["notifications_mailing"]) {
         $active = countElementsInTable(
            'glpi_notifications', [
               'itemtype'  => 'User',
               'event'     => 'passwordforget',
               'is_active' => 1
            ]
         );
         if ($active > 0) {
            $show_lostpass = true;
         }
      }

      $show_faq = false;
      if ($CFG_GLPI["use_public_faq"]) {
         $show_faq = true;
      }

      return $this->view->render(
         $response,
         'login.twig', [
            'glpi_form'       => $glpi_form,
            'show_lostpass'   => $show_lostpass,
            'show_faq'        => $show_faq
         ]
      );
   })->setName('login');

   $app->post('/login', function ($request, $response) use ($CFG_GLPI) {
      $post = $request->getParsedBody();
      $post = array_map('stripslashes', $post);

      //There is certainly a beter way to achieve that test (JS?)
      /*if (!isset($_SESSION["glpicookietest"]) || ($_SESSION["glpicookietest"] != 'testcookie')) {
         if (!is_writable(GLPI_SESSION_DIR)) {
            Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=2");
         } else {
            Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=1");
         }
      }*/

      $login = $post['login_name'];
      $password = $post['login_password'];
      $login_auth = $post['auth'];
      $remember = isset($post['login_remember']) && $CFG_GLPI["login_remember_time"];
      $noauto = isset($_REQUEST["noAUTO"]) ? $_REQUEST["noAUTO"] : false;

      if (empty($login) || empty($password) || empty($login_auth)) {
         $this->flash->addMessage(
            'error',
            __('Missing authentication information.')
         );
         return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('login'));
      }

      /*
      // Redirect management
      $REDIRECT = "";
      if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
         $REDIRECT = "?redirect=" .rawurlencode($_POST['redirect']);

      } else if (isset($_GET['redirect']) && strlen($_GET['redirect'])>0) {
         $REDIRECT = "?redirect=" .rawurlencode($_GET['redirect']);
      }*/

      $auth = new Auth();

      // now we can continue with the process...
      if ($auth->login($login, $password, $noauto, $remember, $login_auth)) {
         return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('central'));
         Auth::redirectIfAuthenticated();
      } else {
         foreach ($auth->getErrors() as $error) {
            $this->flash->addMessage(
               'error',
               $error
            );
         }
         return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('login'));
         // we have done at least a good login? No, we exit.
         /*Html::nullHeader("Login", $CFG_GLPI["root_doc"] . '/index.php');
         echo '<div class="center b">' . $auth->getErr() . '<br><br>';
         // Logout whit noAUto to manage auto_login with errors
         echo '<a href="' . $CFG_GLPI["root_doc"] . '/front/logout.php?noAUTO=1'.
               str_replace("?", "&", $REDIRECT).'">' .__('Log in again') . '</a></div>';
         Html::nullFooter();
         exit();*/
      }
   })->setName('do-login');

   $app->get('/logout', function ($request, $response) use ($CFG_GLPI) {
      /*if (!isset($_SESSION["noAUTO"])
         && isset($_SESSION["glpiauthtype"])
         && $_SESSION["glpiauthtype"] == Auth::CAS
         && Toolbox::canUseCAS()) {

         phpCAS::client(CAS_VERSION_2_0, $CFG_GLPI["cas_host"], intval($CFG_GLPI["cas_port"]),
                        $CFG_GLPI["cas_uri"], false);
         phpCAS::setServerLogoutURL(strval($CFG_GLPI["cas_logout"]));
         phpCAS::logout();
      }*/

      //$toADD = "";

      // Redirect management
      /*if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
         $toADD = "?redirect=" .$_POST['redirect'];

      } else if (isset($_GET['redirect']) && (strlen($_GET['redirect']) > 0)) {
         $toADD = "?redirect=" .$_GET['redirect'];
      }*/

      /*if (isset($_SESSION["noAUTO"]) || isset($_GET['noAUTO'])) {
         if (empty($toADD)) {
            $toADD .= "?";
         } else {
            $toADD .= "&";
         }
         $toADD .= "noAUTO=1";
      }*/

      \Session::destroy();

      //Remove cookie to allow new login
      $cookie_name = session_name() . '_rememberme';
      $cookie_path = ini_get('session.cookie_path');

      if (isset($_COOKIE[$cookie_name])) {
         setcookie($cookie_name, '', time() - 3600, $cookie_path);
         unset($_COOKIE[$cookie_name]);
      }

      // Redirect to the login-page
      //Html::redirect($CFG_GLPI["root_doc"]."/index.php".$toADD);
      return $response
         ->withStatus(301)
         ->withHeader('Location', $this->router->pathFor('login'));
   })->setName('logout');

   $app->get('/lost-password', function ($request, $response, $args) use ($CFG_GLPI) {
      $go = true;
      if ($CFG_GLPI["notifications_mailing"]) {
         $active = countElementsInTable(
            'glpi_notifications', [
               'itemtype'  => 'User',
               'event'     => 'passwordforget',
               'is_active' => 1
            ]
         );
         if ($active == 0) {
            $go = false;
         }
      } else {
         $go = false;
      }

      if (!$go) {
         $this->flash->addMessage(
            'error',
            __('Password recovering is not enabled.')
         );

         return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('login'));
      }

      //TODO: display password recovering page
   })->setName('lostPassword');


   $app->get('/central', function ($request, $response, $args) {
      $central = new Central();

      ob_start();
      $central->display();
      $contents = ob_get_contents();
      ob_end_clean();
      return $this->view->render(
         $response,
         'central.twig',
         ['contents' => $contents]
      );
   })->setName('central');

   $app->get('/knowbase', function ($request, $response) {
      $get = $request->getQueryParams();

      // Clean for search
      $_GET = Toolbox::stripslashes_deep($_GET);

      // Search a solution
      if (!isset($_GET["contains"])
         && isset($_GET["item_itemtype"])
         && isset($_GET["item_items_id"])) {

         if ($item = getItemForItemtype($_GET["item_itemtype"])) {
            if ($item->getFromDB($_GET["item_items_id"])) {
               $_GET["contains"] = $item->getField('name');
            }
         }
      }

      // Manage forcetab : non standard system (file name <> class name)
      if (isset($_GET['forcetab'])) {
         Session::setActiveTab('Knowbase', $_GET['forcetab']);
         unset($_GET['forcetab']);
      }

      $kb = new Knowbase();
      ob_start();
      $kb->display($_GET);
      $contents = ob_get_contents();
      ob_end_clean();

      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         'KnowbaseItem'
      );

      return $this->view->render(
         $response,
         'legacy.twig', [
            'page_title'      => KnowbaseItem::getTypeName(Session::getPluralNumber()),
            'contents'        => $contents,
            'item'            => $kb
         ]
      );
   })->setName('knowbase');

   $app->get('/{itemtype}/list[/page/{page:\d+}[/{reset:reset}]]', function ($request, $response, $args) {
      $item = new $args['itemtype']();
      $params = $request->getQueryParams() + $args;
      if (isset($args['reset'])) {
         $params = $args;
         unset($params['reset']);
         $this->flash->addMessage('info', __('Search params has been reset'));
      }
      if (isset($args['page'])) {
         $params['start'] = ($args['page'] - 1) * $_SESSION['glpilist_limit'];
      }
      $search = new Search($item, $params);
      if (isset($args['page'])) {
         $search->setPage((int)$args['page']);
      }
      $data = $search->getData();

      //legacy
      $params = Search::manageParams($item->getType(), $_GET);

      ob_start();
      Search::showGenericSearch($item->getType(), $params);
      $search_form = ob_get_contents();
      ob_end_clean();
      $search_form = preg_replace(
         '/<\/?form[^>]*?>/',
         '',
         $search_form
      );

      /*ob_start();
      if ($params['as_map'] == 1) {
         Search::showMap($item->getType(), $params, $data);
      } else {
         Search::showList($item->getType(), $params, $data);
      }
      $contents = ob_get_contents();
      ob_end_clean();*/
      //end legacy

      $get = $request->getQueryParams();

      //var_dump(Search::getCleanedOptions($item->getType()));
      return $this->view->render(
         $response,
         'list.twig', [
            'page_title'      => $item->getTypeName(Session::getPluralNumber()),
            'search_data'     => $data,
            'search_form'     => $search_form,
            'item'            => $item,
            'old_search'      => isset($get['querybuilder']) ? false : true,
            'search_options'  => Search::getCleanedOptions($item->getType())
         ]
      );
   })->setName('list');

   $app->get('/{itemtype}/add[/{withtemplate}]', function ($request, $response, $args) {
      $item = new $args['itemtype']();
      $get = $request->getQueryParams();
      $display_options = [
         'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
      ];

      if (isset($get['itemtype'])) {
         $display_options['itemtype'] = $get['itemtype'];
      }
      if (isset($get['items_id'])) {
         $display_options['items_id'] = $get['items_id'];
      }
      if ($args['itemtype'] == Config::getType()) {
         //hack for config...
         $display_options['id'] = 1;
      }

      //reload data from session on error
      if (isset($get['item_rand'])) {
         $item->getFromResultset($_SESSION["{$args['itemtype']}_add_{$get['item_rand']}"]);
         unset($_SESSION["{$args['itemtype']}_add_{$get['item_rand']}"]);
      }

      $params = [];
      $tpl = 'add_page';

      if (!$item->isTwigCompat() && !isset($get['twig'])) {
         $tpl = 'legacy';
         Toolbox::deprecated(
            sprintf(
               '%1$s is not compatible with new templating system!',
               $args['itemtype']
            )
         );
         ob_start();
         $item->display($display_options);
         $params['contents'] = ob_get_contents();
         ob_end_clean();
      } else {
         if ($item instanceof CommonITILObject) {
            $tpl = 'itil_add_page';
         }
         $params['glpi_form'] = $item->getAddForm();
         if (!isset($form['action'])) {
            $form['action'] = $this->router->pathFor(
               'do-add-asset',
               $args
            );
         }
      }

      return $this->view->render(
         $response,
         $tpl . '.twig', [
            'page_title'   => sprintf(
               __('%1$s - %2$s'),
               __('New item'),
               $item->getTypeName(1)
            ),
            'item'         => $item,
            'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
         ] + $params
      );
   })->setName('add-asset');

   $app->get('/ajax/tab/{itemtype}/{id:\d+}/{tab}', function ($request, $response, $args) {
      $get = $request->getQueryParams();
      /*if (!isset($_GET["sort"])) {
         $_GET["sort"] = "";
      }

      if (!isset($_GET["order"])) {
         $_GET["order"] = "";
      }*/

      if (!isset($get["withtemplate"])) {
         $get["withtemplate"] = "";
      }

      if ($item = getItemForItemtype($args['itemtype'])) {
         if ($item->get_item_to_display_tab) {
            // No id if ruleCollection but check right
            if ($item instanceof RuleCollection) {
               if (!$item->canList()) {
                  //TODO: redirect with error
                  throw new \RuntimeException('Cannot list');
                  exit();
               }
            } else if (!$item->can($args["id"], READ)) {
               //TODO: redirect with error
               throw new \RuntimeException('Cannot read');
               exit();
            }
         }
      }

      $notvalidoptions = ['_glpi_tab', '_itemtype', 'sort', 'order', 'withtemplate'];
      $options         = $get;
      foreach ($notvalidoptions as $key) {
         if (isset($options[$key])) {
            unset($options[$key]);
         }
      }
      if (isset($options['locked'])) {
         ObjectLock::setReadOnlyProfile();
      }

      $tab = explode('__', $args['tab']);
      $legacy = true;
      if (count($tab) == 2) {
         $sub_itemtype = $tab[0];
         $sub_item = new $sub_itemtype;
         $sub_item_display = $sub_item->getSubItemDisplay();
         switch ($sub_item_display) {
            case CommonGLPI::SUBITEM_SHOW_LIST:
               if ($sub_item instanceof CommonDBRelation) {
                  //$item = new $args['itemtype']();
                  $params = $request->getQueryParams() + $args;
                  /*if (isset($args['reset'])) {
                     $params = $args;
                     unset($params['reset']);
                     $this->flash->addMessage('info', __('Search params has been reset'));
                  }*/
                  /*if (isset($args['page'])) {
                     $params['start'] = ($args['page'] - 1) * $_SESSION['glpilist_limit'];
                  }*/

                  $inverse = $item->getType() == $sub_item::$itemtype_1;
                  $link_type  = $sub_item::$itemtype_1;
                  if ($inverse === true) {
                     $link_type  = $sub_item::$itemtype_2;
                     if ($link_type == 'itemtype') {
                        if ($itemtype != null) {
                           $link_type  = $itemtype;
                        } else {
                           $link_type = $item->getType();
                        }
                     }
                  }
                  $link = new $link_type;

                  $search = new Search($link, $params);
                  if (isset($args['page'])) {
                     $search->setPage((int)$args['page']);
                  }
                  $data = $search->getData([
                     'item'      => $item,
                     'sub_item'  => $sub_item
                  ]);

                  //$legacy = false;
                  $params = [];
                  /*$params = $request->getQueryParams() + $args;
                  if (isset($args['reset'])) {
                     $params = $args;
                     unset($params['reset']);
                     $this->flash->addMessage('info', __('Search params has been reset'));
                  }*/

                  return $this->view->render(
                     $response,
                     'list_contents.twig', [
                        'search_data'     => $data,
                        'item'            => $sub_item
                     ]
                  );
               } else {
                  $legacy = true;
               }
               break;
            case CommonGLPI::SUBITEM_SHOW_FORM:
               break;
            case CommonDBTM::SUBITEM_SHOW_SPEC:
               break;
            default:
               $legacy = true;
               break;
         }
      }

      if ($legacy) {
         ob_start();
         CommonGLPI::displayStandardTab(
            $item,
            str_replace('__', '$', $args['tab']),
            $get["withtemplate"],
            $options
         );
         $contents = ob_get_contents();
         ob_end_clean();

         $contents = "<div class='legacy container box'>$contents</div>";
      }
      return $this->view->render(
         $response,
         'ajax.twig',
         ['contents' => $contents]
      );
   })->setName('ajax-tab');

   $app->get('/{itemtype}/edit/{id:\d+}[/tab/{tab}]', function ($request, $response, $args) {
      $item = new $args['itemtype']();
      if (!isset($args['tab'])) {
         $args['tab'] = $item->getType() . '__main';
      }
      $item->getFromdB($args['id']);
      $get = $request->getQueryParams();

      //reload data from session on error
      if (isset($get['item_rand'])) {
         $item->getFromResultset($_SESSION["{$args['itemtype']}_edit_$rand"]);
         unset($_SESSION["{$args['itemtype']}_edit_$rand"]);
      }

      $params = [];

      if (!$item->isTwigCompat() && !isset($get['twig'])) {
         Toolbox::deprecated(
            sprintf(
               '%1$s is not compatible with new templating system!',
               $args['itemtype']
            )
         );
         ob_start();
         $item->display([
            'id'           => $args['id'],
            'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
         ]);
         $params['contents'] = ob_get_contents();
         ob_end_clean();
      } else {
         $params['glpi_form'] = $item->getEditForm();
         if (!isset($params['glpi_form']['action'])) {
            $params['glpi_form']['action'] = $this->router->pathFor(
               'do-edit-asset',
               $args
            );
         }
      }

      $page_title = sprintf(
         __('%1$s - %2$s'),
         __('Edit item'),
         $item->getTypeName(1)
      );
      if ($_SESSION['glpiis_ids_visible']) {
         //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
         $nametype = sprintf(__('%1$s - ID %2$d'), $item->getTypeName(1), $item->fields['id']);

         $page_title = sprintf(
            __('%1$s - %2$s (#%2$d)'),
            __('Edit item'),
            $item->getTypeName(1),
            $item->fields['id']
         );
      }

      return $this->view->render(
         $response,
         'edit_page.twig', [
            'page_title'   => $item->getTypeName(Session::getPluralNumber()),
            'item'         => $item,
            'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0),
            'current_tab'  => $args['tab']
         ] + $params
      );
   })->setName('update-asset');

   $app->post('/{itemtype}/add[/{withtemplate}]', function ($request, $response, $args) {
      $item = new $args['itemtype']();

      $post = $request->getParsedBody();
      $item->check(-1, CREATE, $post);
      $newID = $item->add($post);
      if (!$newID) {
         $rand = mt_rand();
         $_SESSION["{$args['itemtype']}_add_$rand"] = $post;
         $redirect_uri = $this->router->pathFor('add-asset', $args) . "?item_rand=$rand";
      } else {
         /** FIXME: should be handled in commondbtm
         Event::log($newID, "computers", 4, "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));*/

         /*if ($_SESSION['glpibackcreated']) {
            Html::redirect($computer->getLinkURL());
         }*/
         $redirect_uri = $this->router->pathFor('list', ['itemtype' => $args['itemtype']]);
         if ($_SESSION['glpibackcreated']) {
            $redirect_uri = $this->router->pathFor(
               'update-asset', [
                  'itemtype'  => $args['itemtype'],
                  'id'        => $item->fields['id']
               ]
            );
         }
      }

      return $response
         ->withStatus(301)
         ->withHeader('Location', $redirect_uri);
   })->setName('do-add-asset');

   $app->post('/{itemtype}/edit[/{withtemplate}]', function ($request, $response, $args) {
      $item = new $args['itemtype']();

      $post = $request->getParsedBody();
      $item->check($post['id'], UPDATE);
      if (!$item->update($post)) {
         $rand = mt_rand();
         $_SESSION["{$args['itemtype']}_edit_$rand"] = $post;
         $redirect_uri = $this->router->pathFor('add-asset', $args) . "?item_rand=$rand";
      } else {
         /** FIXME: should be handled in commondbtm
         Event::log($_POST["id"], "computers", 4, "inventory",
               //TRANS: %s is the user login
            sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
         */
         $redirect_uri = $this->router->pathFor('list', ['itemtype' => $args['itemtype']]);
         if ($_SESSION['glpibackcreated']) {
            $redirect_uri = $this->router->pathFor(
               'update-asset', [
                  'itemtype'  => $args['itemtype'],
                  'id'        => $item->fields['id']
               ]
            );
         }
      }

      return $response
         ->withStatus(301)
         ->withHeader('Location', $redirect_uri);

   })->setName('do-edit-asset');

   $app->get('/ajax/switch-debug', function ($request, $response, $args) use ($CFG_GLPI) {
      if (Config::canUpdate()) {
         $mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? Session::NORMAL_MODE : Session::DEBUG_MODE);
         $user = new User();
         $user->update(
            [
               'id'        => Session::getLoginUserID(),
               'use_mode'  => $mode
            ]
         );

         $this->flash->addMessage(
            'info',
            $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ?
               __('Debug mode has been enabled!') :
               __('Debug mode has been disabled!')
         );
      }

      $route = $_SESSION['glpi_switch_route'];
      $_SESSION['glpi_switch_route'] = null;

      return $response->withRedirect(
         $this->router->pathFor(
            $route['name'],
            $route['arguments']
         ),
         301
      );
   })->setName('switch-debug');

   $app->get('/dropdowns[/{dropdown}]', function ($request, $response, $args) {
      $optgroup = Dropdown::getStandardDropdownItemTypes();

      $glpi_form = [
         'header'       => false,
         'submit'       => false,
         'elements'     => [
            'dropdowns' => [
               'type'         => 'select',
               'name'         => 'dropdowns',
               'autofocus'    => true,
               'values'       => $optgroup,
               'listicon'     => false,
               'addicon'      => false,
               'label'        => _n('Dropdown', 'Dropdowns', 2),
               'noajax'       => true,
               'value'        => isset($args['dropdown']) ? $args['dropdown'] : null,
               'change_func'  => 'onDdListChange',
               'empty_value'  => true
            ]
         ]
      ];

      $tpl = 'dropdowns.twig';
      $params = [
         'page_title'   => __('Dropdowns'),
         'glpi_form'    => $glpi_form
      ];
      if (isset($args['dropdown'])) {
         $tpl = 'dropdown.twig';
         $item = new $args['dropdown']();
         $params['page_title'] = $item->getTypeName(Session::getPluralNumber());
         ob_start();
         Search::show($item->getType());
         $params['contents'] = ob_get_contents();
         ob_end_clean();
      }

      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         'CommonDropdown'
      );

      return $this->view->render(
         $response,
         $tpl,
         $params
      );
   })->setName('dropdowns');

   $app->post('/ajax/dropdown/getvalue/{itemtype:.+}', function ($request, $response, $args) {
      $post = $request->getParsedBody();
      if (!isset($post['itemtype'])) {
         $post['itemtype'] = $args['itemtype'];
      }
      $values = Dropdown::getDropdownValue($post, false);
      return $response->withJson($values);
   })->setName('dropdown-getvalue');

   $app->post('/ajax/display-preference/{itemtype:.+}', function($request, $response, $args) {
      $post = $request->getParsedBody();
      $setupdisplay = new DisplayPreference();

      //legacy
      ob_start();
      $setupdisplay->display([
         'displaytype'  => $args['itemtype'],
         '_target'      => $this->router->pathFor('do-display-preference', ['itemtype' => $args['itemtype']])
      ]);
      $contents = ob_get_contents();
      ob_end_clean();
      $contents = "<div class='legacy'>$contents</div>";
      return $this->view->render(
         $response,
         'ajax.twig',
         ['contents' => $contents]
      );
   })->setName('display-preference');

   $app->post('/ajax/do-display-preference/{itemtype:.+}', function($request, $response, $args) {
      $post = $request->getParsedBody();
      $setupdisplay = new DisplayPreference();

      if (isset($post["activate"])) {
         $setupdisplay->activatePerso($post);

      } else if (isset($post["disable"])) {
         if ($post['users_id'] == Session::getLoginUserID()) {
            $setupdisplay->deleteByCriteria([
               'users_id' => $post['users_id'],
               'itemtype' => $post['itemtype']]);
         }
      } else if (isset($post["add"])) {
         $setupdisplay->add($post);

      } else if (isset($post["purge"]) || isset($post["purge_x"])) {
         $setupdisplay->delete($post, 1);

      } else if (isset($post["up"]) || isset($post["up_x"])) {
         $setupdisplay->orderItem($post, 'up');

      } else if (isset($post["down"]) || isset($post["down_x"])) {
         $setupdisplay->orderItem($post, 'down');
      }

      // Datas may come from GET or POST : use REQUEST
      /*if (isset($_REQUEST["itemtype"])) {
         $setupdisplay->display(['displaytype' => $_REQUEST['itemtype']]);
      }*/

      return $response->withRedirect(
         $this->router->pathFor(
            'list',
            ['itemtype' => $args['itemtype']]
         ),
         301
      );
   })->setName('do-display-preference');

   $app->get('/devices', function ($request, $response, $args) {
      $optgroup = Dropdown::getDeviceItemTypes();

      $glpi_form = [
         'header'       => false,
         'submit'       => false,
         'elements'     => [
            'devices' => [
               'type'         => 'select',
               'name'         => 'devices',
               'autofocus'    => true,
               'values'       => $optgroup,
               'listicon'     => false,
               'addicon'      => false,
               'label'        => _n('Device', 'Devices', 2),
               'noajax'       => true,
               'value'        => isset($args['device']) ? $args['device'] : null,
               'change_func'  => 'onDdListChange',
               'empty_value'  => true
            ]
         ]
      ];

      $tpl = 'devices.twig';
      $params = [
         'page_title'   => __('Devices'),
         'glpi_form'    => $glpi_form
      ];

      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         'CommonDevice'
      );

      return $this->view->render(
         $response,
         $tpl,
         $params
      );
   })->setName('devices');

   $app->get('/stats[/{mode:global|trackink|location}[/{itemtype}]]', function ($request, $response, $args) {
      /*$central = new Central();

      ob_start();
      $central->display();
      $contents = ob_get_contents();
      ob_end_clean();*/

      $glpi_form = [
         'header'       => false,
         'submit'       => false,
         'elements'     => [
            'stats' => [
               'type'         => 'select',
               'name'         => 'stats',
               'autofocus'    => true,
               'values'       => Stat::getStatsList(),
               'listicon'     => false,
               'addicon'      => false,
               'label'        => __('Statistics to display'),
               'noajax'       => true,
               'value'        => /*isset($args['device']) ? $args['device'] : */null,
               'change_func'  => 'onDdListChange',
               'empty_value'  => true
            ]
         ]
      ];

      return $this->view->render(
         $response,
         'stats.twig', [
            'glpi_form' => $glpi_form
            /*'contents' => $contents*/
         ]
      );
   })->setName('stats');

   $app->get('/dictionnaries', function ($request, $response, $args) {
      $dictionnaries = RuleCollection::getDictionnaries();
      $params = [
         'page_title'      => __('Dictionaries'),
         'dictionnaries'   => $dictionnaries
      ];

      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         'RuleCollection'
      );

      return $this->view->render(
         $response,
         'dictionnaries.twig',
         $params
      );
   })->setName('dictionnaries');

   $app->get('/dictionnary/{collection}', function ($request, $response, $args) {
      $class = "RuleDictionnary{$args['collection']}Collection";
      if (!class_exists($class)) {
         $this->flash->addMessage(
            'error',
            str_replace(
               '%classname',
               $class,
               __('Class %classname does not exists!')
            )
         );
         return $response->withRedirect(
            $this->router->pathFor('dictionnaries'),
            301
         );
      }
      $collection = new $class();
      /*$dictionnaries = RuleCollection::getDictionnaries();
      $params = [
         'page_title'      => __('Dictionaries'),
         'dictionnaries'   => $dictionnaries
      ];*/

      $this->view->getEnvironment()->addGlobal(
         "current_itemtype",
         'RuleCollection'
      );

      return $this->view->render(
         $response,
         'dictionnary.twig',
         []
      );
   })->setName('dictionnary');

   $app->get('/cron[/{task}]', function ($request, $response, $args) {
      //FIXME: not finished yet!
      $image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000".
                        "018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
      $response->write($image);
      return $response
         ->withHeader('Content-Type', 'image/gif')
         ->withHeader('Cache-Control', 'no-cache,no-store')
         ->withHeader('Pragma', 'no-cache')
         ->withHeader('Connection', 'close');
   })->setName('cron');

   $app->get('/user/{itemtype:Problem|Change|Ticket}/{id:\d+}', function ($request, $response, $args) {
      $object = new $args['itemtype'];

      $options = [
         'criteria'  => [
            [ //users_id_assign
               'field'        => 5,
               'searchtype'   => 'equals',
               'value'        => (int)$args['id'],
               'link'         => 'AND'
            ], [ //status
               'field'        => 12,
               'searchtype'   => 'equals',
               'value'        => 'notold',
               'link'         => 'AND'
            ]
         ],
         'reset'     => 'reset'
      ];
      $url = $object->getSearchURL()."?".Toolbox::append_params($options, '&amp;');

      return $response->withJson([
         'count'        => $object->countActiveObjectsForTech((int)$args['id']),
         'search_url'   => $url
      ]);
   })->setName('user_itilobjects_active');

   RunTracy\Helpers\Profiler\Profiler::finish('Register routes');

   // Run app
   RunTracy\Helpers\Profiler\Profiler::start('runApp');
   $app->run();
   RunTracy\Helpers\Profiler\Profiler::finish('runApp');

   /*$_SESSION["glpicookietest"] = 'testcookie';

   // For compatibility reason
   if (isset($_GET["noCAS"])) {
      $_GET["noAUTO"] = $_GET["noCAS"];
   }

   if (!isset($_GET["noAUTO"])) {
      Auth::redirectIfAuthenticated();
   }
   Auth::checkAlternateAuthSystems(true, isset($_GET["redirect"])?$_GET["redirect"]:"");*/

   // Send UTF8 Headers
   /*header("Content-Type: text/html; charset=UTF-8");

   // Start the page
   echo "<!DOCTYPE html>\n";
   echo "<html lang=\"{$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]}\" class='loginpage'>";
   echo '<head><title>'.__('GLPI - Authentication').'</title>'."\n";
   echo '<meta charset="utf-8"/>'."\n";
   echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
   echo '<link rel="shortcut icon" type="images/x-icon" href="'.$CFG_GLPI["root_doc"].
          '/pics/favicon.ico" />';

   // auto desktop / mobile viewport
   echo "<meta name='viewport' content='width=device-width, initial-scale=1'/>";

   // Appel CSS
   echo '<link rel="stylesheet" href="'.$CFG_GLPI["root_doc"].'/css/styles.css" type="text/css" '.
         'media="screen" />';
   // CSS theme link
   echo Html::css("css/palettes/".$CFG_GLPI["palette"].".css");

   echo "</head>";

   echo "<body>";
   echo "<div id='firstboxlogin'>";
   echo "<div id='logo_login'></div>";
   echo "<div id='text-login'>";
   echo nl2br(Toolbox::unclean_html_cross_side_scripting_deep($CFG_GLPI['text_login']));
   echo "</div>";

   echo "<div id='boxlogin'>";
   echo "<form action='".$CFG_GLPI["root_doc"]."/front/login.php' method='post'>";

   $_SESSION['namfield'] = $namfield = uniqid('fielda');
   $_SESSION['pwdfield'] = $pwdfield = uniqid('fieldb');
   $_SESSION['rmbfield'] = $rmbfield = uniqid('fieldc');

   // Other CAS
   if (isset($_GET["noAUTO"])) {
      echo "<input type='hidden' name='noAUTO' value='1' />";
   }
   // redirect to ticket
   if (isset($_GET["redirect"])) {
      Toolbox::manageRedirect($_GET["redirect"]);
      echo '<input type="hidden" name="redirect" value="'.$_GET['redirect'].'"/>';
   }
   echo '<p class="login_input">
         <input type="text" name="'.$namfield.'" id="login_name" required="required"
                placeholder="'.__('Login').'" autofocus="autofocus" />
         <span class="login_img"></span>
         </p>';
   echo '<p class="login_input">
         <input type="password" name="'.$pwdfield.'" id="login_password" required="required"
                placeholder="'.__('Password').'"  />
         <span class="login_img"></span>
         </p>';
   if ($CFG_GLPI["login_remember_time"]) {
      echo '<p class="login_input">
            <label for="login_remember">
                   <input type="checkbox" name="'.$rmbfield.'" id="login_remember"
                   '.($CFG_GLPI['login_remember_default']?'checked="checked"':'').' />
            '.__('Remember me').'</label>
            </p>';
   }
   echo '<p class="login_input">
         <input type="submit" name="submit" value="'._sx('button', 'Post').'" class="submit" />
         </p>';

   if ($CFG_GLPI["notifications_mailing"]
       && countElementsInTable('glpi_notifications',
                               "`itemtype`='User'
                                AND `event`='passwordforget'
                                AND `is_active`=1")) {
      echo '<a id="forget" href="front/lostpassword.php?lostpassword=1">'.
             __('Forgotten password?').'</a>';
   }
   Html::closeForm();

   echo "<script type='text/javascript' >\n";
   echo "document.getElementById('login_name').focus();";
   echo "</script>";

   echo "</div>";  // end login box


   echo "<div class='error'>";
   echo "<noscript><p>";
   echo __('You must activate the JavaScript function of your browser');
   echo "</p></noscript>";

   if (isset($_GET['error']) && isset($_GET['redirect'])) {
      switch ($_GET['error']) {
         case 1 : // cookie error
            echo __('You must accept cookies to reach this application');
            break;

         case 2 : // GLPI_SESSION_DIR not writable
            echo __('Checking write permissions for session files');
            break;

         case 3 :
            echo __('Invalid use of session ID');
            break;
      }
   }
   echo "</div>";

   // Display FAQ is enable
   if ($CFG_GLPI["use_public_faq"]) {
      echo '<div id="box-faq">'.
            '<a href="front/helpdesk.faq.php">[ '.__('Access to the Frequently Asked Questions').' ]';
      echo '</a></div>';
   }

   echo "<div id='display-login'>";
   Plugin::doHook('display_login');
   echo "</div>";


   echo "</div>"; // end contenu login

   if (GLPI_DEMO_MODE) {
      echo "<div class='center'>";
      Event::getCountLogin();
      echo "</div>";
   }
   echo "<div id='footer-login' class='home'>" . Html::getCopyrightMessage(false) . "</div>";*/

}
