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
use Search;
use Session;
use Dropdown;
use Toolbox;

class Asset extends AbstractController implements ControllerInterface
{
   /**
    * path: '/itemtype/list'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
    * @Glpi\Annotation\Route(name="list", pattern="/{itemtype}/list[/page/{page:\d+}[/{reset:reset}]]")
    */
    public function list(Request $request, Response $response, array $args)
    {
        global $IS_TWIG;
        $IS_TWIG = true;
        $itemtype = $args['itemtype'];
        $item = $itemtype == 'AllAssets' ? $itemtype : new $itemtype;
        $params = $request->getQueryParams() + $args;
        if (isset($args['reset'])) {
            $params = $args;
            unset($params['reset']);
            $this->flash->addMessage('info', __('Search params has been reset'));
        }
        if (isset($args['page'])) {
            $params['start'] = ($args['page'] - 1) * $_SESSION['glpilist_limit'];
        }
        $search = new \Search($item, $params);
        if (isset($args['page'])) {
            $search->setPage((int)$args['page']);
        }
        $data = $search->getData();

        //legacy
        $get = $request->getQueryParams();
        $params = Search::manageParams($itemtype, $get);

        ob_start();
        Search::showGenericSearch($itemtype, $params);
        $search_form = ob_get_contents();
        ob_end_clean();
        $search_form = preg_replace(
            '/<\/?form[^>]*?>/',
            '',
            $search_form
        );

        $page_title = 'AllAssets' === $itemtype ?
           __('Global') :
           $itemtype::getTypeName(Session::getPluralNumber());
        $route_params = [
            'page_title'      => $page_title,
            'itemtype'        => $itemtype,
            'search_data'     => $data,
            'search_form'     => $search_form,
            'search_options'  => Search::getCleanedOptions($itemtype),
            'search_params'   => json_encode($params),
            'params'          => $params
        ];
        if ($item !== $itemtype) {
            $route_params['item'] = $item;
        }

        $dd_types = \Dropdown::getStandardDropdownItemTypes();

        if ($item instanceof \CommonDevice) {
            $dev_types = \Dropdown::getDeviceItemTypes();
            $head_dd = [
                'type'         => 'select',
                'name'         => 'devices',
                'autofocus'    => true,
                'values'       => $dev_types,
                'listicon'     => false,
                'addicon'      => false,
                'noajax'       => true,
                'value'        => $item->getType(),
                'change_func'  => 'onDdListChange',
                'empty_value'  => true
            ];
            $route_params['head_dd'] = $head_dd;
        } elseif ($item instanceof \CommonDropdown) {
            $head_dd = [
                'name'         => 'dropdowns',
                'autofocus'    => true,
                'values'       => $dd_types,
                'listicon'     => false,
                'addicon'      => false,
                'noajax'       => true,
                'value'        => $item->getType(),
                'change_func'  => 'onDdListChange',
                'empty_value'  => true
            ];
            $route_params['head_dd'] = $head_dd;
        }

        return $this->view->render(
            $response,
            'list.twig',
            $route_params
        );
    }

   /**
    * path: '/itemtype/add'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
     *
     * @Glpi\Annotation\Route(name="add-asset", pattern="/{itemtype}/add[/{withtemplate}]")
    */
    public function add(Request $request, Response $response, array $args)
    {
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
        if ($args['itemtype'] == \Config::getType()) {
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
            if ($item instanceof \CommonITILObject) {
                $tpl = 'itil_add_page';
            }
            $form = $item->getAddForm();
            if (!isset($form['action'])) {
                $form['action'] = $this->router->pathFor(
                    'do-add-asset',
                    $args
                );
            }
            $params['glpi_form'] = $form;
        }

        return $this->view->render(
            $response,
            $tpl . '.twig',
            [
                'page_title'   => sprintf(
                    __('%1$s - %2$s'),
                    __('New item'),
                    $item->getTypeName(1)
                ),
                'item'         => $item,
                'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0)
            ] + $params
        );
    }

   /**
    * path: '/ajax/tab/itemtype'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="ajax-tab", pattern="/ajax/tab/{itemtype}/{id:\d+}/{tab}")
    */
    public function ajaxTab(Request $request, Response $response, array $args)
    {
        global $IS_TWIG;
        $IS_TWIG = true;
        $get = $request->getQueryParams();

        if (!isset($get["withtemplate"])) {
            $get["withtemplate"] = "";
        }

        if ($item = getItemForItemtype($args['itemtype'])) {
            if ($item->get_item_to_display_tab) {
                // No id if ruleCollection but check right
                if ($item instanceof \RuleCollection) {
                    if (!$item->canList()) {
                        //TODO: redirect with error
                        throw new \RuntimeException('Cannot list');
                        exit();
                    }
                } elseif (!$item->can($args["id"], READ)) {
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
            \ObjectLock::setReadOnlyProfile();
        }

        $tab = explode('__', $args['tab']);
        $legacy = true;
        if (count($tab) == 2) {
            $sub_itemtype = $tab[0];
            $sub_item = new $sub_itemtype;
            $sub_item_display = $sub_item->getSubItemDisplay();
           //FIXME: similar code is used for count in CommonGLPI::addTab
            switch ($sub_item_display) {
                case \CommonGLPI::SUBITEM_SHOW_LIST:
                    if ($sub_item instanceof \CommonDBRelation) {
                        $params = $request->getQueryParams() + $args;

                        if ($sub_item instanceof \Item_Devices && $item instanceof \CommonDevice) {
                            $types = $sub_item->itemAffinity();

                            foreach ($types as $sub_type) {
                                $sub_link_item = new $sub_type;
                                $search = new \Search($item, $params);
                                $data[$sub_type] = [
                                    'search_data'  => $search->getData([
                                        'item'      => $item,
                                        'sub_item'  => $sub_link_item
                                    ]),
                                    'item'         => $sub_link_item
                                ];
                            }
                        } elseif ($sub_item instanceof \Item_Devices) {
                            $types = $sub_item->getDeviceTypes();
                            $data = [];
                            foreach ($types as $sub_type) {
                                $sub_link_item = new $sub_type;
                                if ($item->getType() == $sub_link_item::$itemtype_1) {
                                    $link_type = $sub_link_item::$itemtype_2;
                                } elseif ($item->getType() == $sub_link_item::$itemtype_2) {
                                    $link_type = $sub_link_item::$itemtype_1;
                                } else {
                                    $link_type = ($sub_link_item::$itemtype_1 != 'itemtype' ?
                                        $sub_link_item::$itemtype_1 :
                                        $sub_link_item::$itemtype_2
                                    );
                                }

                                if (!empty($link_type) && $link_type != 'itemtype') {
                                    $link = new $link_type;
                                } else {
                                    $link = $sub_item;
                                }

                                $search = new \Search($link, $params);
                                $data[$link->getType()] = [
                                    'search_data'  => $search->getData([
                                        'item'      => $item,
                                        'sub_item'  => $sub_link_item
                                    ]),
                                    'item'         => $sub_link_item
                                ];
                            }

                            return $this->view->render(
                                $response,
                                'list_itemtyped_contents.twig',
                                [
                                    'data'   => $data
                                ]
                            );
                        } else {
                            if ($item->getType() == $sub_item::$itemtype_1) {
                                 $link_type = $sub_item::$itemtype_2;
                            } elseif ($item->getType() == $sub_item::$itemtype_2) {
                                 $link_type = $sub_item::$itemtype_1;
                            } else {
                                $link_type = ($sub_item::$itemtype_1 != 'itemtype' ?
                                    $sub_item::$itemtype_1 :
                                    $sub_item::$itemtype_2
                                );
                            }

                            if (!empty($link_type) && $link_type != 'itemtype') {
                                 $link = new $link_type;
                            } else {
                                 $link = $sub_item;
                            }

                            $search = new \Search($link, $params);
                            if (isset($args['page'])) {
                                 $search->setPage((int)$args['page']);
                            }
                            $data = $search->getData([
                                'item'      => $item,
                                'sub_item'  => $sub_item
                            ]);

                            return $this->view->render(
                                $response,
                                'list_contents.twig',
                                [
                                    'search_data'     => $data,
                                    'item'            => $sub_item
                                ]
                            );
                        }
                    } elseif ($sub_item instanceof \CommonDBChild) {
                        $params = $request->getQueryParams() + $args;

                        $search = new \Search($sub_item, $params);
                        if (isset($args['page'])) {
                            $search->setPage((int)$args['page']);
                        }
                        $data = $search->getData([
                            'item'      => $item,
                            'sub_item'  => $sub_item
                        ]);

                        return $this->view->render(
                            $response,
                            'list_contents.twig',
                            [
                                'search_data'     => $data,
                                'item'            => $sub_item
                            ]
                        );
                    }
                    break;
                case \CommonGLPI::SUBITEM_SHOW_FORM:
                    $getcrit = [];
                    if ($sub_item instanceof \CommonDBRelation) {
                        if ($item->getType() == $sub_item::$itemtype_1) {
                            $getcrit = [
                                $sub_item::$items_id_1  => $item->fields['id']
                            ];
                        } elseif ($item->getType() == $sub_item::$itemtype_2) {
                            $getcrit = [
                                $sub_item::$items_id_2  => $item->fields['id']
                            ];
                        } else {
                            $getcrit = [
                                'itemtype'  => $item->getType(),
                                'items_id'  => $item->fields['id']
                            ];
                        }
                    } elseif ($sub_item instanceof \CommonDBChild) {
                        if ($item->getType() == $sub_item::$itemtype) {
                            $getcrit = [
                                $sub_item::$items_id => $item->fields['id']
                            ];
                        } else {
                            $getcrit = [
                                'itemtype'  => $item->getType(),
                                'items_id'  => $item->fields['id']
                            ];
                        }
                    }

                    $sub_item->getFromDBByCrit($getcrit);

                    if ($sub_item->isNewItem()) {
                        $params['glpi_form'] = $sub_item->getAddForm();
                    } else {
                        $params['glpi_form'] = $sub_item->getEditForm();
                    }
                    return $this->view->render(
                        $response,
                        'elements/form.twig',
                        $params
                    );

                    /*if (!isset($form['action'])) {
                    $form['action'] = $this->router->pathFor(
                        'do-add-asset',
                        $args
                    );
                    }*/
                    /*
                    $params['glpi_form'] = $item->getEditForm();
                    if (!isset($params['glpi_form']['action'])) {
                    $params['glpi_form']['action'] = $this->router->pathFor(
                        'do-edit-asset',
                        $args
                    );
                    }
                    */
                    break;
                case \CommonDBTM::SUBITEM_SHOW_SPEC:
                    if ($sub_item instanceof \Log) {
                        $sql_filters = \Log::convertFiltersValuesToSqlCriteria(
                            isset($_GET['filters']) ? $_GET['filters'] : []
                        );

                        // Total Number of events
                        $count_params = [
                            'items_id'  => $item->fields['id'],
                            'itemtype'  => $item->getType()
                        ];

                        $limit = $_SESSION['glpilist_limit'];
                        $db_history_data = $sub_item->getHistoryData(
                            $item,
                            0,
                            $limit
                        );

                        $columns = [
                            'id'        => [
                                'itemtype'  => 'Log',
                                'id'        => 0,
                                'name'      => __('ID'),
                                'meta'      => 0,
                                'searchopt' => []
                            ],
                            'date_mod'  => [
                                'itemtype'  => 'Log',
                                'id'        => 1,
                                'name'      => __('Date'),
                                'meta'      => 0,
                                'searchopt' => []
                            ],
                            'user_name'  => [
                                'itemtype'  => 'Log',
                                'id'        => 2,
                                'name'      => __('User'),
                                'meta'      => 0,
                                'searchopt' => []
                            ],
                            'field'  => [
                                'itemtype'  => 'Log',
                                'id'        => 3,
                                'name'      => __('Field'),
                                'meta'      => 0,
                                'searchopt' => []
                            ],
                            'change'  => [
                                'itemtype'  => 'Log',
                                'id'        => 4,
                                'name'      => __('Update'),
                                'meta'      => 0,
                                'searchopt' => []
                            ]
                        ];
                        $colkeys = array_keys($columns);

                        $history_data = [];
                        foreach ($db_history_data as $data) {
                            $history_data[] = [
                                'display_history'                             => $data['display_history'],
                                'Log_' . array_search('id', $colkeys)         => ['displayname' => $data["id"]],
                                'Log_' . array_search('date_mod', $colkeys)   => ['displayname'  => $data["date_mod"]],
                                'Log_' . array_search('user_name', $colkeys)  => ['displayname' => $data["user_name"]],
                                'Log_' . array_search('field', $colkeys)      => ['displayname' => $data['field']],
                                'Log_' . array_search('change', $colkeys)     => ['displayname' => $data['change']],
                                'datatype'                                    => $data['datatype'],
                                //'raw'             => $data['raw']
                            ];
                        }

                        $count = countElementsInTable("glpi_logs", $count_params);

                        $pages = $count / $limit;
                        $last = ceil($count / $limit);
                        $current_page = 1;
                        $previous = $current_page - 1;
                        if ($previous < 1) {
                            $previous = false;
                        }

                        $next = $current_page +1;
                        if ($next > $last) {
                            $next = $last;
                        }

                        $pagination = [
                            'start'           => 0,
                            'limit'           => $limit,
                            'count'           => $count,
                            'current_page'    => $current_page,
                            'previous_page'   => $previous,
                            'next_page'       => $next,
                            'last_page'       => $last,
                            'pages'           => []
                        ];

                        $idepart = $current_page - 2;
                        if ($idepart< 1) {
                            $idepart = 1;
                        }

                        $ifin = $current_page + 2;
                        if ($ifin > $last) {
                            $ifin = $last;
                        }

                        for ($i = $idepart; $i <= $ifin; $i++) {
                            $page = [
                              'value' => $i,
                              'title' => preg_replace("(%i)", $i, __("Page %i"))
                            ];
                            if ($i == $current_page) {
                                $page['current'] = true;
                                $page['title'] = preg_replace(
                                    "(%i)",
                                    $i,
                                    __("Current page (%i)")
                                );
                            }
                            $pagination['pages'][] = $page;
                        }

                        $search_data = [
                            'itemtype'        => 'Log',
                            'data'            => [
                                'begin'        => 0,
                                'end'          => $_SESSION['glpilist_limit'],
                                'totalcount'   => $count,
                                'cols'         => $columns,
                                'rows'         => $history_data
                            ],
                            'search'    => [
                                'start'  => 0
                            ],
                            'pagination'   => $pagination
                        ];
                        return $this->view->render(
                            $response,
                            'list_contents.twig',
                            [
                                'search_data'  => $search_data,
                                'item'         => $item,
                                'no_checkbox'  => true
                            ]
                        );
                    } else {
                        throw new \RuntimeException('No template parameted :/');
                    }
                    break;
                default:
                    $legacy = true;
                    break;
            }
        }

        if ($legacy) {
            ob_start();
            \CommonGLPI::displayStandardTab(
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
    }

   /**
    * path: '/itemtype/edit'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="update-asset", pattern="/{itemtype}/{action:edit|show}/{id:\d+}[/tab/{tab}]")
    */
    public function edit(Request $request, Response $response, array $args)
    {
        $action = $args['action'];
        $item = new $args['itemtype']();

        if ('edit' == $action && !$item->canEdit($args['id'])) {
           //redirect to show.
            $this->flash->addMessage('warning', __('You cannot edit this item.'));
            $args['action'] = 'show';
            return $response
             ->withStatus(302)
             ->withHeader('Location', $this->router->pathFor('update-asset', $args));
        }

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
            //TRANS: %1$s is a translated string, %2$s is the Itemtype name and %3$d the ID of the item
            $page_title = sprintf(
                __('%1$s - %2$s (#%3$d)'),
                __('Edit item'),
                $item->getTypeName(1),
                $item->fields['id']
            );
        }

        return $this->view->render(
            $response,
            'edit_page.twig',
            [
                'page_title'   => $page_title,
                'item'         => $item,
                'withtemplate' => (isset($args['withtemplate']) ? $args['withtemplate'] : 0),
                'current_tab'  => $args['tab']
            ] + $params
        );
    }

   /**
    * path: '/itemtype/add' (POST)
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="do-add-asset", pattern="/{itemtype}/add[/{withtemplate}]", method="POST")
    */
    public function doAdd(Request $request, Response $response, array $args)
    {
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
                    'update-asset',
                    [
                       'action'     => 'edit',
                        'itemtype'  => $args['itemtype'],
                        'id'        => $item->fields['id']
                    ]
                );
            }
        }

        return $response
             ->withStatus(302)
             ->withHeader('Location', $redirect_uri);
    }

   /**
    * path: '/itemtype/edit' (POST)
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="do-edit-asset", pattern="/{itemtype}/edit[/{withtemplate}]", method="POST")
    */
    public function doEdit(Request $request, Response $response, array $args)
    {
        $item = new $args['itemtype']();

        $post = $request->getParsedBody();
        $item->check($post['id'], UPDATE);
        if (!$item->update($post)) {
            $rand = mt_rand();
            $_SESSION["{$args['itemtype']}_edit_$rand"] = $post;
            $args['action'] = 'edit';
            $redirect_uri = $this->router->pathFor('update-asset', $args) . "?item_rand=$rand";
        } else {
           /** FIXME: should be handled in commondbtm
           Event::log($_POST["id"], "computers", 4, "inventory",
                 //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
           */
            $redirect_uri = $this->router->pathFor(
                'update-asset',
                [
                    'action'    => 'edit',
                    'itemtype'  => $args['itemtype'],
                    'id'        => $item->fields['id']
                ]
            );
        }

        return $response
         ->withStatus(302)
         ->withHeader('Location', $redirect_uri);
    }

   /**
    * path: '/devices'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="devices", pattern="/devices")
    */
    public function devices(Request $request, Response $response, array $args)
    {
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
    }
}
