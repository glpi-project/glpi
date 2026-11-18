<?php namespace Ssddanbrown\HtmlDiff;

use Generator;

/**
 * @psalm-consistent-constructor
 */
class Diff
{

    /**
     * This value defines balance between speed and memory utilization.
     * The higher it is the faster it works and more memory consumes.
     * @var int
     */
    private $matchGranularityMaximum = 4;

    /**
     * @var string
     */
    private $content = "";

    /**
     * @var string
     */
    private $newText;

    /**
     * @var string
     */
    private $oldText;

    /**
     * @var array<string, int>
     */
    private static $specialCaseClosingTags = [
        '</strong>' => 0,
        '</em>' => 0,
        '</b>' => 0,
        '</i>' => 0,
        '</big>' => 0,
        '</small>' => 0,
        '</u>' => 0,
        '</sub>' => 0,
        '</sup>' => 0,
        '</strike>' => 0,
        '</s>' => 0,
    ];

    /**
     * @var string
     */
    private static $specialCaseOpeningTagRegex = '/<((strong)|(b)|(i)|(em)|(big)|(small)|(u)|(sub)|(sup)|(strike)|(s))[\>\s]+/i';

    /**
     * Tracks opening and closing formatting tags to ensure that we don't
     * inadvertently generate invalid html during the diff process.
     * @var string[]
     */
    private $specialTagDiffStack = [];

    /**
     * @var string[]
     */
    private $newWords = [];

    /**
     * @var string[]
     */
    private $oldWords = [];

    /**
     * @var int
     */
    private $matchGranularity = 1;

    /**
     * @var string[]
     */
    private $blockExpressions = [];

    /**
     * Defines how to compare repeating words. Valid values are from 0 to 1. This value allows to exclude
     * some words from comparison that eventually reduces the total time of the diff algorithm. 0 means
     * that all words are excluded so the diff will not find any matching words at all. 1 (default value)
     * means that all words participate in comparison so this is the most accurate case.
     * 0.5 means that any word that occurs more than 50% times may be excluded from comparison.
     * This doesn't mean that such words will definitely be excluded but only gives a permission
     * to exclude them if necessary.
     * @var double
     */
    public $repeatingWordsAccuracy = 1;

    /**
     * If true all whitespaces are considered as equal.
     * @var bool
     */
    public $ignoreWhitespaceDifferences = true;

    /**
     * If some match is too small and located far from its neighbors then it is considered as orphan
     * and removed. This property defines relative size of the match to be considered as orphan, from 0 to 1.
     * 1 means that all matches will be considered as orphans.
     * 0 (default) means that no match will be considered as orphan.
     * 0.2 means that if match length is less than 20% of distance between its neighbors it is considered as orphan.
     * @var double
     */
    public $orphanMatchThreshold = 0;


    public function __construct(string $oldText, string $newText)
    {
        $this->newText = $newText;
        $this->oldText = $oldText;
    }

    public static function excecute(string $oldText, string $newText): string
    {
        return (new self($oldText, $newText))->build();
    }

    /**
     * Builds the HTML diff output returning the diff HTML.
     */
    public function build(): string
    {
        if ($this->oldText === $this->newText) {
            return $this->newText;
        }

        $this->splitInputIntoWords();

        $this->matchGranularity = min($this->matchGranularityMaximum, min(count($this->oldWords), count($this->newWords)));

        $operations = $this->operations();

        foreach ($operations as $item) {
            $this->performOperation($item);
        }

        return $this->content;
    }

    /**
     * Uses the given $expression to group text together so that any change
     * detected within the group is treated as a single block.
     */
    public function addBlockExpression(string $expression): void
    {
        $this->blockExpressions[] = $expression;
    }

    private function splitInputIntoWords(): void
    {
        $this->oldWords = WordSplitter::convertHtmlToListOfWords($this->oldText, $this->blockExpressions);
        // free memory, allow it for GC
        $this->oldText = "";

        $this->newWords = WordSplitter::convertHtmlToListOfWords($this->newText, $this->blockExpressions);
        // free memory, allow it for GC
        $this->newText = "";
    }

    private function performOperation(Operation $operation): void
    {
        switch ($operation->action) {
            case Action::EQUAL:
                $this->processEqualOperation($operation);
                break;
            case Action::DELETE:
                $this->processDeleteOperation($operation, "diffdel");
                break;
            case Action::INSERT:
                $this->processInsertOperation($operation, "diffins");
                break;
            case Action::NONE:
                break;
            case Action::REPLACE:
                $this->processReplaceOperation($operation);
                break;
        }
    }

    private function processReplaceOperation(Operation $operation): void
    {
        $this->processDeleteOperation($operation, "diffmod");
        $this->processInsertOperation($operation, "diffmod");
    }

    private function processInsertOperation(Operation $operation, string $cssClass): void
    {
        $text = array_values(array_filter($this->newWords, function(string $s, int $pos) use ($operation) {
            return $pos >= $operation->startInNew && $pos < $operation->endInNew;
        }, ARRAY_FILTER_USE_BOTH));
        $this->insertTag("ins", $cssClass, $text);
    }

    private function processDeleteOperation(Operation $operation, string $cssClass): void
    {
        $text = array_values(array_filter($this->oldWords, function(string $s, int $pos) use ($operation) {
            return $pos >= $operation->startInOld && $pos < $operation->endInOld;
        }, ARRAY_FILTER_USE_BOTH));
        $this->insertTag("del", $cssClass, $text);
    }

    private function processEqualOperation(Operation $operation): void
    {
        $result = array_values(array_filter($this->newWords, function(string $s, int $pos) use ($operation) {
            return $pos >= $operation->startInNew && $pos < $operation->endInNew;
        }, ARRAY_FILTER_USE_BOTH));
        $this->content .= implode('', $result);
    }

    /**
     * This method encloses words within a specified tag (ins or del), and adds this into "content",
     * with a twist: if there are words contain tags, it actually creates multiple ins or del,
     * so that they don't include any ins or del. This still doesn't guarantee valid HTML
     * (hint: think about diffing a text containing ins or del tags), but handles correctly more cases
     * than the earlier version.
     * P.S.: Spare a thought for people who write HTML browsers. They live in this ... every day.
     * @param string[] $words
     */
    private function insertTag(string $tag, string $cssClass, array $words): void
    {
        while(true) {
            if (count($words) === 0) {
                break;
            }

            $nonTags = $this->extractConsecutiveWords($words, function(string $x) {
                return !Utils::isTag($x);
            });

            $specialCaseTagInjection = "";
            $specialCaseTagInjectionIsBefore = false;

            if (count($nonTags) !== 0) {
                $text = Utils::wrapText(implode('', $nonTags), $tag, $cssClass);
                $this->content .= $text;
            } else {
                // Check if the tag is a special case
                if (preg_match(static::$specialCaseOpeningTagRegex, $words[0]) === 1) {
                    $this->specialTagDiffStack[] = $words[0];
                    $specialCaseTagInjection = "<ins class=\"mod\">";
                    if ($tag === "del") {
                        array_shift($words);

                        // following tags may be formatting tags as well, follow through
                        while (count($words) > 0 && preg_match(static::$specialCaseOpeningTagRegex, $words[0]) === 1) {
                            array_shift($words);
                        }
                    }
                } else if (isset(static::$specialCaseClosingTags[strtolower($words[0])])) {
                    $openingTag = count($this->specialTagDiffStack) === 0 ? null : array_pop($this->specialTagDiffStack);

                    // If we didn't have an opening tag, and we don't have a match with the previous tag used
                    if (is_null($openingTag) || $openingTag !== str_replace('/', '', end($words))) {
                        // Do nothing
                    } else {
                        $specialCaseTagInjection = "</ins>";
                        $specialCaseTagInjectionIsBefore = true;
                    }

                    if ($tag === "del") {
                        array_shift($words);

                        // Following tags may be formatting tags as well, follow through
                        while (count($words) > 0 && isset(static::$specialCaseClosingTags[strtolower($words[0])])) {
                            array_shift($words);
                        }
                    }
                }
            }

            if (count($words) === 0 && strlen($specialCaseTagInjection) === 0) {
                break;
            }

            $isTagCallback = function(string $string): bool {
                return Utils::isTag($string);
            };
            if ($specialCaseTagInjectionIsBefore) {
                $this->content .= $specialCaseTagInjection . implode('', $this->extractConsecutiveWords($words, $isTagCallback));
            } else {
                $this->content .= implode('', $this->extractConsecutiveWords($words, $isTagCallback)) . $specialCaseTagInjection;
            }
        }
    }

    /**
     * @param string[] $words
     * @param callable(string):bool $condition
     * @return string[]
     */
    private function extractConsecutiveWords(array &$words, callable $condition): array
    {
        $indexOfFirstTag = null;

        for ($i = 0; $i < count($words); $i++) {
            $word = $words[$i];

            if ($i === 0 && $word == " ") {
                $words[$i] = "&nbsp;";
            }

            if (!$condition($word)) {
                $indexOfFirstTag = $i;
                break;
            }
        }

        if (!is_null($indexOfFirstTag)) {
            $items = array_values(array_filter($words, function(string $s, int $pos) use ($indexOfFirstTag) {
                return $pos >= 0 && $pos < $indexOfFirstTag;
            }, ARRAY_FILTER_USE_BOTH));
            if ($indexOfFirstTag > 0) {
                array_splice($words, 0, $indexOfFirstTag);
            }
            return $items;
        } else {
            $items = array_values(array_filter($words, function(string $s, int $pos) use ($words) {
                return $pos >= 0 && $pos <= count($words);
            }, ARRAY_FILTER_USE_BOTH));
            array_splice($words, 0, count($words));
            return $items;
        }
    }

    /**
     * @return Operation[]
     */
    private function operations(): array
    {
        $positionInOld = 0;
        $positionInNew = 0;
        $operations = [];

        $matches = $this->matchingBlocks();

        $matches[] = new DiffMatch(count($this->oldWords), count($this->newWords), 0);

        // Remove orphans from matches.
        // If distance between left and right matches is 4 times
        // longer than length of current match then it is considered as orphan.
        $matchesWithoutOrphans = $this->removeOrphans($matches);

        /** @var DiffMatch $match */
        foreach ($matchesWithoutOrphans as $match) {
            $matchStartsAtCurrentPositionInOld = ($positionInOld === $match->startInOld);
            $matchStartsAtCurrentPositionInNew = ($positionInNew === $match->startInNew);

            if (!$matchStartsAtCurrentPositionInOld && !$matchStartsAtCurrentPositionInNew) {
                $action = Action::REPLACE;
            } else if ($matchStartsAtCurrentPositionInOld && !$matchStartsAtCurrentPositionInNew) {
                $action = Action::INSERT;
            } else if (!$matchStartsAtCurrentPositionInOld) {
                $action = Action::DELETE;
            } else {
                $action = Action::NONE;
            }

            if ($action !== Action::NONE) {
                $operations[] = new Operation($action, $positionInOld, $match->startInOld, $positionInNew, $match->startInNew);
            }

            if ($match->size !== 0) {
                $operations[] = new Operation(Action::EQUAL, $match->startInOld, $match->getEndInOld(), $match->startInNew, $match->getEndInNew());
            }

            $positionInOld = $match->getEndInOld();
            $positionInNew = $match->getEndInNew();
        }

        return $operations;
    }

    /**
     * @param DiffMatch[] $matches
     */
    private function removeOrphans(array $matches): Generator
    {
        $prev = new DiffMatch(0, 0, 0);
        $curr = $matches[0] ?? new DiffMatch(0, 0, 0);

        foreach (array_slice($matches, 1) as $next) {
            // if match has no diff on the left or on the right
            if ($prev->getEndInOld() === $curr->startInOld && $prev->getEndInNew() === $curr->startInNew
                || $curr->getEndInOld() === $next->startInOld && $curr->getEndInNew() === $next->startInNew
            ) {
                yield $curr;
                $prev = $curr;
                $curr = $next;
                continue;
            }

            $oldDistanceInChars = array_sum(array_map(function($i) {
                return mb_strlen($this->oldWords[$i]);
            }, range($prev->getEndInOld(), $next->startInOld - 1)));
            $newDistanceInChars = array_sum(array_map(function($i) {
                return mb_strlen($this->newWords[$i]);
            }, range($prev->getEndInNew(), $next->startInNew - 1)));
            $currMatchLengthInChars = array_sum(array_map(function($i) {
                return mb_strlen($this->newWords[$i]);
            }, range($curr->startInNew, $curr->getEndInNew() - 1)));
            if ($currMatchLengthInChars > max($oldDistanceInChars, $newDistanceInChars) * $this->orphanMatchThreshold) {
                yield $curr;
            }

            $prev = $curr;
            $curr = $next;
        }

        yield $curr; //assume that the last match is always vital
    }

    /**
     * @return DiffMatch[]
     */
    private function matchingBlocks(): array
    {
        $matchingBlocks = [];
        $this->findMatchingBlocks(0, count($this->oldWords), 0, count($this->newWords), $matchingBlocks);
        /** @var DiffMatch[] $matchingBlocks */
        return $matchingBlocks;
    }

    /**
     * @param DiffMatch[] $matchingBlocks
     */
    private function findMatchingBlocks(int $startInOld, int $endInOld, int $startInNew, int $endInNew, array &$matchingBlocks): void
    {
        $match = $this->findMatch($startInOld, $endInOld, $startInNew, $endInNew);

        if (!is_null($match)) {
            if ($startInOld < $match->startInOld && $startInNew < $match->startInNew) {
                $this->findMatchingBlocks($startInOld, $match->startInOld, $startInNew, $match->startInNew, $matchingBlocks);
            }

            $matchingBlocks[] = $match;

            if ($match->getEndInOld() < $endInOld && $match->getEndInNew() < $endInNew) {
                $this->findMatchingBlocks($match->getEndInOld(), $endInOld, $match->getEndInNew(), $endInNew, $matchingBlocks);
            }
        }
    }

    private function findMatch(int $startInOld, int $endInOld, int $startInNew, int $endInNew): ?DiffMatch
    {
        // For large texts it is more likely that there is a Match of size bigger than maximum granularity.
        // If not then go down and try to find it with smaller granularity.
        for ($i = $this->matchGranularity; $i > 0; $i--) {
            $options = new MatchOptions($i, $this->repeatingWordsAccuracy, $this->ignoreWhitespaceDifferences);
            $finder = new MatchFinder($this->oldWords, $this->newWords, $startInOld, $endInOld, $startInNew, $endInNew, $options);
            $match = $finder->findMatch();
            if (!is_null($match)) {
                return $match;
            }
        }
        return null;
    }

}