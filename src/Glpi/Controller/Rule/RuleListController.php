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

namespace Glpi\Controller\Rule;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RuleListController extends AbstractController
{
    private \RuleTicketCollection $ruleCollection; // @todo genéraliser a tous les RuleCollection

    #[Route("/{class}/Search", name: "fixme", priority: -1)]
    public function __invoke(Request $request): Response
    {
        $id =           (int) $request->get('id');
        $reinit =       $request->get('reinit');
        $replay_rule =  $request->get('replay_rule');
        $reorder =      $request->request->get('action');
        // pour debug
//        $reorder =      $request->get('action');

        $this->ruleCollection = new \RuleTicketCollection($_SESSION['glpiactive_entity']);

        // dispatch
        if(!is_null($reorder))
        {
            // @todo maybe rewrite the ajax file
            if(!in_array($reorder, ['up', 'down'])) {
                return new Response('Invalid action, only "up" or "down" are supported.', Response::HTTP_BAD_REQUEST);
            }

            $this->moveRule($id, $reorder, (int) $request->request->get('condition'));

            return new RedirectResponse($request->getPathInfo());
        }
        if(!is_null($reinit))
        {
            $this->reinitializeRules();

            return new RedirectResponse($request->getPathInfo());
        }
        if (!is_null($replay_rule))
        {
            // act only if confirmation is needed and given
            $replay_confirmation = !is_null($request->request->get('replay_confirm') ?? $request->query->get('replay_confirm'));
            if(!$replay_confirmation) {
                // @todo sort du html
                $need_confirmation = $this->ruleCollection->warningBeforeReplayRulesOnExistingDB();
                if($need_confirmation) {
                    throw new \Exception('implement me');
                    // rien à afficher, c'est déjà fait par $this->ruleCollection->warningBeforeReplayRulesOnExistingDB()
                    // donc a capter dans streamResponse
                    return new Response('html à sortir / implement me '.__LINE__, Response::HTTP_NOT_IMPLEMENTED);
                }

            }

            // same behaviour as before, can maybe be simplified if manufacturer is not in conflict in POST & GET
            if(!isset($_GET['offset'])) {
                $manufacturer = $request->request->get('manufacturer') ?? '0';
            } else {
                $manufacturer = $request->query->get('manufacturer') ?? '0';
            }
            // @todo espace $manufacturer

            $offset = (int) $request->get('offset');
            $start = (int) $request->get('start') ?? time();

            $this->replayRule(offset: $offset, manufacturer: $manufacturer, start: $start);

            return new Response('html à sortir / implement me '.__LINE__, Response::HTTP_NOT_IMPLEMENTED);
        }

        // default action : display list
        return $this->renderList();
    }

    private function checkIsValidClass(string $class): void
    {
        if ($class === '') {
            throw new BadRequestHttpException('The "class" attribute is mandatory for itemtype routes.');
        }

        if (!\class_exists($class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" does not exist.", $class));
        }

        if (!\is_subclass_of($class, CommonGLPI::class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" is not a valid itemtype.", $class));
        }

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @param int $id
     * @param 'up' |'down' $direction
     * @param int $condition
     */
    private function moveRule(int $id, string $direction, int $condition): void
    {
        $this->ruleCollection->checkGlobal(UPDATE);
        $this->ruleCollection->changeRuleOrder($id, $direction, $condition);
    }

    private function reinitializeRules(): void
    {
        // @todo pas de verif de droit ?
        $ruleclass = $this->ruleCollection->getRuleClass();
        if(is_null($ruleclass)) {
            throw new \RuntimeException('Rule class not found.');
        }

        $initProcess = $ruleclass->initRules();

        if ($initProcess) {
            // @todo use flash ?
            \Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                //TRANS: first parameter is the rule type name
                    __('%1$s has been reset.'),
                    $this->ruleCollection->getTitle()
                ))
            );
        }
        else
        {
            // @todo use flash ?
            \Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                //TRANS: first parameter is the rule type name
                    __('%1$s reset failed.'),
                    $this->ruleCollection->getTitle()
                )),
                false,
                ERROR
            );
        }
    }

    private function replayRule(int $offset, string $manufacturer, int $start): void
    {
        $this->ruleCollection->checkGlobal(UPDATE);

        // @todo voir layout twig
//        Html::header(
//            Rule::getTypeName(Session::getPluralNumber()),
//            '',
//            "admin",
//            $this->ruleCollection->menu_type,
//            $this->ruleCollection->menu_option
//        );


        // affichage bar de progression -> streamResponse
        echo "<table class='tab_cadrehov'>";

        echo "<tr><th><div class='relative b'>" . htmlescape($this->ruleCollection->getTitle()) . "<br>" .
            __s('Replay the rules dictionary') . "</div></th></tr>";
        echo "<tr><td class='center'>";
        \Html::progressBar('doaction_progress', [
            'create' => true,
            'message' => __s('Work in progress...')
        ]);
        echo "</td></tr>";
        echo "</table>";

        // timestamp limit to stop the process
        $max_execution_time = (int) get_cfg_var("max_execution_time");
        // so at the moment we sum a microtime
        $deadline_timestamp = $start + ($max_execution_time > 0 ? $max_execution_time / 2.0 : 30.0);

        if ($offset === 0)  {
            // First run
            $new_offset       = $this->ruleCollection->replayRulesOnExistingDB(
                $offset,
                $deadline_timestamp,
                [],
                ['manufacturer' => $manufacturer]
            );
        } else {
            // Next run
            $new_offset       = $this->ruleCollection->replayRulesOnExistingDB(
                $offset,
                $deadline_timestamp,
                [],
                ['manufacturer' => $manufacturer]
            );
        }

        $rule_class = $this->ruleCollection->getRuleClassName();

        if ($new_offset < 0) {
            // Work ended
            // @todo remplacer par time()
            $elapsed_time = round(microtime(true) - $start);
            \Html::changeProgressBarMessage(sprintf(
                __('Task completed in %s'),
                \Html::timestampToString($elapsed_time)
            ));
            echo "<a href='" . htmlescape($rule_class::getSearchURL()) . "'>" . __s('Back') . "</a>";
        } else {
            // Need more work
            \Html::redirect($rule_class::getSearchURL() . "?start=$start&replay_rule=1&offset=$new_offset&manufacturer=" .
                "$manufacturer");
        }

    }

    private function renderList(): Response
    {
        $this->ruleCollection->checkGlobal(READ);
        $rulecollection = $this->ruleCollection;

        return new StreamedResponse(static function () use ($rulecollection) {
            \Html::header(
                \Rule::getTypeName(\Session::getPluralNumber()),
                '',
                'admin',
                $rulecollection->menu_type,
                $rulecollection->menu_option
            );

            $rulecollection->display([
                'display_criterias' => true,
                'display_actions'   => true,
            ]);

            \Html::footer();
        });
    }
}
