<?php namespace Ssddanbrown\HtmlDiff;

/**
 * Finds the longest match in given texts.
 * It uses indexing with fixed granularity that is used to compare blocks of text.
 */
final class MatchFinder
{
    /**
     * @var string[]
     */
    private $oldWords;

    /**
     * @var string[]
     */
    private $newWords;

    /**
     * @var int
     */
    private $startInOld;

    /**
     * @var int
     */
    private $endInOld;

    /**
     * @var int
     */
    private $startInNew;

    /**
     * @var int
     */
    private $endInNew;

    /**
     * @var array<String, array<int>>
     */
    private $wordIndices = [];

    private $options;

    /**
     * @param string[] $oldWords
     * @param string[] $newWords
     */
    public function __construct(
        array $oldWords,
        array $newWords,
        int $startInOld,
        int $endInOld,
        int $startInNew,
        int $endInNew,
        MatchOptions $options
    )
    {
        $this->oldWords = $oldWords;
        $this->newWords = $newWords;
        $this->startInOld = $startInOld;
        $this->endInOld = $endInOld;
        $this->startInNew = $startInNew;
        $this->endInNew = $endInNew;
        $this->options = $options;
    }

    private function indexNewWords(): void
    {
        $this->wordIndices = [];
        $block = [];
        for ($i = $this->startInNew; $i < $this->endInNew; $i++) {
            // if word is a tag, we should ignore attributes as attribute changes are not supported (yet)
            $word = $this->normalizeForIndex($this->newWords[$i]);
            $key = $this->putNewWord($block, $word, $this->options->blockSize);

            if (is_null($key)) continue;

            if (isset($this->wordIndices[$key])) {
                $this->wordIndices[$key][] = $i;
            } else {
                $this->wordIndices[$key] = [$i];
            }
        }
    }

    /**
     * @param string[] $block
     */
    private static function putNewWord(array &$block, string $word, int $blockSize): ?string
    {
        array_push($block, $word);
        if (count($block) > $blockSize) {
            array_shift($block);
        }

        if (count($block) !== $blockSize) {
            return null;
        }

        return implode('', $block);
    }

    private function normalizeForIndex(string $word): string
    {
        $word = Utils::stripAnyAttributes($word);
        if ($this->options->ignoreWhitespaceDifferences && Utils::isWhiteSpace($word)) {
            return " ";
        }

        return $word;
    }

    public function findMatch(): ?DiffMatch
    {
        $this->indexNewWords();
        $this->removeRepeatingWords();

        if (count($this->wordIndices) === 0) {
            return null;
        }

        $bestMatchInOld = $this->startInOld;
        $bestMatchInNew = $this->startInNew;
        $bestMatchSize = 0;

        /** @var int[] $matchLengthAt */
        $matchLengthAt = [];
        /** @var string[] $block */
        $block = [];

        for ($indexInOld = $this->startInOld; $indexInOld < $this->endInOld; $indexInOld++) {
            $word = $this->normalizeForIndex($this->oldWords[$indexInOld]);
            $index = $this->putNewWord($block, $word, $this->options->blockSize);

            if (is_null($index)) {
                continue;
            }
            /** @var int[] $newMatchLengthAt */
            $newMatchLengthAt = [];

            if (!isset($this->wordIndices[$index])) {
                $matchLengthAt = $newMatchLengthAt;
                continue;
            }

            foreach ($this->wordIndices[$index] as $indexInNew) {
                $newMatchLength = (isset($matchLengthAt[$indexInNew - 1]) ? $matchLengthAt[$indexInNew - 1] : 0) + 1;
                $newMatchLengthAt[$indexInNew] = $newMatchLength;

                if ($newMatchLength > $bestMatchSize) {
                    $bestMatchInOld = $indexInOld - $newMatchLength + 1 - $this->options->blockSize + 1;
                    $bestMatchInNew = $indexInNew - $newMatchLength + 1 - $this->options->blockSize + 1;
                    $bestMatchSize = $newMatchLength;
                }
            }

            $matchLengthAt = $newMatchLengthAt;
        }

        return ($bestMatchSize !== 0) ? new DiffMatch($bestMatchInOld, $bestMatchInNew, $bestMatchSize + $this->options->blockSize - 1) : null;
    }

    /**
     * This method removes words that occur too many times. This way it reduces total count
     * of comparison operations and as result the diff algorithm takes less time.
     * But the side effect is that it may detect false differences of the repeating words.
     */
    private function removeRepeatingWords(): void
    {
        $threshold = count($this->newWords) * $this->options->repeatingWordsAccuracy;
        $repeatingWords = array_keys(array_filter($this->wordIndices, function($v) use ($threshold) {
            return count($v) > $threshold;
        }));
        foreach ($repeatingWords as $word) {
            unset($this->wordIndices[$word]);
        }
    }

}