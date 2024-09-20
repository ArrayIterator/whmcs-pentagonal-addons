<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use function array_filter;
use function array_map;
use function explode;
use function get_object_vars;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function preg_quote;
use function realpath;
use function str_contains;
use function str_replace;
use const ROOTDIR;

class DataNormalizer
{
    /**
     * Balances tags of string using a modified stack.
     *
     * @param string $text Text to be balanced.
     * @return string Balanced text.
     *
     * Custom mods to be fixed to handle by system result output
     * @copyright November 4, 2001
     * @version 1.1
     *
     * Modified by Scott Reilly (coffee2code) 02 Aug 2004
     *      1.1 Fixed handling append/stack pop order of end text
     *          Added Cleaning Hooks
     *      1.0 First Version
     *
     * @author Leonard Lin <leonard@acm.org>
     * @license GPL
     */
    public static function forceBalanceTags(string $text): string
    {
        $tagStack = [];
        $stackSize = 0;
        $tagQueue = '';
        $newText = '';
        // Known single-entity/self-closing tags
        $single_tags = [
            'area',
            'base',
            'basefont',
            'br',
            'col',
            'command',
            'embed',
            'frame',
            'hr',
            'img',
            'input',
            'isindex',
            'link',
            'meta',
            'param',
            'source'
        ];
        $single_tags_2 = [
            'img',
            'meta',
            'link',
            'input'
        ];
        // Tags that can be immediately nested within themselves
        $nestable_tags = ['blockquote', 'div', 'object', 'q', 'span'];
        // check if contains <html> tag and split it
        // fix doctype
        $text = preg_replace('/<(\s+)?!(\s+)?(DOCTYPE)/i', '<!$3', $text);
        $rand = sprintf('%1$s_%2$s_%1$s', '%', mt_rand(10000, 50000));
        $randQuote = preg_quote($rand, '~');
        $text = str_replace('<!', '< ' . $rand, $text);
        // bug fix for comments - in case you REALLY meant to type '< !--'
        $text = str_replace('< !--', '<    !--', $text);
        // bug fix for LOVE <3 (and other situations with '<' before a number)
        $text = preg_replace('#<([0-9])#', '&lt;$1', $text);
        while (preg_match(
            "~<((?!\s+" . $randQuote . ")/?[\w:]*)\s*([^>]*)>~",
            $text,
            $regex
        )) {
            $newText .= $tagQueue;
            $i = strpos($text, $regex[0]);
            $l = strlen($regex[0]);
            // clear the shifter
            $tagQueue = '';
            // Pop or Push
            if (isset($regex[1][0]) && '/' === $regex[1][0]) { // End Tag
                $tag = strtolower(substr($regex[1], 1));
                // if too many closing tags
                if ($stackSize <= 0) {
                    $tag = '';
                    // or close to be safe $tag = '/' . $tag;
                } elseif ($tagStack[$stackSize - 1] === $tag) {
                    // if stack top value = tag close value then pop
                    // found closing tag
                    $tag = '</' . $tag . '>'; // Close Tag
                    // Pop
                    array_pop($tagStack);
                    $stackSize--;
                } else { // closing tag not at top, search for it
                    for ($j = $stackSize - 1; $j >= 0; $j--) {
                        if ($tagStack[$j] === $tag) {
                            // add tag to tag queue
                            for ($k = $stackSize - 1; $k >= $j; $k--) {
                                $tagQueue .= '</' . array_pop($tagStack) . '>';
                                $stackSize--;
                            }
                            break;
                        }
                    }
                    $tag = '';
                }
            } else { // Begin Tag
                $tag = strtolower($regex[1]);
                // Tag Cleaning
                // If it's an empty tag "< >", do nothing
                /** @noinspection PhpStatementHasEmptyBodyInspection */
                if ('' === $tag
                    // ElseIf it's a known single-entity tag, but it doesn't close itself, do so
                    // $regex[2] .= '';
                    || in_array($tag, $single_tags_2)
                ) {
                    // do nothing
                } elseif (str_ends_with($regex[2], '/')) {
                    // ElseIf it presents itself as a self-closing tag...
                    // ----
                    // ...but it isn't a known single-entity self-closing tag,
                    // then don't let it be treated as such and
                    // immediately close it with a closing tag (the tag will encapsulate no text as a result)
                    if (!in_array($tag, $single_tags)) {
                        $regex[2] = trim(substr($regex[2], 0, -1)) . "></$tag";
                    }
                } elseif (in_array($tag, $single_tags)) {
                    // ElseIf it's a known single-entity tag, but it doesn't close itself, do so
                    $regex[2] .= '/';
                } else {
                    // Else it's not a single-entity tag
                    // ---------
                    // If the top of the stack is the same as the tag we want to push,
                    // close the previous tag
                    if ($stackSize > 0 && !in_array($tag, $nestable_tags)
                        && $tagStack[$stackSize - 1] === $tag
                    ) {
                        $tagQueue = '</' . array_pop($tagStack) . '>';
                        /** @noinspection PhpUnusedLocalVariableInspection */
                        $stackSize--;
                    }
                    $stackSize = array_push($tagStack, $tag);
                }
                // Attributes
                $attributes = $regex[2];
                if (!empty($attributes) && $attributes[0] !== '>') {
                    $attributes = ' ' . $attributes;
                }
                $tag = '<' . $tag . $attributes . '>';
                //If already queuing a close tag, then put this tag on, too
                if (!empty($tagQueue)) {
                    $tagQueue .= $tag;
                    $tag = '';
                }
            }
            $newText .= substr($text, 0, $i) . $tag;
            $text = substr($text, $i + $l);
        }
        // Clear Tag Queue
        $newText .= $tagQueue;
        // Add Remaining text
        $newText .= $text;
        unset($text); // freed memory
        // Empty Stack
        while ($x = array_pop($tagStack)) {
            $newText .= '</' . $x . '>'; // Add remaining tags to close
        }
        // fix for the bug with HTML comments
        $newText = str_replace("< $rand", "<!", $newText);
        $newText = str_replace("< !--", "<!--", $newText);

        return str_replace("<    !--", "< !--", $newText);
    }

    /**
     * @param string $class
     * @return string
     */
    public static function normalizeHtmlClass(string $class): string
    {
        $class = trim($class);
        if ($class === '') {
            return '';
        }
        if (str_contains($class, ':')) {
            $class = array_map([__CLASS__, 'normalizeHtmlClass'], explode(':', $class));
            $class = array_filter($class);
            return implode(':', $class);
        }
        // add : for tailwind
        return preg_replace('/[^a-z0-9_\-]/i', '-', $class);
    }

    /**
     * Splitting the string or iterable to array
     *
     * @param mixed $string
     * @param string $separator
     * @return array|null returning null if not an iterable or string
     */
    public static function splitStringToArray($string, string $separator = ' '): ?array
    {
        // don't process an array
        if (is_array($string)) {
            return $string;
        }
        if (is_string($string)) {
            return explode($separator, $string);
        }
        if (is_iterable($string)) {
            return iterator_to_array($string);
        }
        return null;
    }

    private static ? string $rootDirQuoted = null;

    /**
     * Protect / Replace Root Dir
     *
     * @param $data
     * @param string $replacement
     * @return array|mixed|string|string[]
     */
    public static function protectRootDir($data, string $replacement = '[ROOT]')
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                $data[$key] = self::protectRootDir($item, $replacement);
            }
            return $data;
        }
        if (is_object($data)) {
            foreach (get_object_vars($data) as $key => $item) {
                $data->$key = self::protectRootDir($item, $replacement);
            }
            return $data;
        }
        if (!is_string($data)) {
            return $data;
        }
        self::$rootDirQuoted ??= realpath(ROOTDIR)?:ROOTDIR;
        return str_replace(self::$rootDirQuoted, $replacement, $data);
    }
}
