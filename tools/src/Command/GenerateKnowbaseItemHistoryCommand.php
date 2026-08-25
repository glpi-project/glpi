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

namespace Glpi\Tools\Command;

use Document;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Console\AbstractCommand;
use Glpi\DBAL\QueryParam;
use Glpi\ShareToken;
use Glpi\UI\IllustrationManager;
use Group;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
use KnowbaseItem_Revision;
use KnowbaseItemTranslation;
use Log;
use LogicException;
use mysqli_stmt;
use Profile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use User;

/**
 * Give a single knowledge base article a huge history, to see how the article
 * history side panel behaves with a lot of events.
 *
 * This targets the knowledge base history only, i.e. what
 * `Glpi\Knowbase\History\HistoryBuilder` reads, not the standard GLPI history
 * tab. The builder assembles its events from three tables, and every one of its
 * sources gets data here so no code path is left untested:
 *
 * - `glpi_knowbaseitems_revisions` with an empty language: one "Version N"
 *   event each (`addRevisionsToHistory()`).
 * - `glpi_knowbaseitems_revisions` with a language: one "<language> — Version N"
 *   event each (`addTranslationRevisionsToHistory()`).
 * - `glpi_knowbaseitemtranslations`: one "<language> — Current version" event
 *   per row (`addCurrentTranslationsToHistory()`).
 * - `glpi_logs`, update of the `answer` field (search option 7): the single
 *   "Current version" event (`addCurrentVersionToHistory()`). Only the most
 *   recent row produces an event, older ones are invisible, so exactly one is
 *   written.
 * - `glpi_logs`, update of the `name` field (search option 1): "Renamed"
 *   (`addNameChangesToHistory()`).
 * - `glpi_logs`, update of `is_faq` (search option 8): "Added to the FAQ" /
 *   "Removed from the FAQ" (`addFaqStatusChangesToHistory()`).
 * - `glpi_logs`, update of the service catalog fields (search options 84 to
 *   87): "Service catalog updated" (`addServiceCatalogChangesToHistory()`).
 *   All four options are used, as each one renders a different description.
 * - `glpi_logs`, update of `illustration` (search option 88): "Illustration
 *   updated" (`addIllustrationChangesToHistory()`). The three shapes of the new
 *   value (removed, custom, native) are used.
 * - `glpi_logs`, relations to a `kb_types` itemtype: "Item linked" / "Item
 *   unlinked" (`addAssociatedItemChangesToHistory()`).
 * - `glpi_logs`, relations to the legacy `KnowbaseItemCategory` itemtype:
 *   "Added to category" / "Removed from category"
 *   (`addCategoryChangesToHistory()`).
 * - `glpi_logs`, relations to `Document`: "File added" / "File removed"
 *   (`addDocumentChangesToHistory()`).
 * - `glpi_logs`, relations to `Entity`, `Group`, `Profile` and `User`:
 *   "Permissions updated" (`addPermissionChangesToHistory()`).
 * - `glpi_logs`, relations to `ShareToken`: "Sharing enabled" / "Sharing
 *   disabled" / "Sharing link regenerated" (`addSharingChangesToHistory()`).
 *
 * The `CreationEvent` fallback of `addCurrentVersionToHistory()` is the only
 * case not covered, as it is by definition mutually exclusive with the
 * "Current version" log event, which is the realistic case for an article that
 * has been updated.
 *
 * Rows are inserted with plain `INSERT` statements instead of going through
 * `KnowbaseItem::update()` and friends: replaying 100 000 real updates would
 * take hours, and would also trigger notifications and search engine updates
 * that are not wanted here.
 */
final class GenerateKnowbaseItemHistoryCommand extends AbstractCommand
{
    /**
     * Number of inserted rows between two commits. Inserting everything in a
     * single transaction would build a huge undo log for no benefit.
     */
    private const COMMIT_EVERY = 1000;

    /** Maximum number of ids per `IN (...)` clause built while purging. */
    private const PURGE_CHUNK_SIZE = 1000;

    /** Number of days in the past the generated events are spread over. */
    private const DATE_SPREAD_DAYS = 730;

    private const SOURCE_REVISION             = 'revision';
    private const SOURCE_TRANSLATION_REVISION = 'translation_revision';
    private const SOURCE_NAME                 = 'name';
    private const SOURCE_FAQ                  = 'faq';
    private const SOURCE_SERVICE_CATALOG      = 'service_catalog';
    private const SOURCE_ASSOCIATED_ITEM      = 'associated_item';
    private const SOURCE_CATEGORY             = 'category';
    private const SOURCE_DOCUMENT             = 'document';
    private const SOURCE_PERMISSION           = 'permission';
    private const SOURCE_SHARING              = 'sharing';
    private const SOURCE_ILLUSTRATION         = 'illustration';

    /**
     * How the generated events are spread over the history sources, as relative
     * weights. Content revisions dominate, as they do on a real article, but
     * every source gets at least one event.
     */
    private const WEIGHTS = [
        self::SOURCE_REVISION             => 35,
        self::SOURCE_TRANSLATION_REVISION => 15,
        self::SOURCE_SERVICE_CATALOG      => 10,
        self::SOURCE_ASSOCIATED_ITEM      => 10,
        self::SOURCE_PERMISSION           => 8,
        self::SOURCE_NAME                 => 5,
        self::SOURCE_FAQ                  => 5,
        self::SOURCE_DOCUMENT             => 5,
        self::SOURCE_SHARING              => 3,
        self::SOURCE_CATEGORY             => 2,
        self::SOURCE_ILLUSTRATION         => 2,
    ];

    /** Languages used for translations, when supported by this GLPI version. */
    private const PREFERRED_LANGUAGES = [
        'fr_FR', 'de_DE', 'es_ES', 'it_IT', 'pt_BR', 'nl_NL', 'pl_PL', 'ru_RU',
    ];

    /** Search option of the `answer` field, read as the current version. */
    private const SEARCH_OPTION_ANSWER = 7;

    /** Search option of the `name` field, read as a rename. */
    private const SEARCH_OPTION_NAME = 1;

    /** Search option of the `is_faq` field. */
    private const SEARCH_OPTION_FAQ = 8;

    /** Search options of the service catalog fields, in `WEIGHTS` order. */
    private const SEARCH_OPTIONS_SERVICE_CATALOG = [
        84, // show_in_service_catalog
        85, // is_pinned
        86, // description
        87, // forms_categories_id
    ];

    /** Search option of the `illustration` field. */
    private const SEARCH_OPTION_ILLUSTRATION = 88;

    /** Itemtypes read by `addPermissionChangesToHistory()`. */
    private const PERMISSION_TYPES = [
        Entity::class,
        Group::class,
        Profile::class,
        User::class,
    ];

    /**
     * Legacy itemtype read by `addCategoryChangesToHistory()`. The class no
     * longer exists, only the logs it used to write are still read.
     */
    private const LEGACY_CATEGORY_ITEMTYPE = 'KnowbaseItemCategory';

    private const WORDS = [
        'agent', 'account', 'backup', 'certificate', 'client', 'cluster',
        'command', 'configuration', 'connection', 'console', 'credentials',
        'database', 'device', 'directory', 'domain', 'driver', 'endpoint',
        'entity', 'firewall', 'gateway', 'group', 'hardware', 'helpdesk',
        'hostname', 'image', 'instance', 'interface', 'inventory', 'licence',
        'network', 'node', 'package', 'password', 'permission', 'policy',
        'port', 'printer', 'profile', 'protocol', 'proxy', 'queue', 'replica',
        'repository', 'request', 'resource', 'role', 'schedule', 'server',
        'service', 'session', 'setting', 'snapshot', 'storage', 'switch',
        'task', 'template', 'ticket', 'token', 'update', 'user', 'version',
        'volume', 'workstation',
    ];

    private const CATEGORY_NAMES = [
        'How-to', 'Procedures', 'Known issues', 'Reference', 'Onboarding',
        'Security', 'Networking', 'Workstations',
    ];

    /** Article the history is attached to. */
    private int $article_id = 0;

    private string $article_name = '';

    /** @var array<int, string> Author ids, mapped to their `glpi_logs` name. */
    private array $authors = [];

    private int $author_cursor = 0;

    /** @var string[] Languages used by the generated translations. */
    private array $languages = [];

    /** @var string[] Itemtypes linked by the generated "Item linked" events. */
    private array $item_types = [];

    /** @var string[] Available illustration ids, empty when assets are not built. */
    private array $illustrations = [];

    /** Timestamp the generated dates are spread backward from. */
    private int $reference_time = 0;

    /** Total number of events, used to spread them over the date window. */
    private int $total_events = 0;

    /** @var array<string, int> Number of events written per source, so far. */
    private array $counters = [];

    /** @var array<string, int> Last revision number used, per language. */
    private array $revision_numbers = [];

    private mysqli_stmt $revision_stmt;
    private mysqli_stmt $translation_stmt;
    private mysqli_stmt $log_stmt;

    private bool $in_transaction = false;
    private int $pending_rows = 0;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_knowbase_item_history');
        $this->setDescription(
            'Generate a huge history for a single knowledge base article,'
            . ' to test the article history panel with a lot of events.'
        );

        $this->addOption(
            'count',
            null,
            InputOption::VALUE_REQUIRED,
            'Total number of history events to create',
            '100000'
        );
        $this->addOption(
            'article',
            null,
            InputOption::VALUE_REQUIRED,
            'Id of an existing article the history is added to (defaults to creating a new article)'
        );
        $this->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of the created article, also used to find it back on purge',
            'KB history load test'
        );
        $this->addOption(
            'authors',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of distinct users the generated events are attributed to',
            '5'
        );
        $this->addOption(
            'languages',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of translations the generated events are spread over',
            '3'
        );
        $this->addOption(
            'seed',
            null,
            InputOption::VALUE_REQUIRED,
            'Random seed, to get a reproducible data set',
            '1'
        );
        $this->addOption(
            'purge',
            null,
            InputOption::VALUE_NONE,
            'Delete the generated history instead of creating it: history rows only when --article is given,'
            . ' the whole article otherwise'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $input->getOption('purge')
            ? $this->purge()
            : $this->generate();
    }

    private function generate(): int
    {
        $count     = (int) $this->input->getOption('count');
        $seed      = (int) $this->input->getOption('seed');
        $languages = (int) $this->input->getOption('languages');
        $authors   = (int) $this->input->getOption('authors');

        if ($languages < 1 || $languages > count(self::PREFERRED_LANGUAGES)) {
            $this->output->writeln(
                sprintf(
                    '<error>The --languages option must be between 1 and %d.</error>',
                    count(self::PREFERRED_LANGUAGES)
                )
            );
            return Command::FAILURE;
        }
        if ($authors < 1) {
            $this->output->writeln('<error>The --authors option must be greater than 0.</error>');
            return Command::FAILURE;
        }

        mt_srand($seed);

        $this->authors = $this->findAuthors($authors);
        if ($this->authors === []) {
            $this->output->writeln('<error>No user found to use as author of the generated events.</error>');
            return Command::FAILURE;
        }

        $article_id = $this->findTargetArticleId();
        if ($article_id === false) {
            return Command::FAILURE;
        }

        $this->languages = $this->pickLanguages($languages, $article_id);
        if ($this->languages === []) {
            $this->output->writeln(
                '<error>Every usable language is already translated on the target article.'
                . ' Use another article, or --purge it first.</error>'
            );
            return Command::FAILURE;
        }
        if (count($this->languages) < $languages) {
            $this->output->writeln(
                sprintf(
                    '<comment>Only %d language(s) are left on the target article, the others are'
                    . ' already translated.</comment>',
                    count($this->languages)
                )
            );
        }

        // One "Current version" event, plus one "Current version" event per
        // translation: those are not spread over the sources, they exist once
        // and for all.
        $fixed_events = 1 + count($this->languages);
        $min_count    = $fixed_events + count(self::WEIGHTS);
        if ($count < $min_count) {
            $this->output->writeln(
                sprintf(
                    '<error>The --count option must be at least %d, so every history source gets'
                    . ' at least one event.</error>',
                    $min_count
                )
            );
            return Command::FAILURE;
        }

        $distribution = $this->buildDistribution($count - $fixed_events);

        $this->output->writeln(
            sprintf(
                '<info>About to create %d history events on %s.</info>',
                $count,
                $article_id === null
                    ? sprintf('a new article named "%s"', (string) $this->input->getOption('name'))
                    : sprintf('article #%d', $article_id)
            )
        );
        $this->outputDistribution($distribution, $fixed_events);
        $this->warnAboutExecutionTime();
        $this->askForConfirmation();

        $this->reference_time = time();
        $this->total_events   = $count;
        $this->illustrations  = $this->findIllustrationIds();
        $this->item_types     = $this->findAssociatedItemTypes();
        $this->counters       = array_fill_keys(array_keys(self::WEIGHTS), 0);

        $progress_bar = new ProgressBar($this->output, $count);
        $progress_bar->setRedrawFrequency(max(1, intdiv($count, 100)));
        $progress_bar->start();

        $this->beginTransaction();
        try {
            $this->article_id = $article_id ?? $this->insertArticle();

            $this->prepareStatements();
            $this->revision_numbers = $this->findLastRevisionNumbers($this->article_id);

            // Events are written from the oldest to the most recent one, so the
            // revision numbers grow with time, as they would on a real article.
            $index = 0;
            foreach ($this->buildPlan($distribution) as $source) {
                $this->addEvent($source, $this->dateAt($index));
                $index++;
                $progress_bar->advance();
            }

            // The current version of each translation, then the current version
            // of the article itself: the most recent events of the history.
            foreach ($this->languages as $language) {
                $this->addCurrentTranslation($language, $this->dateAt($index));
                $index++;
                $progress_bar->advance();
            }
            $this->addCurrentVersionLog($this->dateAt($index));
            $progress_bar->advance();

            $this->commitTransaction();
        } catch (Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        $progress_bar->finish();
        $this->output->write(PHP_EOL);

        $this->output->writeln(
            sprintf('<info>%d history events created.</info>', $count),
            OutputInterface::VERBOSITY_QUIET
        );
        $this->output->writeln(
            sprintf('<info>Article: %s</info>', $this->getArticleUrl($this->article_id)),
            OutputInterface::VERBOSITY_QUIET
        );

        return Command::SUCCESS;
    }

    /**
     * Delete the history rows of the article given by `--article`, or the
     * articles named `--name` attached to the knowledge base root and
     * everything hanging on them.
     */
    private function purge(): int
    {
        $article_option = $this->input->getOption('article');
        if ($article_option !== null) {
            $article_id = (int) $article_option;
            $article    = new KnowbaseItem();
            if (!$article->getFromDB($article_id)) {
                $this->output->writeln(
                    sprintf('<error>No article found with the #%d id.</error>', $article_id)
                );
                return Command::FAILURE;
            }

            $this->output->writeln(
                sprintf(
                    '<comment>You are about to delete the whole history (revisions, translations and logs)'
                    . ' of article #%d "%s". Its content is kept, but every past version is lost.</comment>',
                    $article_id,
                    $article->fields['name']
                )
            );
            if (!$this->input->getOption('no-interaction')) {
                // Deleting a history is not reversible, so an interactive run
                // has to confirm it. `--no-interaction` is an explicit
                // "go ahead".
                $this->askForConfirmation(false);
            }

            $this->deleteHistoryRows([$article_id]);
            $this->output->writeln(
                sprintf('<info>History of article #%d deleted.</info>', $article_id),
                OutputInterface::VERBOSITY_QUIET
            );

            return Command::SUCCESS;
        }

        $name = trim((string) $this->input->getOption('name'));
        $ids  = $this->findGeneratedArticleIds($name);
        if ($ids === []) {
            $this->output->writeln(
                sprintf('<info>No article named "%s" found below the knowledge base root.</info>', $name),
                OutputInterface::VERBOSITY_QUIET
            );
            return Command::SUCCESS;
        }

        $this->output->writeln(
            sprintf(
                '<comment>You are about to delete %d article(s) named "%s" and their whole history.</comment>',
                count($ids),
                $name
            )
        );
        if (!$this->input->getOption('no-interaction')) {
            $this->askForConfirmation(false);
        }

        $db = $this->getDb();
        foreach (array_chunk($ids, self::PURGE_CHUNK_SIZE) as $chunk) {
            $this->deleteHistoryRows($chunk);
            $db->delete(Entity_KnowbaseItem::getTable(), ['knowbaseitems_id' => $chunk]);
            $db->delete(KnowbaseItem_KnowbaseItem::getTable(), [
                'OR' => [
                    'knowbaseitems_id'        => $chunk,
                    'knowbaseitems_id_parent' => $chunk,
                ],
            ]);
            $db->delete(KnowbaseItem::getTable(), ['id' => $chunk]);
        }

        $this->output->writeln(
            sprintf('<info>%d article(s) deleted.</info>', count($ids)),
            OutputInterface::VERBOSITY_QUIET
        );

        return Command::SUCCESS;
    }

    /**
     * Delete everything the history builder reads for the given articles.
     *
     * @param int[] $article_ids
     */
    private function deleteHistoryRows(array $article_ids): void
    {
        $db = $this->getDb();

        $db->delete(KnowbaseItem_Revision::getTable(), ['knowbaseitems_id' => $article_ids]);
        $db->delete(KnowbaseItemTranslation::getTable(), ['knowbaseitems_id' => $article_ids]);
        $db->delete(Log::getTable(), [
            'itemtype' => KnowbaseItem::class,
            'items_id' => $article_ids,
        ]);
    }

    /**
     * Id of the article to add the history to, `null` when a new article has to
     * be created, or `false` when the `--article` option does not match any
     * article.
     */
    private function findTargetArticleId(): int|false|null
    {
        $article_option = $this->input->getOption('article');
        if ($article_option === null) {
            if (!KnowbaseItem::hasRoot()) {
                $this->output->writeln(
                    '<error>The knowledge base has no root article,'
                    . ' run the database installation/update first.</error>'
                );
                return false;
            }
            return null;
        }

        $article_id = (int) $article_option;
        $article    = new KnowbaseItem();
        if (!$article->getFromDB($article_id)) {
            $this->output->writeln(
                sprintf('<error>No article found with the #%d id.</error>', $article_id)
            );
            return false;
        }

        $this->article_name = (string) $article->fields['name'];

        $existing = $this->countExistingEvents($article_id);
        if ($existing > 0) {
            $this->output->writeln(
                sprintf(
                    '<comment>Article #%d already has about %d history event(s); the generated ones are'
                    . ' added on top of them. Note that the generated "Current version" event replaces the'
                    . ' existing one, as only the most recent content update is displayed, so the article'
                    . ' ends up with one event less than the sum of both runs.</comment>',
                    $article_id,
                    $existing
                )
            );
        }

        return $article_id;
    }

    /**
     * Rough number of history events the given article already has. Only used
     * to warn the user, so log rows the builder ignores are not filtered out.
     */
    private function countExistingEvents(int $article_id): int
    {
        $db = $this->getDb();

        $count = 0;
        foreach ([KnowbaseItem_Revision::getTable(), KnowbaseItemTranslation::getTable()] as $table) {
            $count += (int) $db->request([
                'COUNT' => 'cpt',
                'FROM'  => $table,
                'WHERE' => ['knowbaseitems_id' => $article_id],
            ])->current()['cpt'];
        }

        return $count + (int) $db->request([
            'COUNT' => 'cpt',
            'FROM'  => Log::getTable(),
            'WHERE' => [
                'itemtype' => KnowbaseItem::class,
                'items_id' => $article_id,
            ],
        ])->current()['cpt'];
    }

    /**
     * Number of events to create per source: the `WEIGHTS` ratios applied to
     * the requested count, with at least one event per source.
     *
     * @return array<string, int>
     */
    private function buildDistribution(int $count): array
    {
        $total_weight = array_sum(self::WEIGHTS);

        $distribution = [];
        foreach (self::WEIGHTS as $source => $weight) {
            $distribution[$source] = max(1, (int) floor($count * $weight / $total_weight));
        }

        // Rounding, and the "at least one" floor, leave a gap with the
        // requested count: it is taken on (or given to) the heaviest source, so
        // the total is exactly the requested one.
        $heaviest = array_key_first(self::WEIGHTS);
        $distribution[$heaviest] += $count - array_sum($distribution);
        if ($distribution[$heaviest] < 1) {
            // Cannot happen with the minimal count enforced by generate(), but
            // a silently negative count would be a nightmare to debug.
            throw new LogicException(
                sprintf('%d events cannot be spread over %d sources', $count, count(self::WEIGHTS))
            );
        }

        return $distribution;
    }

    /**
     * The sources of every event to create, in a random order, so events of all
     * kinds are interleaved over the whole date window.
     *
     * @param array<string, int> $distribution
     *
     * @return string[]
     */
    private function buildPlan(array $distribution): array
    {
        $plan = [];
        foreach ($distribution as $source => $events) {
            for ($i = 0; $i < $events; $i++) {
                $plan[] = $source;
            }
        }
        shuffle($plan);

        return $plan;
    }

    private function addEvent(string $source, string $date): void
    {
        $this->counters[$source]++;

        match ($source) {
            self::SOURCE_REVISION             => $this->addRevision($date, ''),
            self::SOURCE_TRANSLATION_REVISION => $this->addRevision($date, $this->pickLanguage()),
            self::SOURCE_NAME                 => $this->addNameChange($date),
            self::SOURCE_FAQ                  => $this->addFaqChange($date),
            self::SOURCE_SERVICE_CATALOG      => $this->addServiceCatalogChange($date),
            self::SOURCE_ASSOCIATED_ITEM      => $this->addAssociatedItemChange($date),
            self::SOURCE_CATEGORY             => $this->addCategoryChange($date),
            self::SOURCE_DOCUMENT             => $this->addDocumentChange($date),
            self::SOURCE_PERMISSION           => $this->addPermissionChange($date),
            self::SOURCE_SHARING              => $this->addSharingChange($date),
            self::SOURCE_ILLUSTRATION         => $this->addIllustrationChange($date),
            default => throw new LogicException(sprintf('Unknown history source "%s"', $source)),
        };
    }

    /**
     * A content revision of the article, or of one of its translations when a
     * language is given.
     */
    private function addRevision(string $date, string $language): void
    {
        [$author_id, ] = $this->pickAuthor();

        // Revision numbers are sequential per language, as
        // `KnowbaseItem_Revision::getNewRevision()` computes them.
        $revision = ($this->revision_numbers[$language] ?? 0) + 1;
        $this->revision_numbers[$language] = $revision;

        $this->getDb()->executeStatement($this->revision_stmt, [
            $this->article_id,
            $revision,
            sprintf('%s (v%d)', $this->article_name, $revision),
            $this->buildAnswer($revision),
            $language,
            $author_id,
            $date,
        ]);
        $this->onRowsInserted(1);
    }

    private function addCurrentTranslation(string $language, string $date): void
    {
        [$author_id, ] = $this->pickAuthor();

        $this->getDb()->executeStatement($this->translation_stmt, [
            $this->article_id,
            $language,
            sprintf('%s [%s]', $this->article_name, $language),
            $this->buildAnswer(0),
            $author_id,
            $date,
            $date,
        ]);
        $this->onRowsInserted(1);
    }

    /**
     * The log row read as the current version of the article. Only the most
     * recent one is displayed, so a single row is written.
     */
    private function addCurrentVersionLog(string $date): void
    {
        $this->addLog(
            date: $date,
            id_search_option: self::SEARCH_OPTION_ANSWER,
            old_value: $this->buildSentence(mt_rand(6, 12)),
            new_value: $this->buildSentence(mt_rand(6, 12)),
        );
    }

    private function addNameChange(string $date): void
    {
        $revision = $this->counters[self::SOURCE_NAME];

        $this->addLog(
            date: $date,
            id_search_option: self::SEARCH_OPTION_NAME,
            old_value: sprintf('%s (rev. %d)', $this->article_name, $revision),
            new_value: sprintf('%s (rev. %d)', $this->article_name, $revision + 1),
        );
    }

    private function addFaqChange(string $date): void
    {
        // The article is alternatively added to, then removed from the FAQ, so
        // both descriptions are rendered.
        $added = $this->counters[self::SOURCE_FAQ] % 2 === 1;

        $this->addLog(
            date: $date,
            id_search_option: self::SEARCH_OPTION_FAQ,
            old_value: $added ? '0' : '1',
            new_value: $added ? '1' : '0',
        );
    }

    private function addServiceCatalogChange(string $date): void
    {
        // Cycle over the four service catalog fields: each one renders a
        // different description, and two of them also render their values.
        $counter          = $this->counters[self::SOURCE_SERVICE_CATALOG] - 1;
        $id_search_option = self::SEARCH_OPTIONS_SERVICE_CATALOG[
            $counter % count(self::SEARCH_OPTIONS_SERVICE_CATALOG)
        ];
        $enabled = intdiv($counter, count(self::SEARCH_OPTIONS_SERVICE_CATALOG)) % 2 === 0;

        [$old_value, $new_value] = match ($id_search_option) {
            84, 85  => $enabled ? ['0', '1'] : ['1', '0'],
            86      => [$this->buildSentence(mt_rand(6, 10)), $this->buildSentence(mt_rand(6, 10))],
            default => [$this->pickCategoryName(), $this->pickCategoryName()], // 87
        };

        $this->addLog(
            date: $date,
            id_search_option: $id_search_option,
            old_value: $old_value,
            new_value: $new_value,
        );
    }

    private function addIllustrationChange(string $date): void
    {
        // The three shapes of the new value render a different description:
        // removed, custom illustration, native illustration.
        $new_value = match (($this->counters[self::SOURCE_ILLUSTRATION] - 1) % 3) {
            0       => $this->pickIllustration(),
            1       => IllustrationManager::CUSTOM_ILLUSTRATION_PREFIX . 'load-test-illustration.png',
            default => '',
        };

        $this->addLog(
            date: $date,
            id_search_option: self::SEARCH_OPTION_ILLUSTRATION,
            old_value: '',
            new_value: $new_value,
        );
    }

    private function addAssociatedItemChange(string $date): void
    {
        $counter   = $this->counters[self::SOURCE_ASSOCIATED_ITEM];
        $itemtype  = $this->item_types[$counter % count($this->item_types)];
        $item_id   = mt_rand(1, 5000);
        $item_name = sprintf('%s-%05d', strtoupper(substr($itemtype, 0, 3)), $item_id);

        $this->addRelationLog($date, $itemtype, $item_id, $item_name, $counter);
    }

    private function addCategoryChange(string $date): void
    {
        $counter = $this->counters[self::SOURCE_CATEGORY];

        $this->addRelationLog(
            $date,
            self::LEGACY_CATEGORY_ITEMTYPE,
            mt_rand(1, 50),
            $this->pickCategoryName(),
            $counter
        );
    }

    private function addDocumentChange(string $date): void
    {
        $counter = $this->counters[self::SOURCE_DOCUMENT];

        $this->addRelationLog(
            $date,
            Document::class,
            mt_rand(1, 5000),
            sprintf('%s.pdf', $this->buildSlug(mt_rand(2, 4))),
            $counter
        );
    }

    private function addPermissionChange(string $date): void
    {
        $counter  = $this->counters[self::SOURCE_PERMISSION];
        $itemtype = self::PERMISSION_TYPES[$counter % count(self::PERMISSION_TYPES)];
        $target   = mt_rand(1, 500);

        $this->addRelationLog(
            $date,
            $itemtype,
            $target,
            sprintf('%s %d', $itemtype::getTypeName(1), $target),
            $counter
        );
    }

    private function addSharingChange(string $date): void
    {
        // Sharing has a third action, the regeneration of the link, so the
        // add/delete alternation of the other relations does not apply.
        $linked_action = match (($this->counters[self::SOURCE_SHARING] - 1) % 3) {
            0       => Log::HISTORY_ADD_RELATION,
            1       => Log::HISTORY_UPDATE_RELATION,
            default => Log::HISTORY_DEL_RELATION,
        };

        $token_id = mt_rand(1, 500);

        $this->addLog(
            date: $date,
            linked_action: $linked_action,
            itemtype_link: ShareToken::class,
            old_id: $linked_action === Log::HISTORY_DEL_RELATION ? $token_id : 0,
            new_id: $linked_action === Log::HISTORY_DEL_RELATION ? 0 : $token_id,
        );
    }

    /**
     * A relation log, alternatively an addition and a deletion, so both
     * descriptions of the source are rendered.
     */
    private function addRelationLog(
        string $date,
        string $itemtype,
        int $item_id,
        string $item_name,
        int $counter,
    ): void {
        $is_add = $counter % 2 === 1;

        $this->addLog(
            date: $date,
            linked_action: $is_add ? Log::HISTORY_ADD_RELATION : Log::HISTORY_DEL_RELATION,
            itemtype_link: $itemtype,
            old_value: $is_add ? '' : $item_name,
            new_value: $is_add ? $item_name : '',
            old_id: $is_add ? 0 : $item_id,
            new_id: $is_add ? $item_id : 0,
        );
    }

    private function addLog(
        string $date,
        int $linked_action = 0,
        string $itemtype_link = '',
        int $id_search_option = 0,
        string $old_value = '',
        string $new_value = '',
        int $old_id = 0,
        int $new_id = 0,
    ): void {
        [, $author_name] = $this->pickAuthor();

        $this->getDb()->executeStatement($this->log_stmt, [
            KnowbaseItem::class,
            $this->article_id,
            $itemtype_link,
            $linked_action,
            $author_name,
            $date,
            $id_search_option,
            // `Log::history()` truncates values to the column length, do the
            // same so the generated rows cannot be rejected.
            mb_substr($old_value, 0, 255),
            mb_substr($new_value, 0, 255),
            $old_id,
            $new_id,
        ]);
        $this->onRowsInserted(1);
    }

    /**
     * Date of the event at the given index: events are spread evenly over the
     * `DATE_SPREAD_DAYS` window, from the oldest to the most recent one.
     */
    private function dateAt(int $index): string
    {
        $span = self::DATE_SPREAD_DAYS * DAY_TIMESTAMP;
        $step = $span / max(1, $this->total_events);

        return date('Y-m-d H:i:s', $this->reference_time - $span + (int) round($index * $step));
    }

    /**
     * Insert the article the generated history is attached to, and make it
     * visible to everybody in the root entity.
     */
    private function insertArticle(): int
    {
        $db = $this->getDb();

        $this->article_name = trim((string) $this->input->getOption('name'));
        [$author_id, ]      = $this->pickAuthor();
        $created            = $this->dateAt(0);

        $db->insert(KnowbaseItem::getTable(), [
            'entities_id'   => 0,
            'is_recursive'  => 1,
            'name'          => $this->article_name,
            'answer'        => $this->buildAnswer(0),
            'description'   => $this->buildSentence(mt_rand(8, 14)),
            'illustration'  => $this->pickIllustration(),
            'is_faq'        => 0,
            'is_pinned'     => 0,
            'users_id'      => $author_id,
            'view'          => 0,
            'date_creation' => $created,
            'date_mod'      => $this->dateAt($this->total_events),
        ]);
        $article_id = (int) $db->insertId();

        $db->insert(KnowbaseItem_KnowbaseItem::getTable(), [
            'knowbaseitems_id'        => $article_id,
            'knowbaseitems_id_parent' => KnowbaseItem::getRootId(),
        ]);
        $db->insert(Entity_KnowbaseItem::getTable(), [
            'knowbaseitems_id' => $article_id,
            'entities_id'      => 0,
            'is_recursive'     => 1,
        ]);

        $this->onRowsInserted(3);

        return $article_id;
    }

    /**
     * Highest revision number already used per language on the given article,
     * so the generated revisions continue the existing sequence instead of
     * colliding with it on the `knowbaseitems_id, revision, language` unicity
     * key.
     *
     * @return array<string, int>
     */
    private function findLastRevisionNumbers(int $article_id): array
    {
        $numbers = [];
        foreach ($this->getDb()->request([
            'SELECT' => ['language', 'MAX' => 'revision AS revision'],
            'FROM'   => KnowbaseItem_Revision::getTable(),
            'WHERE'  => ['knowbaseitems_id' => $article_id],
            'GROUPBY' => 'language',
        ]) as $row) {
            $numbers[(string) $row['language']] = (int) $row['revision'];
        }

        return $numbers;
    }

    /**
     * @return array<int, string> Author ids mapped to the name written in the
     *                           `glpi_logs` rows.
     */
    private function findAuthors(int $limit): array
    {
        $authors = [];
        foreach ($this->getDb()->request([
            'SELECT' => 'id',
            'FROM'   => User::getTable(),
            'WHERE'  => ['is_deleted' => 0],
            'ORDER'  => 'id ASC',
            'LIMIT'  => $limit,
        ]) as $row) {
            $id = (int) $row['id'];
            // `LogEvent::getAuthor()` reads the author id back from that name,
            // so it has to be built the way `Log::history()` does.
            $authors[$id] = User::getNameForLog($id);
        }

        return $authors;
    }

    /** @return array{int, string} Id and log name of the next author. */
    private function pickAuthor(): array
    {
        $ids = array_keys($this->authors);
        $id  = $ids[$this->author_cursor % count($ids)];
        $this->author_cursor++;

        return [$id, $this->authors[$id]];
    }

    /**
     * Languages the translations are created for: the ones this GLPI version
     * supports, minus the ones the target article is already translated in, as
     * an article holds a single translation per language.
     *
     * @return string[]
     */
    private function pickLanguages(int $count, ?int $article_id): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $available = array_intersect(
            self::PREFERRED_LANGUAGES,
            array_keys($CFG_GLPI['languages'])
        );

        if ($article_id !== null) {
            $available = array_diff($available, $this->findTranslatedLanguages($article_id));
        }

        return array_slice(array_values($available), 0, $count);
    }

    /**
     * @return string[] Languages the given article is already translated in.
     */
    private function findTranslatedLanguages(int $article_id): array
    {
        $languages = [];
        foreach ($this->getDb()->request([
            'SELECT' => 'language',
            'FROM'   => KnowbaseItemTranslation::getTable(),
            'WHERE'  => ['knowbaseitems_id' => $article_id],
        ]) as $row) {
            $languages[] = (string) $row['language'];
        }

        return $languages;
    }

    /** Language of the next translation revision, cycling over the languages. */
    private function pickLanguage(): string
    {
        return $this->languages[
            $this->counters[self::SOURCE_TRANSLATION_REVISION] % count($this->languages)
        ];
    }

    /**
     * @return string[] Itemtypes an article can be linked to, minus the ones
     *                  read as permissions by the history builder.
     */
    private function findAssociatedItemTypes(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return array_values(array_diff($CFG_GLPI['kb_types'], self::PERMISSION_TYPES));
    }

    /**
     * @return string[] Native illustration ids, empty when the front-end assets
     *                  holding their definitions are not available.
     */
    private function findIllustrationIds(): array
    {
        try {
            return (new IllustrationManager())->getAllIconsIds();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * `DBmysql::buildInsert()` turns every value into a placeholder, so all
     * columns are bound, including the ones that never change from a row to the
     * next.
     */
    private function prepareStatements(): void
    {
        $db = $this->getDb();

        $this->revision_stmt = $db->prepare(
            $db->buildInsert(KnowbaseItem_Revision::getTable(), [
                'knowbaseitems_id' => new QueryParam(),
                'revision'         => new QueryParam(),
                'name'             => new QueryParam(),
                'answer'           => new QueryParam(),
                'language'         => new QueryParam(),
                'users_id'         => new QueryParam(),
                'date'             => new QueryParam(),
            ])
        );

        $this->translation_stmt = $db->prepare(
            $db->buildInsert(KnowbaseItemTranslation::getTable(), [
                'knowbaseitems_id' => new QueryParam(),
                'language'         => new QueryParam(),
                'name'             => new QueryParam(),
                'answer'           => new QueryParam(),
                'users_id'         => new QueryParam(),
                'date_mod'         => new QueryParam(),
                'date_creation'    => new QueryParam(),
            ])
        );

        $this->log_stmt = $db->prepare(
            $db->buildInsert(Log::getTable(), [
                'itemtype'         => new QueryParam(),
                'items_id'         => new QueryParam(),
                'itemtype_link'    => new QueryParam(),
                'linked_action'    => new QueryParam(),
                'user_name'        => new QueryParam(),
                'date_mod'         => new QueryParam(),
                'id_search_option' => new QueryParam(),
                'old_value'        => new QueryParam(),
                'new_value'        => new QueryParam(),
                'old_id'           => new QueryParam(),
                'new_id'           => new QueryParam(),
            ])
        );
    }

    /**
     * Ids of the articles named $name that are attached to the knowledge base
     * root, i.e. the articles a previous run created.
     *
     * @return int[]
     */
    private function findGeneratedArticleIds(string $name): array
    {
        $link_table = KnowbaseItem_KnowbaseItem::getTable();

        $ids = [];
        foreach ($this->getDb()->request([
            'SELECT'     => KnowbaseItem::getTableField('id'),
            'FROM'       => KnowbaseItem::getTable(),
            'INNER JOIN' => [
                $link_table => [
                    'ON' => [
                        $link_table              => 'knowbaseitems_id',
                        KnowbaseItem::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                KnowbaseItem::getTableField('name') => $name,
                KnowbaseItem_KnowbaseItem::getTableField('knowbaseitems_id_parent')
                    => KnowbaseItem::getRootId(),
            ],
        ]) as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    /** @param array<string, int> $distribution */
    private function outputDistribution(array $distribution, int $fixed_events): void
    {
        $lines = $distribution;
        // Not part of the distribution: they exist once per article and per
        // translation, whatever the requested count is.
        $lines['current_version']      = 1;
        $lines['current_translations'] = $fixed_events - 1;

        foreach ($lines as $source => $events) {
            $this->output->writeln(sprintf('  %-22s %8d', $source, $events));
        }
        $this->output->writeln(
            sprintf(
                '<info>Translations: %s.</info>',
                $this->languages === [] ? 'none' : implode(', ', $this->languages)
            )
        );
    }

    private function pickIllustration(): string
    {
        if ($this->illustrations === []) {
            return '';
        }

        return $this->illustrations[mt_rand(0, count($this->illustrations) - 1)];
    }

    private function pickCategoryName(): string
    {
        return self::CATEGORY_NAMES[mt_rand(0, count(self::CATEGORY_NAMES) - 1)];
    }

    /**
     * Content of a revision. Revisions are stored in full, so they are kept
     * short here: 100 000 real sized articles would weight gigabytes, and the
     * history panel does not read the content anyway.
     */
    private function buildAnswer(int $revision): string
    {
        return sprintf(
            '<p>%s</p><p>%s</p>',
            $revision > 0 ? sprintf('Revision %d.', $revision) : 'Current version.',
            $this->buildSentence(mt_rand(12, 24))
        );
    }

    private function buildSentence(int $words, bool $with_dot = true): string
    {
        $picked = [];
        for ($i = 0; $i < $words; $i++) {
            $picked[] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
        }

        return ucfirst(implode(' ', $picked)) . ($with_dot ? '.' : '');
    }

    private function buildSlug(int $words): string
    {
        $picked = [];
        for ($i = 0; $i < $words; $i++) {
            $picked[] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
        }

        return implode('-', $picked);
    }

    private function getArticleUrl(int $id): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // `KnowbaseItem::getFormURL()` picks the URL from the current session
        // interface, which is not set in a CLI context and would fall back to
        // the helpdesk FAQ. The generated data is meant to be browsed from the
        // central interface, so its URL is built here.
        return sprintf(
            '%s%s/front/knowbaseitem.form.php?id=%d',
            $CFG_GLPI['url_base'],
            $CFG_GLPI['root_doc'],
            $id
        );
    }

    /**
     * Commit the current transaction and start a new one once enough rows have
     * been inserted.
     */
    private function onRowsInserted(int $rows): void
    {
        $this->pending_rows += $rows;
        if ($this->pending_rows < self::COMMIT_EVERY) {
            return;
        }

        $this->commitTransaction();
        $this->beginTransaction();
    }

    private function beginTransaction(): void
    {
        $this->getDb()->beginTransaction();
        $this->in_transaction = true;
        $this->pending_rows   = 0;
    }

    private function commitTransaction(): void
    {
        $this->getDb()->commit();
        $this->in_transaction = false;
        $this->pending_rows   = 0;
    }

    private function rollbackTransaction(): void
    {
        if (!$this->in_transaction) {
            return;
        }

        $this->getDb()->rollBack();
        $this->in_transaction = false;
        $this->pending_rows   = 0;
    }
}
