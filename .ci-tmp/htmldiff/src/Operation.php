<?php namespace Ssddanbrown\HtmlDiff;

class Operation
{
    public $action;
    public $startInOld;
    public $endInOld;
    public $startInNew;
    public $endInNew;

    public function __construct(string $action, int $startInOld, int $endInOld, int $startInNew, int $endInNew)
    {
        $this->action = $action;
        $this->startInOld = $startInOld;
        $this->endInOld = $endInOld;
        $this->startInNew = $startInNew;
        $this->endInNew = $endInNew;
    }

    /**
     * @codeCoverageIgnore
     * @param string[] $oldWords
     * @param string[] $newWords
     */
    public function debug_printDebugInfo(array $oldWords, array $newWords): void
    {
        $oldText = implode('', array_filter($oldWords, function(string $v, int $i) {
            return $i >= $this->startInOld && $i < $this->endInOld;
        }, ARRAY_FILTER_USE_BOTH));
        $newText = implode('', array_filter($newWords, function(string $v, int $i) {
            return $i >= $this->startInNew && $i < $this->endInNew;
        }, ARRAY_FILTER_USE_BOTH));
        echo "Operation: {$this->action}, Old Text: {$oldText}, New Text: {$newText}";
    }

}