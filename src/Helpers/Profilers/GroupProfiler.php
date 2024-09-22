<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Profilers;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use SplObjectStorage;
use Traversable;
use function iterator_to_array;

/**
 * Class GroupProfiler is a group of profilers
 * @template-implements Traversable<Profiler>
 */
final class GroupProfiler implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var string $name the group name
     */
    private string $name;

    /**
     * @var SplObjectStorage<Profiler> $profilers the profilers
     */
    private SplObjectStorage $profilers;

    /**
     * GroupProfiler constructor.
     *
     * @param string $groupName
     */
    public function __construct(string $groupName)
    {
        $this->name = $groupName;
        $this->profilers = new SplObjectStorage();
    }

    /**
     * Profile
     *
     * @param string $name
     * @param array $data
     * @return Profiler
     */
    public function profile(string $name, array $data = []): Profiler
    {
        $profiler = new Profiler($this, $name, $data);
        $this->add($profiler);
        return $profiler;
    }

    /**
     * Get group name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add profiler
     *
     * @param Profiler $profiler
     * @return void
     */
    public function add(Profiler $profiler): bool
    {
        // group should equal & profiler is not exists
        if ($profiler->getGroupProfiler() !== $this) {
            return false;
        }
        if (!isset($this->profilers[$profiler]) || $this->profilers[$profiler] !== $profiler) {
            $this->profilers->attach($profiler);
        }
        return true;
    }

    /**
     * Check if profiler is attached
     *
     * @param Profiler $profiler
     * @return bool
     */
    public function isAttached(Profiler $profiler): bool
    {
        return $this->profilers->contains($profiler);
    }

    /**
     * Remove profiler
     *
     * @param Profiler $profiler
     * @return void
     */
    public function remove(Profiler $profiler): void
    {
        unset($this->profilers[$profiler]);
    }

    /**
     * Check if has profiler
     *
     * @param Profiler $profiler
     * @return bool
     */
    public function has(Profiler $profiler): bool
    {
        return $this->profilers->contains($profiler);
    }

    /**
     * Clear all profilers
     *
     * @return void
     */
    public function clear(): void
    {
        $this->profilers = new SplObjectStorage();
    }

    /**
     * @return array<Profiler>
     */
    public function getProfilers(): array
    {
        return iterator_to_array($this->profilers);
    }

    /**
     * Count profiler
     *
     * @return int
     */
    public function count() : int
    {
        return $this->profilers->count();
    }

    /**
     * @return ArrayIterator<Profiler>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->getProfilers());
    }

    /**
     * @return array{
     *       "name": "string",
     *       "records": array<array{
     *          "name": "string",
     *          "memory": array{
     *              "start" : int,
     *              "end" : int,
     *              "usage" : int,
     *          },
     *          "time": array{
     *              "start" : int,
     *              "end" : int,
     *              "usage" : int,
     *          },
     *          "data": array
     *      }>
     *   }
     */
    public function jsonSerialize() : array
    {
        return [
            'name' => $this->getName(),
            'records' => $this->getProfilers()
        ];
    }
}
