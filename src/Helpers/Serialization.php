<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Closure;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnprocessableDataException;
use ReflectionFunction;
use RuntimeException;
use stdClass;
use Throwable;
use function array_map;
use function array_shift;
use function get_class;
use function implode;
use function is_array;
use function is_callable;
use function is_iterable;
use function is_object;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function preg_match;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function sprintf;
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
                    throw new UnprocessableDataException($errStr, $errNo);
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

    /**
     * Safe serialize
     *
     * @param $data
     * @return mixed
     */
    public static function safeSerialize($data)
    {
        $convertCallable = function ($callable) {
            if ($callable instanceof Closure) {
                $ref = new ReflectionFunction($callable);
                $closureThis = $ref->getClosureThis();
                $parameterNames = array_map(function ($param) {
                    return '$'.$param->getName();
                }, $ref->getParameters());
                if ($closureThis) {
                    return sprintf('Closure->call(%s, %s)', get_class($closureThis), implode(', ', $parameterNames));
                }
                return 'Closure('.implode(', ', $parameterNames).')';
            }
            $className = is_object($callable) ? get_class($callable) : null;
            if (!is_callable($callable)) {
                return $callable;
            }
            if (is_object($callable)) {
                return sprintf('%s->__invoke()', $className);
            }
            if (is_array($callable)) {
                $callback = array_shift($callable);
                $method = array_shift($callable);
                if (is_object($callback)) {
                    return sprintf('%s->%s', get_class($callback), $method);
                }
                return sprintf('%s::%s', $callback, $method);
            }
            return $callable;
        };
        $succeedSerialize = function ($data) {
            set_error_handler(function ($errNum, $errStr) {
                throw new UnprocessableDataException($errStr, $errNum);
            });
            try {
                serialize($data);
                return true;
            } catch (Throwable $e) {
                return false;
            } finally {
                restore_error_handler();
            }
        };
        $convertData = function ($data) use (&$convertData, $convertCallable, $succeedSerialize) {
            if ($data instanceof stdClass) {
                $data = clone $data;
                foreach ((array) $data as $key => $item) {
                    if (is_iterable($item) || !is_callable($item)) {
                        $data[$key] = $convertData($item);
                        continue;
                    }
                    $data[$key] = $convertCallable($item);
                }
                return $data;
            }
            if (is_iterable($data)) {
                $data = is_array($data) ? $data : iterator_to_array($data);
                foreach ($data as $key => $v) {
                    if (is_iterable($v) || !is_callable($v)) {
                        $data[$key] = $convertData($v);
                        continue;
                    }
                    $data[$key] = $convertCallable($v);
                }
                return $data;
            }
            if (is_object($data)) {
                if ($succeedSerialize($data)) {
                    return $data;
                }
                return json_encode($data);
            }
            return $data;
        };
        return self::shouldSerialize($convertData($data));
    }
}
