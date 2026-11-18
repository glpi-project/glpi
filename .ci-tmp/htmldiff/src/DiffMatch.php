<?php namespace Ssddanbrown\HtmlDiff;

class DiffMatch
{
    public $startInOld;
    public $startInNew;
    public $size;

    public function __construct(int $startInOld, int $startInNew, int $size)
    {
        $this->startInOld = $startInOld;
        $this->startInNew = $startInNew;
        $this->size = $size;
    }

    public function getEndInOld(): int
    {
        return $this->startInOld + $this->size;
    }

    public function getEndInNew(): int
    {
        return $this->startInNew + $this->size;
    }

    /**
     * @codeCoverageIgnore
     * @param string[] $oldWords
     */
    public function debug_printWordsFromOld(array $oldWords): void
    {
        $filtered = array_values(array_filter($oldWords, function(string $v, int $i) {
            return $i >= $this->startInOld && $i < $this->getEndInOld();
        }, ARRAY_FILTER_USE_BOTH));
        $text = implode('', $filtered);
        echo "OLD: " . $text;
    }

    /**
     * @codeCoverageIgnore
     * @param string[] $newWords
     */
    public function debug_printWordsFromNew(array $newWords): void
    {
        $filtered = array_values(array_filter($newWords, function(string $v, int $i) {
            return $i >= $this->startInNew && $i < $this->getEndInNew();
        }, ARRAY_FILTER_USE_BOTH));
        $text = implode('', $filtered);
        echo "NEW: " . $text;
    }

}