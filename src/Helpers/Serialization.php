<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use RuntimeException;
use Throwable;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function serialize;
use function set_error_handler;
use function strlen;
use function strpos;
use function substr;
use function trim;
use function unserialize;

final class Serialization
{

    /* --------------------------------------------------------------------------------*
     |                              Serialize Helper                                   |
     |                                                                                 |
     | Custom From WordPress Core wp-includes/functions.php                            |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Check value to find if it was serialized.
     * If $data is not a string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @param mixed $data Value to check to see if was serialized.
     * @param bool $strict Optional. Whether to be strict about the end of the string. Defaults true.
     *
     * @return bool  false if not serialized and true if it was.
     */
    public static function isSerialized($data, bool $strict = true): bool
    {
        /* if it isn't a string, it isn't serialized
         ------------------------------------------- */
        if (!is_string($data) || ($data = trim($data)) === '') {
            return false;
        }

        // null && boolean
        if ('N;' === $data || $data === 'b:0;' || 'b:1;' === $data) {
            return true;
        }

        if (strlen($data) < 4 || ':' !== $data[1]) {
            return false;
        }

        if ($strict) {
            $last_char = substr($data, -1);
            if (';' !== $last_char && '}' !== $last_char) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');

            // Either ";" or "}" must exist.
            if (false === $semicolon && false === $brace
                || false !== $semicolon && $semicolon < 3
                || false !== $brace && $brace < 4
            ) {
                return false;
            }
        }

        $token = $data[0];
        switch ($token) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (!str_contains($data, '"')) {
                    return false;
                }
            // or else fall through
            case 'a':
            case 'O':
            case 'C':
                return (bool)preg_match("/^$token:[0-9]+:/s", $data);
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^$token:[0-9.E-]+;$end/", $data);
        }

        return false;
    }

    /**
     * Un-serialize value only if it was serialized.
     *
     * @param mixed $original Maybe serialized original, if is needed.
     *
     * @return mixed  Un-serialized data can be any type.
     */
    public static function shouldUnSerialize($original)
    {
        if (!is_string($original) || trim($original) === '') {
            return $original;
        }

        /**
         * Check if serialized
         * check with trim
         */
        if (self::isSerialized($original)) {
            try {
                set_error_handler(static function ($errNo, $errStr) {
                    throw new RuntimeException($errStr, $errNo);
                });
                return unserialize($original);
            } catch (Throwable $e) {
                return $original;
            } finally {
                restore_error_handler();
            }
        }

        return $original;
    }

    /**
     * Serialize data, if needed. @param mixed $data Data that might be serialized.
     *
     * @param bool $doubleSerialize Double Serialize if you want to use returning real value of serialized
     *                                for database result
     *
     * @return mixed A scalar data
     * @uses for ( un-compress serialize values )
     * This method to use safe as safe data on a database. The Value that has been
     * Serialized will be double-serialized to make sure data is stored as original
     */
    public static function shouldSerialize($data, bool $doubleSerialize = true)
    {
        /**
         * Double serialization is required for backward compatibility.
         * if @param bool $doubleSerialize is enabled
         */
        if (is_array($data)
            || is_object($data)
            || $doubleSerialize && self::isSerialized($data, false)
        ) {
            return serialize($data);
        }

        return $data;
    }
}
