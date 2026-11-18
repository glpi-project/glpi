<?php namespace Ssddanbrown\HtmlDiff;

class Utils
{
    /**
     * @var string
     */
    private static $openingTagRegex = '/^\s*<[^>]+>\s*$/';

    /**
     * @var string
     */
    private static $closingTagRegex = '/^\s*<\/[^>]+>\s*$/';

    /**
     * @var string
     */
    private static $tagWordRegex = '/<[^\s>]+/';

    /**
     * @var string
     */
    private static $whitespaceRegex = '/^(\s|&nbsp;)+$/';

    /**
     * @var string
     */
    private static $wordRegex = '/[\w\#@]+/u';

    /**
     * @var string[]
     */
    private static $specialCaseWordTags = ["<img"];

    public static function isTag(string $item): bool
    {
        $specials = array_filter(static::$specialCaseWordTags, function(string $val) use ($item) {
            return strpos($item, $val) === 0;
        });

        if (count($specials) > 0) {
            return false;
        }

        return static::isOpeningTag($item) || static::isClosingTag($item);
    }

    private static function isOpeningTag(string $item): bool
    {
        return preg_match(static::$openingTagRegex, $item) === 1;
    }

    private static function isClosingTag(string $item): bool
    {
        return preg_match(static::$closingTagRegex, $item) === 1;
    }

    public static function stripTagAttributes(string $word): string
    {
        $matches = [];
        preg_match(static::$tagWordRegex, $word, $matches);
        $tag = $matches[0] ?? '';
        $needsClosing = substr($word, -2) === '/>' && substr($tag, -1) !== '/';
        return $tag . ($needsClosing ? '/>' : '>');
    }

    public static function wrapText(string $text, string $tagName, string $cssClass): string
    {
        return "<{$tagName} class=\"{$cssClass}\">{$text}</{$tagName}>";
    }

    public static function isStartOfTag(string $val): bool
    {
        return $val === '<';
    }

    public static function isEndOfTag(string $val): bool
    {
        return $val === '>';
    }

    public static function isStartOfEntity(string $val): bool
    {
        return $val === '&';
    }

    public static function isEndOfEntity(string $val): bool
    {
        return $val === ';';
    }

    public static function isWhiteSpace(string $val): bool
    {
        return preg_match(static::$whitespaceRegex, $val) === 1;
    }

    public static function stripAnyAttributes(string $word): string
    {
        if (static::isTag($word)) {
            return static::stripTagAttributes($word);
        }
        return $word;
    }

    public static function isWord(string $text): bool
    {
        return preg_match(static::$wordRegex, $text) === 1;
    }
}