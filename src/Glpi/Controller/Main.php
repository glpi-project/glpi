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

namespace Glpi\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use Auth;
use Dropdown;
use Search;
use Session;

class Main extends AbstractController implements ControllerInterface
{
   /**
    * path: '/'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
    * @Glpi\Annotation\Route(name="slash", pattern="/")
    */
    public function slash(Request $request, Response $response, array $args)
    {
       //TODO: do some checks and redirect the the right URL
        $redirect_uri = $this->router->pathFor('login');
        if (Session::getLoginUserID()) {
            $redirect_uri = $this->router->pathFor('central');
        }
        return $response->withRedirect($redirect_uri, 302);
    }

   /**
    * path: '/login'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
    * @Glpi\Annotation\Route(name="login", pattern="/login")
    */
    public function login(Request $request, Response $response, array $args)
    {
       //if user is logged in, redirect to /central
        if (Session::getLoginUserID()) {
            $redirect_uri = $this->router->pathFor('central');
            return $response->withRedirect($redirect_uri, 302);
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

        $auth_methods = Auth::getLoginAuthMethods();
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

        if ($this->configParams['login_remember_time']) {
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
        if ($this->configParams["notifications_mailing"]) {
            $active = \countElementsInTable(
                'glpi_notifications',
                [
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
        if ($this->configParams["use_public_faq"]) {
            $show_faq = true;
        }

        return $this->view->render(
            $response,
            'login.twig',
            [
                'glpi_form'       => $glpi_form,
                'show_lostpass'   => $show_lostpass,
                'show_faq'        => $show_faq
            ]
        );
    }

   /**
    * path: '/login' (POST)
    *
    * @param Request  $request  Request
    * @param Response $response Response
    *
    * @return void
    *
    * @Glpi\Annotation\Route(name="do-login", pattern="/login", method="POST")
    */
    public function doLogin(Request $request, Response $response)
    {
        $post = $request->getParsedBody();
        $post = array_map('stripslashes', $post);

       //There is certainly a beter way to achieve that test (JS?)
       /*if (!isset($_SESSION["glpicookietest"]) || ($_SESSION["glpicookietest"] != 'testcookie')) {
         if (!is_writable(GLPI_SESSION_DIR)) {
            Html::redirect($this->configParams['root_doc'] . "/index.php?error=2");
         } else {
            Html::redirect($this->configParams['root_doc'] . "/index.php?error=1");
         }
       }*/

        $login = $post['login_name'];
        $password = $post['login_password'];
        $login_auth = $post['auth'];
        $remember = isset($post['login_remember']) && $this->configParams["login_remember_time"];
        $noauto = isset($_REQUEST["noAUTO"]) ? $_REQUEST["noAUTO"] : false;
        $redirect = isset($_SESSION['glpi_redirect']) ? $_SESSION['glpi_redirect'] : $this->router->pathFor('central');

        if (empty($login) || empty($password) || empty($login_auth)) {
            $this->flash->addMessage(
                'error',
                __('Missing authentication information.')
            );
            return $response
             ->withStatus(302)
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

        $auth = new \Auth();

       // now we can continue with the process...
        if ($auth->login($login, $password, $noauto, $remember, $login_auth)) {
            if (isset($_SESSION['glpi_redirect'])) {
                unset($_SESSION['glpi_redirect']);
            }

            return $response
                ->withStatus(302)
                ->withHeader('Location', $redirect);
        } else {
            foreach ($auth->getErrors() as $error) {
                $this->flash->addMessage(
                    'error',
                    $error
                );
            }
            return $response
                ->withStatus(302)
                ->withHeader('Location', $this->router->pathFor('login'));
           // we have done at least a good login? No, we exit.
           /*Html::nullHeader("Login", $this->configParams["root_doc"] . '/index.php');
           echo '<div class="center b">' . $auth->getErr() . '<br><br>';
           // Logout whit noAUto to manage auto_login with errors
           echo '<a href="' . $this->configParams["root_doc"] . '/front/logout.php?noAUTO=1'.
               str_replace("?", "&", $REDIRECT).'">' .__('Log in again') . '</a></div>';
           Html::nullFooter();
           exit();*/
        }
    }

   /**
    * path: '/logout'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    *
    * @return void
     *
     * @Glpi\Annotation\Route(name="logout", pattern="/logout")
    */
    public function logout(Request $request, Response $response)
    {
       /*if (!isset($_SESSION["noAUTO"])
         && isset($_SESSION["glpiauthtype"])
         && $_SESSION["glpiauthtype"] == Auth::CAS
         && \Toolbox::canUseCAS()) {

         phpCAS::client(CAS_VERSION_2_0, $this->configParams["cas_host"], intval($this->configParams["cas_port"]),
                        $this->configParams["cas_uri"], false);
         phpCAS::setServerLogoutURL(strval($this->configParams["cas_logout"]));
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

        Session::destroy();

       //Remove cookie to allow new login
        $cookie_name = session_name() . '_rememberme';
        $cookie_path = ini_get('session.cookie_path');

        if (isset($_COOKIE[$cookie_name])) {
            setcookie($cookie_name, '', time() - 3600, $cookie_path);
            unset($_COOKIE[$cookie_name]);
        }

       // Redirect to the login-page
       //Html::redirect($this->configParams["root_doc"]."/index.php".$toADD);
        return $response
         ->withStatus(302)
         ->withHeader('Location', $this->router->pathFor('login'));
    }

   /**
    * path: '/central'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    *
    * @return void
     *
     * @Glpi\Annotation\Route(name="central", pattern="/central")
    */
    public function central(Request $request, Response $response)
    {
        $central = new \Central();

        ob_start();
        $central->display();
        $contents = ob_get_contents();
        ob_end_clean();

        return $this->view->render(
            $response,
            'central.twig',
            ['contents' => $contents]
        );
    }

   /**
    * path: '/ajax/switch-debug'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="switch-debug", pattern="/ajax/switch-debug")
    */
    public function switchDebug(Request $request, Response $response)
    {
        if (\Config::canUpdate()) {
            $mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? Session::NORMAL_MODE : Session::DEBUG_MODE);
            $user = new \User();
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
            302
        );
    }

   /**
    * path: '/lost-password'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="lost-password", pattern="/lost-password")
    */
    public function lostPassword(Request $request, Response $response, array $args)
    {
        $go = true;
        if ($this->configParams["notifications_mailing"]) {
            $active = countElementsInTable(
                'glpi_notifications',
                [
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
              ->withStatus(302)
              ->withHeader('Location', $this->router->pathFor('login'));
        }

      //TODO: display password recovering page
    }

   /**
    * path: '/knowbase'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="knowbase", pattern="/knowbase")
    */
    public function knowbase(Request $request, Response $response)
    {
        $get = $request->getQueryParams();

        // Clean for search
        $_GET = \Toolbox::stripslashes_deep($_GET);

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

        $kb = new \Knowbase();
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
            'legacy.twig',
            [
                'page_title'      => \KnowbaseItem::getTypeName(Session::getPluralNumber()),
                'contents'        => $contents,
                'item'            => $kb
            ]
        );
    }

   /**
    * path: '/dropdowns'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="dropdowns", pattern="/dropdowns")
    */
    public function dropdowns(Request $request, Response $response, array $args)
    {
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

        $this->view->getEnvironment()->addGlobal(
            "current_itemtype",
            'CommonDropdown'
        );

        return $this->view->render(
            $response,
            $tpl,
            $params
        );
    }

   /**
    * path: '/dropdown/getvalue'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="dropdown-getvalue", pattern="/dropdown/getvalue/{itemtype:.+}", method="POST")
    */
    public function getDropdownValue(Request $request, Response $response, array $args)
    {
        $post = $request->getParsedBody();
        if (!isset($post['itemtype'])) {
            $post['itemtype'] = $args['itemtype'];
        }
        $values = Dropdown::getDropdownValue($post, false);
        return $response->withJson($values);
    }

   /**
    * path: '/display-preference'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="display-preference", pattern="/display-preference/{itemtype:.+}", method="POST")
    */
    public function displayPreference(Request $request, Response $response, array $args)
    {
        $post = $request->getParsedBody();
        $setupdisplay = new \DisplayPreference();

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
    }

   /**
    * path: '/do-display-preference'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="do-display-preference", pattern="/do-display-preference/{itemtype:.+}", method="POST")
    */
    public function doDisplayPreference(Request $request, Response $response, array $args)
    {
        $post = $request->getParsedBody();
        $setupdisplay = new \DisplayPreference();

        if (isset($post["activate"])) {
            $setupdisplay->activatePerso($post);
        } elseif (isset($post["disable"])) {
            if ($post['users_id'] == Session::getLoginUserID()) {
                $setupdisplay->deleteByCriteria([
                    'users_id' => $post['users_id'],
                    'itemtype' => $post['itemtype']
                ]);
            }
        } elseif (isset($post["add"])) {
            $setupdisplay->add($post);
        } elseif (isset($post["purge"]) || isset($post["purge_x"])) {
            $setupdisplay->delete($post, 1);
        } elseif (isset($post["up"]) || isset($post["up_x"])) {
            $setupdisplay->orderItem($post, 'up');
        } elseif (isset($post["down"]) || isset($post["down_x"])) {
            $setupdisplay->orderItem($post, 'down');
        }

      // Data may come from GET or POST : use REQUEST
      /*if (isset($_REQUEST["itemtype"])) {
         $setupdisplay->display(['displaytype' => $_REQUEST['itemtype']]);
      }*/

        return $response->withRedirect(
            $this->router->pathFor(
                'list',
                ['itemtype' => $args['itemtype']]
            ),
            302
        );
    }

   /**
    * path: '/stats'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="stats", pattern="/stats[/{mode:global|trackink|location}[/{itemtype}]]")
    */
    public function stats(Request $request, Response $response, array $args)
    {
        $glpi_form = [
         'header'       => false,
         'submit'       => false,
         'elements'     => [
            'stats' => [
               'type'         => 'select',
               'name'         => 'stats',
               'autofocus'    => true,
               'values'       => \Stat::getStatsList(),
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
            'stats.twig',
            [
            'glpi_form' => $glpi_form
            ]
        );
    }

   /**
    * path: '/dictionnaries'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="dictionnaries", pattern="/dictionnaries")
    */
    public function dictionaries(Request $request, Response $response, array $args)
    {
        $dictionnaries = \RuleCollection::getDictionnaries();
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
    }

   /**
    * path: '/dictionnary/collection'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="dictionnary", pattern="/dictionnary/{collection}")
    */
    public function dictionnary(Request $request, Response $response, array $args)
    {
        $class = "\RuleDictionnary{$args['collection']}Collection";
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
                302
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
    }

    /**
    * path: '/cron'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
     *
     * @Glpi\Annotation\Route(name="cron", pattern="/cron[/{task}]")
    *
    */
    public function cron(Request $request, Response $response, array $args)
    {
        //FIXME: not finished yet!
        $image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000".
                        "018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
        $response->write($image);
        return $response
            ->withHeader('Content-Type', 'image/gif')
            ->withHeader('Cache-Control', 'no-cache,no-store')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Connection', 'close');
    }

    /**
     * path: '/asset/name'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="asset", pattern="/assets/css/{file}")
     */
    public function asset(Request $request, Response $response, array $args)
    {
        $get = $request->getQueryParams();
        $css = \Html::compileScss(array_merge($args, $get));

        $response = $response->withHeader('Content-type', 'text/css');
        $body = $response->getBody();
        $body->write($css);
        return $response;
    }

    /**
     * path: '/ajax/messages'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="messages", pattern="/ajax/messages")
     */
    public function messages(Request $request, Response $response)
    {
        $this->view->render(
            $response,
            'flash_messages.twig'
        );
        return $response;
    }

    /**
     * path: '/ajax/map'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="map-results", pattern="/ajax/map")
     */
    public function mapResults(Request $request, Response $response, array $args)
    {
        $post = $request->getParsedBody();

        $result = [];
        if (!isset($post['itemtype']) || !isset($post['params'])) {
            $response = $response->withStatus(500);
            $result = [
                'success'   => false,
                'message'   => __('Required argument missing!')
            ];
        } else {
            $itemtype = $post['itemtype'];
            $params   = $post['params'];

            $params['criteria'][] = [
                'link'         => 'AND NOT',
                'field'        => ($itemtype == 'Location') ? 21 : 998,
                'searchtype'   => 'contains',
                'value'        => 'NULL'
            ];
            $params['criteria'][] = [
                'link'         => 'AND NOT',
                'field'        => ($itemtype == 'Location') ? 20 : 999,
                'searchtype'   => 'contains',
                'value'        => 'NULL'
            ];

            $data = Search::prepareDatasForSearch($itemtype, $params);
            Search::constructSQL($data);
            Search::constructData($data);

            if ($itemtype == 'Location') {
                $lat_field = $itemtype . '_21';
                $lng_field = $itemtype . '_20';
                $name_field = $itemtype . '_1';
            } else {
                $lat_field = $itemtype . '_998';
                $lng_field = $itemtype . '_999';
                $name_field = $itemtype . '_3';
            }
            if ($itemtype == 'Ticket') {
                //duplicate search options... again!
                $name_field = $itemtype . '_83';
            }

            $rows = $data['data']['rows'];
            $points = [];
            foreach ($rows as $row) {
                $idx = $row['raw']["ITEM_$lat_field"] . ',' . $row['raw']["ITEM_$lng_field"];
                if (isset($points[$idx])) {
                    $points[$idx]['count'] += 1;
                } else {
                    $points[$idx] = [
                        'lat'    => $row['raw']["ITEM_$lat_field"],
                        'lng'    => $row['raw']["ITEM_$lng_field"],
                        'title'  => $row['raw']["ITEM_$name_field"],
                        'loc_id' => $row['raw']['loc_id'],
                        'count'  => 1
                    ];
                }

                if ($itemtype == 'AllAssets') {
                    $curtype = $row['TYPE'];
                    if (isset($points[$idx]['types'][$curtype])) {
                        $points[$idx]['types'][$curtype]['count']++;
                        $points[$idx]['types'][$curtype]['name'] = strtolower(
                            $curtype::getTypeName(Session::getPluralNumber())
                        );
                    } else {
                        $points[$idx]['types'][$curtype] = [
                        'name'   => strtolower($curtype::getTypeName(1)),
                        'count'  => 1
                        ];
                    }
                }
            }
            $result['points'] = $points;
        }

        return $response->withJson($result);
    }

    /**
     * path: '/savedsearch/show'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="show-saved-searches", pattern="/savedsearch/show")
     */
    public function showSavedSearches(Request $request, Response $response, array $args)
    {
        $savedsearch = new \SavedSearch();
        $searches = $savedsearch->getMine();

        $this->view->render(
            $response,
            'savedsearches.twig',
            [
                'page_title'  => \SavedSearch::getTypeName(Session::getPluralNumber()),
                'item'        => $savedsearch,
                'searches'    => $searches,
                'is_xhr'      => $request->isXhr()
            ]
        );
        return $response;
    }

    /**
     * path: '/savedsearch/load/id'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="load-saved-search", pattern="/ajax/savedsearch/load/{id:\d+}")
     */
    public function loadSavedSearch(Request $request, Response $response, array $args)
    {
        $savedsearch = new \SavedSearch();
        $savedsearch->check($args['id'], READ);

        if ($params = $savedsearch->getParameters($args['id'])) {
            $redirect_uri = $this->router->pathFor('list', ['itemtype' => $savedsearch->fields['itemtype']]);
            $redirect_uri .= "?". \Toolbox::append_params($params);

            return $response->withRedirect($redirect_uri, 302);
        }

        $this->flash->addMessage('error', __('Unable to load requested saved search!'));
        return $response->withRedirect($this->router->getBasePath(), 500);
    }

    /**
     * path: '/savedsearch/toggle-default/id'
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="toggle-default-saved-search", pattern="/ajax/savedsearch/toggle-default/{id:\d+}")
     */
    public function toggleDefaultSavedSearch(Request $request, Response $response, array $args)
    {
        $savedsearch = new \SavedSearch();
        $savedsearch->check($args['id'], READ);

        if ($savedsearch->isDefault()) {
            $savedsearch->unmarkDefault();
            $this->flash->addMessage('info', __('Saved search is no longer the default'));
        } else {
            $savedsearch->markDefault();
            $this->flash->addMessage('info', __('Saved search has been set as default'));
        }

        if ($request->isXhr()) {
            return $response->withJson(['success' => true]);
        } else {
            return $response->withRedirect($request->getUri());
        }
    }

    /**
     * path: '/savedsearch/reorder' (POST)
     *
     * @param Request  $request  Request
     * @param Response $response Response
     * @param array    $args     URL arguments
     *
     * @return void
     *
     * @Glpi\Annotation\Route(name="reorder-saved-searches", pattern="/savedsearch/reorder", method="POST")
     */
    public function reorderSavedSearches(Request $request, Response $response, array $args)
    {
        $post = $request->getParsedBody();
        $savedsearch = new \SavedSearch();

        $savedsearch->saveOrder($post['ids']);

        return $response->withJson(['res' => true]);
    }
}
