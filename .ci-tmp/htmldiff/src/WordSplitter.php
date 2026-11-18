<?php namespace Ssddanbrown\HtmlDiff;

use Exception;

class WordSplitter
{

    /**
     * Converts HTML text into a list of words
     * @throws Exception
     * @param string[] $blockExpressions
     * @return string[]
     */
    public static function convertHtmlToListOfWords(string $text, array $blockExpressions): array
    {
        $mode = Mode::CHARACTER;
        $currentWord = "";
        /** @var string[] $words */
        $words = [];
        $blockLocations = static::findBlocks($text, $blockExpressions);
        $isBlockCheckRequired = count($blockLocations) > 0;
        $isGrouping = false;
        $groupingUntil = -1;

        $length = strlen($text);
        $mbCharLength = 0;
        $lastCharSize = 1;
        $character = "";
        for ($index = 0; $index < $length; $index++)
        {
            $currentCharacter = substr($text, $index, 1);

            // Join multibyte characters together if we're in one
            if ($mbCharLength > 1) {
                $mbCharLength--;
                $character .= $currentCharacter;
                if ($mbCharLength !== 1) {
                    continue;
                }
            } else {
                // Check if we're in a multibyte character
                $currentCharVal = ord($currentCharacter);
                $character = $currentCharacter;
                if ($currentCharVal >= 192) {
                    $mbCharLength = ($currentCharVal >= 240) ? 4 : (($currentCharVal >= 224) ? 3 : 2);
                    continue;
                }
            }

            // Don't bother executing block checks if we don't have any blocks to check for!
            if ($isBlockCheckRequired) {
                // Check if we have completed grouping a text sequence/block
                if ($groupingUntil === $index) {
                    $groupingUntil = -1;
                    $isGrouping = false;
                }

                // Check if we need to group the next text sequence/block
                $until = $blockLocations[$index] ?? 0;
                if (isset($blockLocations[$index])) {
                    $isGrouping = true;
                    $groupingUntil = $until;
                }

                // if we are grouping, then we don't care about what type of character we have,
                // it's going to be treated as a word
                if ($isGrouping) {
                    $currentWord .= $character;
                    $mode = Mode::CHARACTER;
                    continue;
                }
            }

            switch ($mode) {
                case Mode::CHARACTER:
                    if (Utils::isStartOfTag($character)) {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = "<";
                        $lastCharSize = 1;
                        $mode = Mode::TAG;
                    } else if (Utils::isStartOfEntity($character)) {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::ENTITY;
                    } else if (Utils::isWhiteSpace($character)) {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::WHITESPACE;
                    } else if (Utils::isWord($character) &&
                        (strlen($currentWord) === 0) || Utils::isWord(substr($currentWord, 0 - $lastCharSize))) {
                        $currentWord .= $character;
                        $lastCharSize = strlen($character);
                    } else {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                    }

                    break;
                case Mode::TAG:

                    if (Utils::isEndOfTag($character)) {
                        $currentWord .= $character;
                        $words[] = $currentWord;
                        $currentWord = "";
                        $lastCharSize = 0;
                        $mode = Utils::isWhiteSpace($character) ? Mode::WHITESPACE : Mode::CHARACTER;
                    } else {
                        $currentWord .= $character;
                        $lastCharSize = strlen($character);
                    }

                    break;
                case Mode::WHITESPACE:

                    if (Utils::isStartOfTag($character))
                    {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::TAG;
                    } else if (Utils::isStartOfEntity($character)) {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::ENTITY;
                    } else if (Utils::isWhiteSpace($character)) {
                        $currentWord .= $character;
                        $lastCharSize = strlen($character);
                    } else {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::CHARACTER;
                    }

                    break;
                case Mode::ENTITY:

                    if (Utils::isStartOfTag($character))
                    {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::TAG;
                    } else if (Utils::isWhiteSpace($character)) {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::WHITESPACE;
                    } else if (Utils::isEndOfEntity($character)) {
                        $switchToNextMode = true;
                        if (strlen($currentWord) !== 0) {
                            $currentWord .= $character;
                            $lastCharSize = strlen($character);
                            $words[] = $currentWord;

                            //join &nbsp; entity with last whitespace
                            if (count($words) > 2
                                && Utils::isWhiteSpace($words[count($words) - 2])
                                && Utils::isWhiteSpace($words[count($words) - 1])) {
                                $w1 = $words[count($words) - 2];
                                $w2 = $words[count($words) - 1];
                                $words = array_slice($words, 0, count($words) - 2);
                                $currentWord = $w1 . $w2;
                                $mode = Mode::WHITESPACE;
                                $switchToNextMode = false;
                            }
                        }
                        if ($switchToNextMode) {
                            $currentWord = "";
                            $lastCharSize = 0;
                            $mode = Mode::CHARACTER;
                        }
                    } else if (Utils::isWord($character)) {
                        $currentWord .= $character;
                        $lastCharSize = strlen($character);
                    } else {
                        if (strlen($currentWord) !== 0) {
                            $words[] = $currentWord;
                        }
                        $currentWord = $character;
                        $lastCharSize = strlen($character);
                        $mode = Mode::CHARACTER;
                    }
                    break;
            }
        }

        if (strlen($currentWord) !== 0) {
            $words[] = $currentWord;
        }

        return $words;
    }

    /**
     * Finds any blocks that need to be grouped.
     * @param string[] $blockExpressions
     * @return array<int, int>
     */
    private static function findBlocks(string $text, array $blockExpressions): array
    {
        /** @var array<int, int> $blockLocations */
        $blockLocations = [];

        foreach ($blockExpressions as $exp) {
            $matches = [];
            preg_match_all($exp, $text, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as $matchAndOffset) {
                $match = $matchAndOffset[0];
                /** @var int $offset */
                $offset = $matchAndOffset[1];
                if (isset($blockLocations[$offset])) {
                    throw new Exception("One or more block expressions result in a text sequence that overlaps. Current expression: {$exp}");
                }

                $blockLocations[$offset] = $offset + mb_strlen($match);
            }
        }

        return $blockLocations;
    }

}