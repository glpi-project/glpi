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

//Load GLPI constants
define('GLPI_ROOT', __DIR__ . '/..');
include (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/define.php");

define('DO_NOT_CHECK_HTTP_REFERER', 1);

// If config_db doesn't exist -> start installation
if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   include_once (GLPI_ROOT . "/inc/autoload.function.php");
   Html::redirect("install/install.php");
   die();
} else {
   $TRY_OLD_CONFIG_FIRST = true;
   include (GLPI_ROOT . "/inc/includes.php");

   // Create app
   $app_settings = [
      'settings' => [
         'displayErrorDetails'               => true,
         'determineRouteBeforeAppMiddleware' => true
      ],
      'logger' => [
         'name'   => 'GLPI',
         'level'  => Monolog\Logger::DEBUG,
         'path'   => GLPI_LOG_DIR . '/php-errors.log',
      ],
   ];
   $app = new \Slim\App($app_settings);

   // Get container
   $container = $app->getContainer();

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
      $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
      $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
      $view->addExtension(new \Twig_Extensions_Extension_I18n());
      $view->addExtension(new \Twig_Extension_Debug());
      include_once __DIR__ . '/../twig_extensions/Reflection.php';
      $view->addExtension(new \Twig\Glpi\Extensions\Reflection());
      include_once __DIR__ . '/../twig_extensions/Locales.php';
      $view->addExtension(new \Twig\Glpi\Extensions\Locales());
      include_once __DIR__ . '/../twig_extensions/GlpiDebug.php';
      $view->addExtension(new \Twig\Glpi\Extensions\GlpiDebug());

      return $view;
   };

   /**
    * Switch middleware (to get UI reloaded after switching
    * debug mode, language, ...
    */
   $app->add(function ($request, $response, $next) {
      $get = $request->getQueryParams();

      if (isset($get['switch'])) {
         $switch_route = $get['switch'];
         $route = $request->getAttribute('route');
         $uri = $request->getUri();
         $route_name = $route->getName();
         $arguments = $route->getArguments();

         $_SESSION['glpi_switch_route'] = [
            'name'      => $route_name,
            'arguments' => $arguments
         ];

         return $response->withRedirect($this->router->pathFor($switch_route), 301);
      }
      return $next($request, $response);
   });

   // Render Twig template in route
   $app->get('/', function ($request, $response, $args) {
      //todo do some checks and redirect the the right URL
      return $response->withRedirect($this->router->pathFor('login'), 301);
   })->setName('slash');

   // Render Twig template in route
   $app->get('/login', function ($request, $response, $args) use ($CFG_GLPI) {
      //if user is logged in, redirect to /central

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

      return $this->view->render($response, 'login.twig', ['glpi_form' => $glpi_form]);
   })->setName('login');

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

   $app->get('/{itemtype}/list', function ($request, $response, $args) {
      $item = new $args['itemtype']();

      ob_start();
      Search::show($item->getType());
      $contents = ob_get_contents();
      ob_end_clean();
      return $this->view->render(
         $response,
         'legacy.twig', [
            'page_title'   => $item->getTypeName(Session::getPluralNumber()),
            'item'         => $item,
            'contents'     => $contents
         ]
      );
   })->setName('asset');

   $app->get('/{itemtype}/add[/{withtemplate}]', function ($request, $response, $args) {
      $item = new $args['itemtype']();
      $get = $request->getQueryParams();

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
         $item->display([
            'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
         ]);
         $params['contents'] = ob_get_contents();
         ob_end_clean();
      } else {
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

   $app->get('/{itemtype}/edit/{id:\d+}', function ($request, $response, $args) {
      $item = new $args['itemtype']();
      $item->getFromdB($args['id']);
      $get = $request->getQueryParams();

      //reload data from session on error
      if (isset($get['item_rand'])) {
         $item->getFromResultset($_SESSION["{$args['itemtype']}_edit_$rand"]);
         unset($_SESSION["{$args['itemtype']}_edit_$rand"]);
      }

      $params = [];

      if (!$item->isTwigCompat()) {
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
            'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
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
         $redirect_uri = $this->router->pathFor('asset', ['itemtype' => $args['itemtype']]);
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
         $redirect_uri = $this->router->pathFor('asset', ['itemtype' => $args['itemtype']]);
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
         Session::addMessageAfterRedirect(
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
         'pure_form'    => 'aligned',
         'header_title' => false,
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

      return $this->view->render(
         $response,
         $tpl,
         $params
      );
   })->setName('dropdowns');

   $app->post('/ajax/dropdown/getvalue', function ($request, $response) {
      $post = $request->getParsedBody();
      $values = Dropdown::getDropdownValue($post, false);
      return $response->withJson($values);
   })->setName('dropdown-getvalue');

   // Run app
   $app->run();

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
// call cron
if (!GLPI_DEMO_MODE) {
   CronTask::callCronForce();
}

/*echo "</body></html>";*/
