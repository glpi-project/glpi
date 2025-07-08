<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Tools;

use FriendsOfTwig\Twigcs\RegEngine\Checker\Handler;
use FriendsOfTwig\Twigcs\RegEngine\RulesetBuilder;
use FriendsOfTwig\Twigcs\RegEngine\RulesetConfigurator;
use FriendsOfTwig\Twigcs\Rule\LowerCaseVariable;
use FriendsOfTwig\Twigcs\Rule\RegEngineRule;
use FriendsOfTwig\Twigcs\Rule\TrailingSpace;
use FriendsOfTwig\Twigcs\Rule\UnusedMacro;
use FriendsOfTwig\Twigcs\Ruleset\Official;
use FriendsOfTwig\Twigcs\Validator\Violation;

class GlpiTwigRuleset extends Official
{
    private $twigMajorVersion;

    public function __construct(int $twigMajorVersion)
    {
        $this->twigMajorVersion = $twigMajorVersion;
    }

    public function getRules()
    {
        return [
            new LowerCaseVariable(Violation::SEVERITY_ERROR),
            new RegEngineRule(Violation::SEVERITY_ERROR, $this->getRegEngineRuleset()),
            new TrailingSpace(Violation::SEVERITY_ERROR),
            new UnusedMacro(Violation::SEVERITY_WARNING),
            // new UnusedVariable(Violation::SEVERITY_WARNING), // Cannot be enable since we do not explicitely pass variables to includes
        ];
    }

    private function getRegEngineRuleset()
    {
        $configurator = new RulesetConfigurator();
        $configurator->setTwigMajorVersion($this->twigMajorVersion);

        // Update config
        $config = $configurator->getProcessedConfiguration();

        // Retrieve ruleset
        $builder = new RulesetBuilder($configurator);
        $rulesets = $builder->build();

        // Override rules:
        $overrided = [];

        // Remove strictness in spacing inside variables declaration.
        $overrided['<➀set➊@➋=➌$➁>'] = $builder
         ->argTag()
         ->delegate('$', 'expr')
         ->enforceSize('➊', $config['set']['after_set'], 'There should be %quantity% space(s) after the "set".')
         //->enforceSize('➋', $this->config['set']['after_var_name'], 'There should be %quantity% space(s) before the "=".')
         ->enforceSize('➌', $config['set']['after_equal'], 'There should be %quantity% space(s) after the "=".');
        $overrided['@ :_$•,…%'] = $overrided['"@" :_$•,…%'] = Handler::create()
         ->delegate('$', 'expr')
         ->delegate('%', 'hash')
         //->enforceSize(' ', $onfig['hash']['after_key'], 'There should be %quantity% space(s) between the key and ":".')
         ->enforceSize('_', $config['hash']['before_value'], 'There should be %quantity% space(s) between ":" and the value.')
         ->enforceSize('•', $config['hash']['after_value'], 'There should be %quantity% space(s) between the value and the following ",".')
         ->enforceSpaceOrLineBreak('…', $config['hash']['after_coma'], 'There should be %quantity% space(s) between the , and the following hash key.');
        $overrided['@ :_$,'] = $overrided['@ :_$'] = $overrided['"@" :_$,'] = $overrided['"@" :_$'] = Handler::create()
         ->delegate('$', 'expr')
         //->enforceSize(' ', $config['hash']['after_key'], 'There should be %quantity% space(s) between the key and ":".')
         ->enforceSize('_', $config['hash']['before_value'], 'There should be %quantity% space(s) between ":" and the value.');

        // Fixes https://github.com/friendsoftwig/twigcs/issues/170
        $overrided['@➀=(?![>=])➁$➂,➃%'] = Handler::create()
         ->enforceSize('➀', 1, 'There should be %quantity% space(s) before the "=" in the named arguments list.')
         ->enforceSize('➁', 1, 'There should be %quantity% space(s) after the "=" in the named arguments list.')
         ->enforceSize('➂', $config['named_args']['after_value'], 'There should be %quantity% space(s) after the value in the named arguments list.')
         ->delegate('$', 'expr')
         ->delegate('%', 'argsList');
        $overrided['@➀=(?![>=])➁$'] = Handler::create()
         ->enforceSize('➀', 1, 'There should be %quantity% space(s) before the "=" in the named arguments list.')
         ->enforceSize('➁', 1, 'There should be %quantity% space(s) after the "=" in the named arguments list.')
         ->delegate('$', 'expr');

        foreach ($rulesets as $ruleset_key => $ruleset) {
            foreach ($ruleset as $rule_key => $rule) {
                $rule_id = $rule[1];
                if (array_key_exists($rule_id, $overrided)) {
                    $rulesets[$ruleset_key][$rule_key][2] = $overrided[$rule_id];
                }
            }
        }

        return $rulesets;
    }
}
