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

namespace Glpi\Controller\Dashboard;

use Glpi\Controller\AbstractController;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Grid;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use function Safe\json_encode;
use function Safe\ob_end_flush;

final class CardsController extends AbstractController
{
    /**
     * Render several cards of a dashboard in one request, each one sent as soon as it is computed.
     */
    #[Route("/Dashboard/Cards", name: "glpi_dashboard_cards", methods: "POST")]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function __invoke(Request $request): Response
    {
        // A JSON body is not subject to `max_input_vars` and keeps the types of the card options.
        try {
            $input = $request->toArray();
        } catch (JsonException) {
            throw new BadRequestHttpException();
        }

        $dashboard_key = $input['dashboard'] ?? null;
        $cards         = $input['cards'] ?? null;
        if (!\is_string($dashboard_key) || !\is_array($cards)) {
            throw new BadRequestHttpException();
        }

        if (!(new Dashboard($dashboard_key))->canViewCurrent()) {
            throw new AccessDeniedHttpException();
        }

        // The cards only read the session: its lock must not make the other requests of the page wait.
        Session::writeClose();

        $grid = new Grid($dashboard_key);

        return new StreamedResponse(
            function () use ($grid, $dashboard_key, $cards): void {
                // An output buffer would hold the lines back until the end of the request.
                while (\ob_get_level() > 0) {
                    ob_end_flush();
                }

                foreach ($this->getCardsLines($grid, $dashboard_key, $cards) as $line) {
                    echo $line . "\n";
                    \flush();
                }
            },
            headers: [
                // One JSON object per line. Sent as plain text, which web servers compress by default, unlike JSON types.
                'Content-Type'      => 'text/plain; charset=UTF-8',
                'Cache-Control'     => 'no-store',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * Render the given cards, one JSON line (`id` of the grid item, `html` of its card) each.
     *
     * @param array<mixed> $cards
     *
     * @return iterable<string>
     */
    private function getCardsLines(Grid $grid, string $dashboard_key, array $cards): iterable
    {
        foreach ($cards as $card) {
            if (!\is_array($card) || !\is_string($card['card_id'] ?? null) || !\is_string($card['gridstack_id'] ?? null)) {
                continue;
            }

            try {
                // @phpstan-ignore-next-line argument.type
                $html = $grid->getCardHtml($card['card_id'], [
                    'dashboard' => $dashboard_key,
                    'card_id'   => $card['card_id'],
                    // The browser cache headers of a card cannot be sent: the response is shared and already started.
                    'force'     => true,
                    'args'      => \is_array($card['args'] ?? null) ? $card['args'] : [],
                ]);

                yield json_encode(
                    ['id' => $card['gridstack_id'], 'html' => $html],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );
            } catch (Throwable $e) {
                // The script requests a card it did not receive on its own, the next ones are still computed.
                ErrorHandler::logCaughtException($e);
            }
        }
    }
}
