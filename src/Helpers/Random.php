<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Throwable;
use function random_bytes;

/**
 * Random Library
 */
class Random
{
    /**
     * @var int DEFAULT_LENGTH the default length
     */
    public const DEFAULT_LENGTH = 16;

    /**
     * @var string CHARS the chars
     */
    public const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Create Random String based on chars
     *
     * @param int $length
     * @param string $chars
     * @return string
     */
    public static function chars(int $length = self::DEFAULT_LENGTH, string $chars = self::CHARS) : string
    {
        $charsLength = strlen($chars);
        if ($charsLength < 1) {
            return '';
        }
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[self::int(0, $charsLength - 1)];
        }
        return $str;
    }

    /**
     * Create Random Hex
     *
     * @param int $length
     * @return string
     */
    public static function hex(int $length = self::DEFAULT_LENGTH) : string
    {
        return self::chars($length, '0123456789abcdef');
    }

    /**
     * Create Random Integers
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function int(int $min, int $max) : int
    {
        if ($min > $max) {
            $min = $max;
        }
        if ($min === $max) {
            return $min;
        }
        try {
            return random_int($min, $max);
        } catch (Throwable $e) {
            return mt_rand($min, $max);
        }
    }

    /**
     * Create Random Bytes
     *
     * @param int $length
     * @return string
     */
    public static function bytes(int $length = self::DEFAULT_LENGTH) : string
    {
        if ($length < 1) {
            return '';
        }
        try {
            return random_bytes($length);
        } catch (Throwable $e) {
            try {
                if (function_exists('openssl_random_pseudo_bytes')) {
                    return openssl_random_pseudo_bytes($length);
                }
            } catch (Throwable $e) {
                // do nothing
            }
            $str = '';
            for ($i = 0; $i < $length; $i++) {
                $str .= chr(self::int(0, 255));
            }
            return $str;
        }
    }
}