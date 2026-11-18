<?php namespace Ssddanbrown\HtmlDiff;

class MatchOptions
{
    /**
     * Match granularity, defines how many words are joined into single block
     * @var int
     */
    public $blockSize;

    /**
     * @var float
     */
    public $repeatingWordsAccuracy;

    /**
     * @var bool
     */
    public $ignoreWhitespaceDifferences;

    /**
     * MatchOptions constructor.
     */
    public function __construct(int $blockSize, float $repeatingWordsAccuracy, bool $ignoreWhitespaceDifferences)
    {
        $this->blockSize = $blockSize;
        $this->repeatingWordsAccuracy = $repeatingWordsAccuracy;
        $this->ignoreWhitespaceDifferences = $ignoreWhitespaceDifferences;
    }

}