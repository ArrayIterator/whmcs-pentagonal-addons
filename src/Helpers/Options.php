<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Closure;
use JsonSerializable;
use Serializable;
use Throwable;
use WHMCS\Database\Capsule;
use function array_map;
use function array_shift;
use function count;
use function get_class;
use function implode;
use function is_object;
use function is_resource;
use function method_exists;
use function strpos;
use function strtolower;

/**
 * Class Options for handling options
 * use deferred save to database
 *
 */
final class Options
{
    /**
     * @var int MAX_QUEUE the max queue
     */
    public const MAX_QUEUE = 100;

    /**
     * @var int MAX_NOT_EXISTS_RECORD the max not exists record
     */
    public const MAX_NOT_EXISTS_RECORD = 500;

    /**
     * @var int MAX_OPTIONS_RECORD the max options record
     */
    public const MAX_OPTIONS_RECORD = 100;

    /**
     * @var int MAX_BATCH_INSERT the max batch insert
     */
    public const MAX_BATCH_INSERT = 50;

    /**
     * @var string TABLE_OPTIONS the table name
     */
    public const TABLE_OPTIONS = 'pentagonal_options';

    /**
     * @var Options $instance the instance
     */
    private static self $instance;

    /**
     * @var array<array{string: array{0: string, 1: mixed}}> $queue the queue
     */
    private array $queue = [];

    /**
     * @var array<array{string: array{0: string, 1: mixed}}> $options the queue
     */
    private array $options = [];

    /**
     * @var array<string, bool>
     */
    private array $notExists = [];

    /**
     * @var bool $initialized is initialized
     */
    private bool $initialized = false;

    /**
     * @var bool $destructedSave is destructed
     */
    private bool $destructedSave = false;

    /**
     * @private
     */
    private function __construct()
    {
    }

    /**
     * Get instance
     *
     * @return Options
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Initialize the database
     *
     * @return void
     */
    private function initialize() : self
    {
        if ($this->initialized) {
            return $this;
        }
        $this->initialized = true;
        if (Capsule::schema()->hasTable(self::TABLE_OPTIONS)) {
            $count = Capsule::table(self::TABLE_OPTIONS)->count();
            if ($count <= self::MAX_OPTIONS_RECORD) {
                Capsule::table(self::TABLE_OPTIONS)
                    ->select(['name', 'value'])
                    ->limit(self::MAX_OPTIONS_RECORD)
                    ->get()
                    ->each(function ($item) {
                        $this->options[$this->normalizeOptionName($item->name)] = [
                            $item->name,
                            Serialization::shouldUnSerialize($item->value),
                        ];
                    });
            }
            return $this;
        }

        Capsule::schema()->create(self::TABLE_OPTIONS, function ($table) {
            /**
             * @var \Illuminate\Database\Schema\Blueprint $table
             * @noinspection PhpFullyQualifiedNameUsageInspection
             */
            $table->string('name')->primary();
            $table->text('value')->default('');
        });
        return $this;
    }

    /**
     * Save queue to database
     *
     * @return void
     */
    private function saveDatabase() : void
    {
        $count = count($this->initialize()->queue);
        $this->destructedSave = false;
        if ($count === 0) {
            return;
        }

        $totalUnshift = $count + count($this->options);
        while ($totalUnshift >= self::MAX_OPTIONS_RECORD) {
            array_shift($this->options);
            $totalUnshift--;
        }
        $doInsert = static function (array &$inserts) {
            if (count($inserts) === 0) {
                return;
            }
            // use traditional mysql replace
            $table = self::TABLE_OPTIONS;
            $sql = "REPLACE INTO `$table` (`name`, `value`) VALUES ";
            $sql .= implode(', ', array_map(static function () {
                return '(?, ?)';
            }, $inserts));
            $binding = [];
            while ($item = array_shift($inserts)) {
                $binding[] = array_shift($item);
                $binding[] = array_shift($item);
            }
            Capsule::connection()->statement($sql, $binding);
        };
        $inserts = [];
        while ($queue = array_shift($this->queue)) {
            $name = array_shift($queue);
            $value = array_shift($queue);
            try {
                $value = Serialization::shouldSerialize($value);
            } catch (Throwable $e) {
                continue;
            }
            $inserts[] = [$name, $value];
            if (count($inserts) >= self::MAX_BATCH_INSERT) {
                $doInsert($inserts);
            }
        }
        $doInsert($inserts);
    }

    /**
     * Normalize the key name
     *
     * @param string $name
     * @return string
     * @public @static
     */
    protected function normalizeOptionName(string $name) : string
    {
        return strtolower(trim($name));
    }

    /**
     * Check if options exists
     *
     * @param string $name
     * @return bool
     * @public @static
     */
    protected function hasOption(string $name) : bool
    {
        $this->getOption($name, $exists);
        return $exists;
    }

    /**
     * Get the value
     *
     * @param string $name represents the name
     * @param $exist
     * @return mixed|null the value
     */
    protected function getOption(string $name, &$exist = null)
    {
        $performance = Performance::profile('options_get', 'system.options')
            ->setDataValue('name', $name);
        try {
            $key = $this->initialize()->normalizeOptionName($name);
            $exist = !isset($this->notExists[$key]);
            if (!$exist) {
                return null;
            }
            if (isset($this->options[$key])) {
                return $this->options[$key][1];
            }
            if (isset($this->queue[$key])) {
                return $this->queue[$key][1];
            }
            $value = Capsule::table(self::TABLE_OPTIONS)
                ->where('name', $key)
                ->first();
            if (!$value) {
                $exist = false;
                while (count($this->notExists) >= self::MAX_NOT_EXISTS_RECORD) {
                    array_shift($this->notExists);
                }
                $this->notExists[$key] = true;
                return null;
            }
            $this->options[$key] = [
                $name,
                Serialization::shouldUnSerialize($value->value),
            ];
            return $this->options[$key][1];
        } finally {
            $performance->end();
        }
    }

    /**
     * Set the value
     *
     * @param string $name represents the name
     * @param mixed $value represents the value
     * @return bool true if success
     */
    protected function setOption(string $name, $value): bool
    {
        // closure & resource is not allowed
        if ($value instanceof Closure || is_resource($value)) {
            return false;
        }
        // check if is object & anonymous class
        if (is_object($value)
            && strpos(get_class($value), '@') !== false
            && (
            ! $value instanceof Serializable
            )
        ) {
            if (method_exists($value, '__toString')) {
                $value = (string) $value;
            } elseif ($value instanceof JsonSerializable) {
                $value = $value->jsonSerialize();
            } else {
                return false;
            }
        }

        $key = $this->normalizeOptionName($name);
        $this->queue[$key] = [
            $name,
            $value,
        ];

        unset($this->notExists[$name], $this->options[$name]);
        if ($this->destructedSave || count($this->queue) >= self::MAX_QUEUE) {
            $this->saveDatabase();
        }
        return true;
    }

    /**
     * Delete the value
     *
     * @param string $name represents the name
     * @return bool true if success
     */
    protected function optionDelete(string $name): bool
    {
        $name = $this->initialize()->normalizeOptionName($name);
        try {
            Capsule::table(self::TABLE_OPTIONS)
                ->where('name', $name)
                ->delete();
            unset($this->queue[$name], $this->options[$name]);
            while (count($this->notExists) >= self::MAX_NOT_EXISTS_RECORD) {
                array_shift($this->notExists);
            }
            $this->notExists[$name] = true;
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get the value
     *
     * @param string $name
     * @param boolean $exists
     * @return mixed|null
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function get(string $name, &$exists = null)
    {
        return self::getInstance()->getOption($name, $exists);
    }

    /**
     * Set the value
     *
     * @param string $name represents the name
     * @param mixed $value represents the value
     * @return bool true if success
     */
    public static function set(string $name, $value): bool
    {
        return self::getInstance()->setOption($name, $value);
    }

    /**
     * Check if options exists
     *
     * @param string $name the name
     * @return bool true if exists
     */
    public static function has(string $name): bool
    {
        return self::getInstance()->hasOption($name);
    }

    /**
     * Delete the value
     *
     * @param string $name represents the name
     * @return bool true if success
     */
    public static function delete(string $name): bool
    {
        return self::getInstance()->optionDelete($name);
    }

    public function __destruct()
    {
        $this->saveDatabase();
        $this->destructedSave = true;
    }
}
