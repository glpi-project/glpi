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

namespace Twig\Glpi\Extensions;

class GlpiDebug extends \Twig_Extension
{
   public function getFunctions() {
      return [
         new \Twig_SimpleFunction('exectime', [$this, 'callExecTime']),
         new \Twig_SimpleFunction('debugpanel', [$this, 'callDebugPanel'])
      ];
   }

   public function callExecTime() {
      global $TIMER_DEBUG;

      $timedebug = sprintf(_n('%s second', '%s seconds', $TIMER_DEBUG->getTime()),
                           $TIMER_DEBUG->getTime());

      if (function_exists("memory_get_usage")) {
         $timedebug = sprintf(__('%1$s - %2$s'), $timedebug, \Toolbox::getSize(memory_get_usage()));
      }
      return $timedebug;
   }

   public function callDebugPanel() {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $DEBUG_AUTOLOAD;

      $ajax = false; //FIXME
      $with_session = true; //FIXME

      $panel = '';
      if ($_SESSION['glpi_use_mode'] != \Session::DEBUG_MODE) { // mode normal
         return;
      }

      $panel .= "<div class='debug ".($ajax?"debug_ajax":"")."'>";
      if (!$ajax) {
         $panel .= "<span id='see_debug'>
                  <a href='#' class='fa fa-bug btn btn-warning' data-toggle='debugtabs' title='" . __s('Display GLPI debug informations')  . "'>
                     <span class='sr-only'>See GLPI DEBUG</span>
                  </a>
         </span>";
      }

      /*
<div class="box box-success box-solid">
            <div class="box-header with-border">
              <h3 class="box-title">Removable</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              The body of the box
            </div>
            <!-- /.box-body -->
          </div>
       */
      $panel .= "<div id='debugtabs' class='hidden box box-warning box-solid'>";
      $panel .= "      <div class='box-header with-border'>
              <h3 class='box-title'>Debug panel</h3>

              <div class='box-tools pull-right'>
                <button type='button' class='btn btn-box-tool' id='hide_debug'><i class='fa fa-times'></i></button>
              </div>
              <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class='box-body'>";

      $panel .= "<ul class='nav nav-pills nav-justified' role='tablist'>";
      if ($CFG_GLPI["debug_sql"]) {
         $panel .= "<li class='active'><a href='#debugsql' data-toggle='pill'>SQL REQUEST</a></li>";
      }
      if ($CFG_GLPI["debug_vars"]) {
         $panel .= "<li><a href='#debugautoload' data-toggle='pill'>AUTOLOAD</a></li>";
         $panel .= "<li><a href='#debugpost' data-toggle='pill'>POST VARIABLE</a></li>";
         $panel .= "<li><a href='#debugget' data-toggle='pill'>GET VARIABLE</a></li>";
         if ($with_session) {
            $panel .= "<li><a href='#debugsession' data-toggle='pill'>SESSION VARIABLE</a></li>";
         }
         $panel .= "<li><a href='#debugserver' data-toggle='pill'>SERVER VARIABLE</a></li>";
      }
      $panel .= "</ul>";

      $panel .= "<div class='tab-content'>";
      if ($CFG_GLPI["debug_sql"]) {
         $panel .= "<div id='debugsql' role='tabpanel' class='tab-pane active'>";
         $panel .= "<div class='b'>".$SQL_TOTAL_REQUEST." Queries ";
         $panel .= "took  ".array_sum($DEBUG_SQL['times'])."s</div>";

         $panel .= "<table class='tab_cadre'><tr><th>N&#176; </th><th>Queries</th><th>Time</th>";
         $panel .= "<th>Errors</th></tr>";

         foreach ($DEBUG_SQL['queries'] as $num => $query) {
            $panel .= "<tr class='tab_bg_".(($num%2)+1)."'><td>$num</td><td>";
            $panel .= \Html::cleanSQLDisplay($query);
            $panel .= "</td><td>";
            $panel .= $DEBUG_SQL['times'][$num];
            $panel .= "</td><td>";
            if (isset($DEBUG_SQL['errors'][$num])) {
               $panel .= $DEBUG_SQL['errors'][$num];
            } else {
               $panel .= "&nbsp;";
            }
            $panel .= "</td></tr>";
         }
         $panel .= "</table>";
         $panel .= "</div>";
      }
      if ($CFG_GLPI["debug_vars"]) {
         $panel .= "<div id='debugautoload' role='tabpanel' class='tab-pane'>".implode(', ', $DEBUG_AUTOLOAD)."</div>";
         $panel .= "<div id='debugpost' role='tabpanel' class='tab-pane'>";

         ob_start();
         \Html::printCleanArray($_POST, 0, true);
         $panel .= ob_get_contents();
         ob_end_clean();

         $panel .= "</div>";
         $panel .= "<div id='debugget' role='tabpanel' class='tab-pane'>";

         ob_start();
         \Html::printCleanArray($_GET, 0, true);
         $panel .= ob_get_contents();
         ob_end_clean();

         $panel .= "</div>";
         if ($with_session) {
            $panel .= "<div id='debugsession' role='tabpanel' class='tab-pane'>";
            ob_start();
            \Html::printCleanArray($_SESSION, 0, true);
            $panel .= ob_get_contents();
            ob_end_clean();
            $panel .= "</div>";
         }
         $panel .= "<div id='debugserver' role='tabpanel' class='tab-pane'>";
         ob_start();
         \Html::printCleanArray($_SERVER, 0, true);
         $panel .= ob_get_contents();
         ob_end_clean();

         $panel .= "</div>";
      }
      $panel .= "</div></div>";
      $panel .= "</div>";
      $panel .= "</div>";
      return $panel;
   }

   public function getName() {
      return 'glpi_debug';
   }
}
