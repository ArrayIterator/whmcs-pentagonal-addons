<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\LogWriter;

use Monolog\Logger;
use PDO;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnprocessableException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Options;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Serialization;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\LogWriterInterface;
use Throwable;
use WHMCS\Database\Capsule;
use function array_change_key_case;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function strtolower;
use const CASE_LOWER;

class DatabaseWriter implements LogWriterInterface
{
    /**
     * @var string ENABLE_OPTION_NAME the enable option name
     */
    public const ENABLE_OPTION_NAME = 'system_enable_logging';

    /**
     * @var string DISABLE_AUTO_CLEAN_OPTION_NAME the auto clean option name
     */
    public const DISABLE_AUTO_CLEAN_OPTION_NAME = 'system_disable_auto_clean_log';

    public const MAX_COUNT_AUTO_CLEAN_OPTION_NAME = 'system_max_log_record';

    /**
     * @var int MAX_QUEUE the max queue
     */
    public const MAX_QUEUE = 100;

    /**
     * @var int MAX_BATCH_INSERT the max batch insert
     */
    public const MAX_BATCH_INSERT = 50;

    /**
     * @var string TABLE_NAME the table name
     */
    public const TABLE_NAME = 'pentagonal_logs';

    /**
     * @var int CLEAN_LOG_COUNT the clean log count if more than 100K
     */
    public const CLEAN_LOG_COUNT = 100000;

    /**
     * @var int MAX_DUPLICATION_CLEAN maximum clean
     */
    public const MAX_DUPLICATION_CLEAN = 5000;

    /**
     * Queue the log
     *
     * @var array<array{
     *     level: int,
     *     message: string,
     *     context: array<string, mixed> | null,
     *     extra: array<string, mixed> | null
     *  }> $queue the queue
     */
    protected array $queue = [];

    /**
     * @var bool $initialized the initialized
     */
    private bool $initialized = false;

    /**
     * @var bool $enabled the enabled
     */
    protected bool $enabled = true;

    /**
     * @var int $initialLogCount the initial length
     */
    private int $initialLogCount = 0;

    /**
     * @var int $limitLogRecords the limit log records
     */
    private int $limitLogRecords = self::CLEAN_LOG_COUNT;

    /**
     * @var array|string[] $columns
     */
    private array $columns = [
        'id',
        'level',
        'hash',
        'message',
        'context',
        'extra',
        'timestamp'
    ];

    private function createSchema() : void
    {
        Capsule::schema()->create(self::TABLE_NAME, function ($table) {
            /**
             * @var \Illuminate\Database\Schema\Blueprint $table
             * @noinspection PhpFullyQualifiedNameUsageInspection
             */
            $table->bigIncrements('id')->unsigned();
            $table->integer('level');
            $table->string('hash', 32);
            $table->binary('message');
            $table->binary('context')->nullable();
            $table->binary('extra')->nullable();
            $table->integer('timestamp', false, true);
            $table->index(['hash'], 'hash_log_index');
        });
    }

    /**
     * @return void
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $performance = Performance::profile('init', 'system.database_writer');
        try {
            // do something
            if (!Capsule::schema()->hasTable(self::TABLE_NAME)) {
                $this->createSchema();
            } else {
                Capsule::schema()->table(self::TABLE_NAME, function ($table) {
                    /**
                     * @var \Illuminate\Database\Schema\Blueprint $table
                     * @noinspection PhpFullyQualifiedNameUsageInspection
                     */
                    $pdo = Capsule::connection()->getPdo();
                    $stmt = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`', self::TABLE_NAME));
                    $contains = false;
                    try {
                        $columns = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $row = array_change_key_case($row, CASE_LOWER);
                            $columns[strtolower($row['field'])] = $row['field'];
                        }
                        $stmt->closeCursor();
                        foreach ($this->columns as $column) {
                            if (!isset($columns[$column])) {
                                Capsule::schema()->drop(self::TABLE_NAME);
                                $this->createSchema();
                                return;
                            }
                        }
                        $stmt = $pdo->query(sprintf('SHOW INDEX FROM `%s`', self::TABLE_NAME));
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $row = array_change_key_case($row, CASE_LOWER);
                            if (strtolower($row['column_name']??'') === 'hash') {
                                $contains = true;
                                break;
                            }
                        }
                        $stmt->closeCursor();
                    } catch (Throwable $e) {
                        return;
                    }
                    if ($contains) {
                        return;
                    }
                    $table->index(['hash'], 'hash_log_index');
                });
            }
            if (!Options::has(self::ENABLE_OPTION_NAME)) {
                Options::set(self::ENABLE_OPTION_NAME, 'yes');
            } else {
                $logOption = Options::get(self::ENABLE_OPTION_NAME);
                $logOption = is_string($logOption) ? strtolower(trim($logOption)) : $logOption;
                $this->enabled = in_array($logOption, ['yes', '1', 'true', 'on', true, 1], true);
            }

            // SELECT * FROM information_schema.tables WHERE table_schema
            //  = 'table_name' and TABLE_NAME = 'pentagonal_record_logs';
            try {
                $count = Capsule::table('information_schema.tables')
                    ->select('TABLE_ROWS')
                    ->where([
                        ['TABLE_SCHEMA', '=', Capsule::connection()->getDatabaseName()],
                        ['TABLE_NAME', '=', self::TABLE_NAME],
                    ])->value('TABLE_ROWS');
                if (is_numeric($count)) {
                    $this->initialLogCount = $count;
                }
            } catch (Throwable $e) {
            }
            if (!Options::has(self::MAX_COUNT_AUTO_CLEAN_OPTION_NAME)) {
                Options::set(self::MAX_COUNT_AUTO_CLEAN_OPTION_NAME, self::CLEAN_LOG_COUNT);
                $maxLogCount = self::CLEAN_LOG_COUNT;
            } else {
                $maxLogCount = Options::get(self::MAX_COUNT_AUTO_CLEAN_OPTION_NAME);
                $maxLogCount = is_string($maxLogCount) ? trim($maxLogCount) : $maxLogCount;
            }
            if (is_numeric($maxLogCount) && $maxLogCount > 0) {
                $this->limitLogRecords = (int)$maxLogCount;
            }
            if (!Options::has(self::DISABLE_AUTO_CLEAN_OPTION_NAME)) {
                Options::set(self::DISABLE_AUTO_CLEAN_OPTION_NAME, 'no');
                return;
            } else {
                $disableAutoClean = Options::get(self::DISABLE_AUTO_CLEAN_OPTION_NAME);
                $disableAutoClean = is_string($disableAutoClean)
                    ? strtolower(trim($disableAutoClean))
                    : $disableAutoClean;
                if (in_array($disableAutoClean, ['yes', '1', 'true', 'on', true, 1], true)) {
                    return;
                }
            }

            $initialLogCount = $this->getInitialLogCount();
            $limitLogCount = $this->getLimitLogRecords();
            if ($initialLogCount >= $limitLogCount) {
                try {
                    // delete and truncate only 100K
                    // delete oldest first by timestamp
                    Capsule::table(self::TABLE_NAME)
                        ->orderBy('timestamp', 'asc')
                        ->limit($initialLogCount - $limitLogCount)
                        ->delete();
                } catch (Throwable $e) {
                    // next
                }
            }
        } finally {
            $performance->stop();
        }
    }

    /**
     * @return int
     */
    public function getInitialLogCount(): int
    {
        return $this->initialLogCount;
    }

    /**
     * Get limit log records
     *
     * @return int
     */
    public function getLimitLogRecords(): int
    {
        return $this->limitLogRecords;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Doing write
     *
     * @return void
     */
    private function saveDatabase(): void
    {
        $this->init();
        if (!$this->isEnabled()) {
            $this->queue = [];
            return;
        }
        if (empty($this->queue)) {
            return;
        }
        $performance = Performance::profile('save', 'system.database_writer')
            ->setData([
                'queue' => count($this->queue),
            ]);
        try {
            $insert = [];
            while ($record = array_shift($this->queue)) {
                try {
                    set_error_handler(static function ($errNo, $errStr) {
                        throw new UnprocessableException($errStr, $errNo);
                    });
                    $record['context'] = $record['context'] !== null
                        ? Serialization::safeSerialize($record['context'])
                        : null;
                    $record['extra'] = $record['extra'] !== null
                        ? Serialization::safeSerialize($record['extra'])
                        : null;
                } catch (Throwable $e) {
                    $record['context'] = null;
                    $record['extra'] = null;
                } finally {
                    restore_error_handler();
                }
                $record['context'] = $record['context'] === null || is_string($record['context'])
                    ? $record['context']
                    : null;
                $record['extra'] = $record['extra'] === null || is_string($record['extra'])
                    ? $record['extra']
                    : null;
                $insert[] = [
                    'level' => $record['level'],
                    'message' => $record['message'],
                    'context' => $record['context'],
                    'extra' => $record['extra'],
                    'timestamp' => time(),
                    'hash' => md5($record['message'] . $record['level'])
                ];
                $record = null;
                unset($record); // free memory
                if (count($insert) >= self::MAX_BATCH_INSERT) {
                    try {
                        Capsule::table(self::TABLE_NAME)->insert($insert);
                    } catch (Throwable $e) {
                        // next
                    }
                    $insert = [];
                }
            }
            if (!empty($insert)) {
                try {
                    Capsule::table(self::TABLE_NAME)->insert($insert);
                } catch (Throwable $e) {
                    // next
                }
                $insert = [];
                unset($insert);
            }
        } finally {
            $performance->stop();
        }
    }

    /**
     * Truncate table
     *
     * @return void
     */
    public function emptyTable()
    {
        Capsule::table(self::TABLE_NAME)->truncate();
    }

    /**
     * @param int $limit
     * @param int $timeCheck in seconds compare of same hash checking
     * @return bool
     */
    public function cleanDuplicates(int $limit = 500, int $timeCheck = 3600) : bool
    {
        if ($limit < 1) {
            return true;
        }
        $timeCheck = max($timeCheck, 60);
        $limit = min(self::MAX_DUPLICATION_CLEAN, $limit);
        $tableName = self::TABLE_NAME;
        $sql = <<<SQL
DELETE
    t1
FROM
    `$tableName` AS t1
JOIN (
        SELECT t2.* FROM
    `$tableName` AS t1,
    `$tableName` AS t2
    WHERE
        (
            t1.hash = t2.hash AND t1.id < t2.id AND (
                t1.timestamp = t2.timestamp OR(
                    CAST(
                        (
                            CAST(t2.timestamp AS SIGNED) - CAST(t1.timestamp AS SIGNED)
                        ) AS SIGNED
                    ) < $timeCheck
                )
            )
        )
    LIMIT $limit
    ) AS t2 ON t1.id = t2.id
SQL;

        return Capsule::connection()->statement($sql);
    }

    /**
     * @inheritDoc
     */
    public function write(array $record): void
    {
        if (!$this->isEnabled()) {
            $this->queue = [];
            return;
        }
        if (($record['message']??null) === null) {
            return;
        }

        try {
            $level = $record['level']??null;
            if (is_string($level)) {
                $level = is_numeric($level) ? (int) $level : Logger::toMonologLevel($level);
            }
            if (!is_int($level)) {
                $level = 0;
            }
        } catch (Throwable $e) {
            $level = 0;
        }
        $context = $record['context']??null;
        $extra = $record['extra']??null;
        $context = $context === null ? null : (is_array($context) ? $context : []);
        $extra = $extra === null ? null : (is_array($extra) ? $extra : []);
        $this->queue[] = [
            'level' => $level,
            'message' => $record['message']??null,
            'context' => $context,
            'extra' => $extra,
        ];
        if (count($this->queue) >= self::MAX_QUEUE) {
            $this->saveDatabase();
        }
    }

    /**
     * Magic method destruct - write the log
     */
    public function __destruct()
    {
        $this->saveDatabase();
    }
}
