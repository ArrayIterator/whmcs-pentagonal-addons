<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Profilers;

use JsonSerializable;
use function array_merge_recursive;
use function memory_get_peak_usage;
use function memory_get_usage;
use function microtime;

final class Profiler implements JsonSerializable
{
    /**
     * @var GroupProfiler $groupProfiler the group profiler
     */
    private GroupProfiler $groupProfiler;

    /**
     * @var string $name the profiler name
     */
    private string $name;

    /**
     * @var int $startMemory the start memory
     */
    private int $startMemory;

    /**
     * @var int $endMemory the end memory
     */
    private int $endMemory;

    /**
     * @var int $memoryUsage the used memory
     */
    private int $memoryUsage;

    /**
     * @var int $startPeakMemory the memory peak start
     */
    private int $startPeakMemory;

    /**
     * @var int $endPeakMemory the memory peak end
     */
    private int $endPeakMemory;

    /**
     * @var float $start the start time
     */
    private float $start;

    /**
     * @var float $end the end time
     */
    private float $end;

    /**
     * @var float|null $firstEnded the first ended
     */
    private ?float $firstEnded = null;

    /**
     * @var array $data the data
     */
    private array $data;

    /**
     * @var bool $locked is locked end
     */
    private bool $locked = false;

    /**
     * @var ?string $stopCode the stop code
     */
    private ?string $stopCode = null;

    /**
     * @var float|null $elapsed the elapsed time
     */
    private float $elapsed;

    /**
     * Profiler constructor.
     *
     * @param GroupProfiler $groupProfiler
     * @param string $name
     * @param array $data
     */
    public function __construct(GroupProfiler $groupProfiler, string $name, array $data = [])
    {
        $this->groupProfiler = $groupProfiler;
        $this->name = $name;
        $this->data = $data;
        $this->start = microtime(true) * 1000;
        $this->startMemory = memory_get_usage(true);
        $this->startPeakMemory = memory_get_peak_usage(true);
    }

    /**
     * Set stopCode
     *
     * @param string $stopCode
     * @return $this
     */
    public function setStopCode(string $stopCode): self
    {
        $this->stopCode = $stopCode;
        return $this;
    }

    /**
     * Create a new profiler
     *
     * @param GroupProfiler $groupProfiler
     * @param string $name
     * @param array $data
     * @return self
     */
    public static function create(GroupProfiler $groupProfiler, string $name, array $data = []): self
    {
        return new self($groupProfiler, $name, $data);
    }

    /**
     * Migrate the profiler to another group
     *
     * @param GroupProfiler $groupProfiler
     * @return void
     */
    public function migrate(GroupProfiler $groupProfiler)
    {
        // check if group equals
        if ($groupProfiler === $this->getGroupProfiler()) {
            return;
        }

        $this->getGroupProfiler()->remove($this);
        $this->groupProfiler = $groupProfiler;
        $this->groupProfiler->add($this);
    }

    /**
     * Check if profiler attached into group
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function isAttached() : bool
    {
        return $this->getGroupProfiler()->isAttached($this);
    }

    /**
     * Get group profiler
     *
     * @return GroupProfiler
     */
    public function getGroupProfiler(): GroupProfiler
    {
        return $this->groupProfiler;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Lock the performance, this will work if has been ended
     *
     * @return self
     */
    public function lock() : self
    {
        // lock only if not ended
        if (!$this->isEnded()) {
            return $this;
        }
        $this->locked = true;
        return $this;
    }

    /**
     * Check if profiler locked
     *
     * @return bool
     */
    public function isLocked() : bool
    {
        return $this->locked;
    }

    /**
     * Set data
     *
     * @param array $data
     * @return Profiler
     */
    public function setData(array $data): self
    {
        if ($this->isLocked()) {
            return $this;
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Merge data
     *
     * @param array $data
     * @return self
     */
    public function mergeData(array $data) : self
    {
        if ($this->isLocked() || empty($data)) {
            return $this;
        }
        $this->data = array_merge_recursive($this->data, $data);
        return $this;
    }

    /**
     * Set data value
     *
     * @param string $key
     * @param $value
     * @return self
     */
    public function setDataValue(string $key, $value): self
    {
        if ($this->isLocked()) {
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get data value
     *
     * @return self
     */
    public function clearData(): self
    {
        if ($this->isLocked()) {
            return $this;
        }
        $this->data = [];
        return $this;
    }

    /**
     * Get data value
     *
     * @param string $key
     * @return mixed|null
     */
    public function getDataValue(string $key)
    {
        return $this->data[$key]??null;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the profiler is ended
     *
     * @return bool
     */
    public function isEnded(): bool
    {
        return isset($this->end);
    }

    /**
     * Get the start time
     *
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->start;
    }

    /**
     * Get the end time
     *
     * @return float
     */
    public function getEndTime(): float
    {
        return $this->end??(microtime(true) * 1000);
    }

    /**
     * @return int
     */
    public function getStartMemory(): int
    {
        return $this->startMemory;
    }

    /**
     * @return int
     */
    public function getEndMemory(): int
    {
        return $this->endMemory??memory_get_usage(true);
    }

    /**
     * Get start peak memory
     *
     * @return int
     */
    public function getStartPeakMemory(): int
    {
        return $this->startPeakMemory;
    }

    /**
     * Get end peak memory
     *
     * @return int
     */
    public function getEndPeakMemory(): int
    {
        return $this->endPeakMemory??memory_get_peak_usage(true);
    }

    /**
     * Get the first ended
     *
     * @return float|null
     */
    public function getFirstEnded(): ?float
    {
        return $this->firstEnded;
    }

    /**
     * End the profiler
     *
     * @param bool $force
     * @param array $data
     * @param string|null $code
     * @return void
     */
    public function end(bool $force = false, array $data = [], ?string $code = null): self
    {
        // prevent stop when stop code is invalid
        if ($this->stopCode !== null && $this->stopCode !== $code) {
            return $this;
        }

        try {
            if (!isset($this->end)) {
                $this->firstEnded = microtime(true) * 1000;
                $this->end = $this->firstEnded;
                $this->elapsed = $this->end - $this->start;
                $this->endMemory = memory_get_usage(true);
                $this->endPeakMemory = memory_get_peak_usage(true);
                return $this;
            }
            if ($force && !$this->isLocked()) {
                $this->end = microtime(true) * 1000;
                $this->mergeData($data);
                $this->elapsed = $this->end - $this->start;
                $this->endMemory = memory_get_usage(true);
                $this->endPeakMemory = memory_get_peak_usage(true);
            }
        } finally {
            $this->memoryUsage ??= $this->endMemory > $this->startMemory
                ? $this->endMemory - $this->startMemory
                : 0;
        }
        return $this;
    }

    /**
     * Stop and lock
     *
     * @param array $data
     * @param string|null $code
     * @return self
     */
    public function stop(array $data = [], ?string $code = null) : self
    {
        $this->end(false, $data, $code);
        $this->lock();
        return $this;
    }

    /**
     * Get the time
     *
     * @return float
     */
    public function getElapsedTime(): float
    {
        if (isset($this->elapsed)) {
            return $this->elapsed;
        }
        return $this->getEndTime() - $this->getStartTime();
    }

    /**
     * Get the memory usage
     * @return int the memory usage
     */
    public function getMemoryUsage(): int
    {
        if (isset($this->memoryUsage)) {
            return $this->memoryUsage;
        }
        $start = $this->getStartMemory();
        $end = $this->getEndMemory();
        return $end > $start ? $end - $start : 0;
    }

    /**
     * @return array{
     *      "name": "string",
     *      "memory": array{
     *           "start" : int,
     *           "end" : int,
     *           "usage" : int,
     *           "peak_start" : int,
     *           "peak_end" : int,
     *      },
     *      "time": array{
     *           "start" : int,
     *           "end" : int,
     *           "usage" : int,
     *      },
     *      "data": array
     *  }
     */
    public function jsonSerialize() : array
    {
        $timeUsage = $this->getElapsedTime();
        $memoryUsage = $this->getMemoryUsage();
        return [
            'name' => $this->getName(),
            'memory' => [
                'start' => $this->getStartMemory(),
                'end' => $memoryUsage + $this->getStartMemory(),
                'usage' => $memoryUsage,
                'peak_start' => $this->getStartPeakMemory(),
                'peak_end' => $this->getEndPeakMemory(),
            ],
            'time' => [
                'start' => $this->getStartTime(),
                'end' => $timeUsage + $this->getStartTime(),
                'usage' => $timeUsage,
            ],
            'data' => $this->getData()
        ];
    }
}
