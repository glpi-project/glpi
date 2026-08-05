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

namespace Glpi\Controller\Rule;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\BadRequestHttpException;
use Html;
use Rule;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Safe\ini_get;

final class RuleListController extends AbstractController
{
    private \RuleCollection $ruleCollection;

    public function __invoke(Request $request): Response
    {
        $id             = $request->request->getInt('id');
        $request->request->get('replay_rule');
        $rule_classname = $request->attributes->getString('class');

        $rule = \getItemForItemtype($rule_classname);
        if (!($rule instanceof Rule)) {
            throw new BadRequestHttpException(sprintf('Invalid rule (sub)type `%s`.', $rule_classname));
        }
        $this->ruleCollection = $rule->getCollectionClassInstance();
        $this->ruleCollection->setEntity((int) $_SESSION['glpiactive_entity']);

        // dispatch
        if ($request->request->has('action')) {
            $this->moveRule(
                $id,
                $request->request->getString('action'),
                (int) $request->request->getInt('condition')
            );

            return new RedirectResponse(Html::getBackUrl());
        }
        if ($request->request->has('reinit')) {
            $this->reinitializeRules();

            return new RedirectResponse(Html::getBackUrl());
        }
        if ($request->query->has('reinit') || $request->request->has('replay_rule')) {
            // confirmation for replay
            $confirmed = !is_null($request->request->get('replay_confirm'));
            $manufacturer = $request->request->getInt('manufacturer');

            $offset = $request->request->getInt('offset');
            $start = $request->request->getInt('start') ?: time();

            return $this->replayRule(
                offset: $offset,
                manufacturer_id: $manufacturer,
                start: $start,
                confirmed: $confirmed
            );
        }

        // default action : display list
        return $this->renderList();
    }

    /**
     * @param int $id
     * @param string $direction
     * @param int $condition
     */
    private function moveRule(int $id, string $direction, int $condition): void
    {
        if (!in_array($direction, ['up', 'down'])) {
            throw new BadRequestHttpException('Invalid action, only "up" or "down" are supported.');
        }

        $this->ruleCollection->checkGlobal(UPDATE);
        $this->ruleCollection->changeRuleOrder($id, $direction, $condition);
    }

    private function reinitializeRules(): void
    {
        $this->ruleCollection->checkGlobal(UPDATE);

        $ruleclass = $this->ruleCollection->getRuleClass();
        if (is_null($ruleclass)) {
            throw new \RuntimeException('Rule class not found.');
        }

        $initProcess = $ruleclass->initRules();

        if ($initProcess) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('%1$s has been reset.'),
                    $this->ruleCollection->getTitle()
                ))
            );
        } else {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('%1$s reset failed.'),
                    $this->ruleCollection->getTitle()
                )),
                false,
                ERROR
            );
        }
    }

    /**
     * @param int $offset
     * @param int $manufacturer_id
     * @param int $start
     * @param bool $confirmed
     * @return Response
     * @throws \Exception
     *
     * Possible responses:
     * - Confirmation needed response
     * - RedirectResponse to continue the process
     * - StreamedResponse to display the final result
     */
    private function replayRule(int $offset, int $manufacturer_id, int $start, bool $confirmed): Response
    {
        $this->ruleCollection->checkGlobal(UPDATE);

        // - Confirmation needed response
        if (!$confirmed) {
            $rulecollection = $this->ruleCollection;
            if ($this->ruleCollection->hasWarningBeforeReplayRulesOnExistingDB()) {
                return new StreamedResponse(static function () use ($rulecollection) {
                    Html::header(
                        Rule::getTypeName(Session::getPluralNumber()),
                        '',
                        "admin",
                        $rulecollection->menu_type,
                        $rulecollection->menu_option
                    );

                    echo $rulecollection->getWarningBeforeReplayRulesOnExistingDB();

                    Html::footer();
                });
            }
        }

        // - RedirectResponse to continue the process & StreamedResponse to display the final result
        // start the final response, abort with HTML::redirect to continue the process
        $rulecollection = $this->ruleCollection;
        $rule_class = $this->ruleCollection->getRuleClassName();

        return new StreamedResponse(static function () use ($rulecollection, $rule_class, $start, $offset, $manufacturer_id) {
            // timestamp limit to stop the process
            $max_execution_time = (int) ini_get("max_execution_time");
            // so at the moment we sum a microtime
            $deadline_timestamp = $start + (int) ($max_execution_time > 0 ? $max_execution_time / 2 : 30);

            Html::header(
                Rule::getTypeName(Session::getPluralNumber()),
                '',
                "admin",
                $rulecollection->menu_type,
                $rulecollection->menu_option
            );
            // output html contents
            $new_offset       = $rulecollection->replayRulesOnExistingDB(
                $offset,
                $deadline_timestamp,
                [],
                ['manufacturer' => $manufacturer_id]
            );

            $more_work_needed = false !== $new_offset && $new_offset >= 0;
            if ($more_work_needed) {
                Html::redirect($rule_class::getSearchURL() . "?start=$start&replay_rule=1&offset=$new_offset&manufacturer=" . $manufacturer_id);
            }

            $elapsed_time = time() - $start;
            $message = sprintf(
                __('Task completed in %s'),
                Html::timestampToString($elapsed_time)
            );

            echo "<a href='" . htmlescape($rule_class::getSearchURL()) . "'>" . __s('Back') . "</a>";
            echo "<table class='tab_cadrehov'>";
            echo "<tr><th><div class='relative b'>" . htmlescape($rulecollection->getTitle()) . "<br>"
                . __s('Replay the rules dictionary') . "</div></th></tr>";
            echo "<tr><td class='center'>" . \htmlescape($message) . "</td></tr>";
            echo "</table>";

            Html::footer();
        });
    }

    private function renderList(): Response
    {
        $this->ruleCollection->checkGlobal(READ);
        $rulecollection = $this->ruleCollection;

        return new StreamedResponse(static function () use ($rulecollection) {
            Html::header(
                Rule::getTypeName(Session::getPluralNumber()),
                '',
                'admin',
                $rulecollection->menu_type,
                $rulecollection->menu_option
            );

            $rulecollection->display([
                'display_criterias' => true,
                'display_actions'   => true,
            ]);

            Html::footer();
        });
    }
}
