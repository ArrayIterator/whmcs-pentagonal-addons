<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Monolog\Logger as MonologLogger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\LoggerHandler\LogHandler;
use Pentagonal\Neon\WHMCS\Addon\Helpers\LogWriter\DatabaseWriter;
use Throwable;
use function call_user_func_array;
use function in_array;
use function is_string;
use function strtolower;
use function trim;

/**
 * @method static void emergency($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void critical($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void info($message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void log($level, $message, array $context = [])
 * @method static void addRecord($level, $message, array $context = [])
 * @method static void pushProcessor($callback)
 * @method static void popProcessor()
 * @method static void setHandlers(array $handlers)
 * @method static void getHandlers()
 * @method static void pushHandler($handler)
 * @method static void popHandler()
 * @method static void useMicrosecondTimestamps($micro)
 * @method static void addInfo($message, array $context = [])
 * @method static void addNotice($message, array $context = [])
 * @method static void addWarning($message, array $context = [])
 * @method static void addError($message, array $context = [])
 * @method static void addCritical($message, array $context = [])
 * @method static void addAlert($message, array $context = [])
 * @method static void addEmergency($message, array $context = [])
 * @method static void addDebug($message, array $context = [])
 * @method static Logger getLogger()
 * @method static Logger logger()
 *
 * @mixin MonologLogger
 */
final class Logger
{
    public const OPTION_LEVEL_NAME = 'system_log_level';

    /**
     * @var string LOGGER_NAME the logger name
     */
    public const LOGGER_NAME = 'pentagonal';

    /**
     * @var int EMERGENCY the emergency level
     */
    public const EMERGENCY = MonologLogger::EMERGENCY;

    /**
     * @var int ALERT the alert level
     */
    public const ALERT = MonologLogger::ALERT;

    /**
     * @var int CRITICAL the critical level
     */
    public const CRITICAL = MonologLogger::CRITICAL;

    /**
     * @var int ERROR the error level
     */
    public const ERROR = MonologLogger::ERROR;

    /**
     * @var int WARNING the warning level
     */
    public const WARNING = MonologLogger::WARNING;

    /**
     * @var int NOTICE the notice level
     */
    public const NOTICE = MonologLogger::NOTICE;

    /**
     * @var int INFO the info level
     */
    public const INFO = MonologLogger::INFO;

    /**
     * @var int DEBUG the debug level
     */
    public const DEBUG = MonologLogger::DEBUG;

    /**
     * @var int API the api level
     */
    public const API = MonologLogger::API;

    /**
     * @var MonologLogger $logger the logger
     */
    private MonologLogger $logger;

    /**
     * @var Logger $instance the instance
     */
    private static self $instance;

    /**
     * @var DatabaseWriter $databaseWriter the database writer
     */
    private DatabaseWriter $databaseWriter;

    /**
     * Logger constructor.
     */
    private function __construct()
    {
    }

    /**
     * Get the instance
     *
     * @return Logger the instance
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return DatabaseWriter the database writer
     */
    public function getDatabaseWriter(): DatabaseWriter
    {
        return $this->databaseWriter ??= new DatabaseWriter();
    }

    /**
     * Get the logger
     *
     * @return MonologLogger the logger
     * @public
     * @static
     */
    protected function getLogger(): MonologLogger
    {
        if (isset($this->logger)) {
            return $this->logger;
        }
        $logLevel = MonologLogger::WARNING;
        $monologLevelName = MonologLogger::getLevelName($logLevel);
        if (!Options::has(self::OPTION_LEVEL_NAME)) {
            Options::set(self::OPTION_LEVEL_NAME, $monologLevelName);
        } else {
            $logLevel = Options::get(self::OPTION_LEVEL_NAME);
            $originalLevel = $logLevel;
            $logLevel = is_string($logLevel) ? strtolower(trim($logLevel)) : $monologLevelName;
            $update = false;
            try {
                $logLevel = MonologLogger::toMonologLevel($logLevel);
            } catch (Throwable $e) {
                $update = true;
                $logLevel = MonologLogger::WARNING;
            }
            $monologLevelName = MonologLogger::getLevelName($logLevel);
            if ($update || $originalLevel !== $monologLevelName) {
                Options::set(self::OPTION_LEVEL_NAME, $monologLevelName);
            }
        }

        $this->logger = new MonologLogger(self::LOGGER_NAME);
        // set level to notice
        $handler = new LogHandler($logLevel);

        // push writer
        $handler->pushWriter($this->getDatabaseWriter());
        $this->logger->pushHandler($handler);
        return $this->logger;
    }

    /**
     * Magic static caller
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->__call($name, $arguments);
    }

    /**
     * Magic caller
     */
    public function __call(string $name, array $arguments)
    {
        $lower = strtolower($name);
        if (in_array($lower, ['getlogger', 'logger'])) {
            return $this->getLogger();
        }
        return call_user_func_array([$this->getLogger(), $name], $arguments);
    }
}
