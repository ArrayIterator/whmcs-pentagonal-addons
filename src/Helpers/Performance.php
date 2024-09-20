<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use InvalidArgumentException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Profilers\GroupProfiler;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Profilers\Profiler;
use WHMCS\Database\Capsule;
use function is_object;
use function is_string;
use function print_r;

/**
 * Performance profiler - to record performance
 */
final class Performance
{
    public const DEFAULT_GROUP = 'default';

    /**
     * @var Performance $instance the instance
     */
    private static self $instance;

    /**
     * @var array<GroupProfiler> $groups the records
     */
    private array $groups = [];

    /**
     * @var bool $enabled enable or disable performance
     */
    private bool $enabled;

    /**
     * @var Profiler $dummyProfiler the dummy profiler
     */
    private Profiler $dummyProfiler;

    /**
     * Performance constructor.
     * @private Performance constructor.
     */
    private function __construct()
    {
        $isEnabled = ApplicationConfig::get('display_errors') === true;
        if (!$isEnabled) {
            $value = Capsule::table(Options::TABLE_OPTIONS)
                ->where('name', 'enable_profiler')
                ->first();
            if (is_object($value) && isset($value->value)) {
                $isEnabled = Serialization::shouldUnSerialize($value->value) === true;
            }
        }
        $this->enabled = $isEnabled;
    }

    /**
     * @return array<string, GroupProfiler>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Add group if not exists
     *
     * @param string|GroupProfiler $group
     * @return GroupProfiler
     */
    public function addGroup($group): GroupProfiler
    {
        if (is_string($group)) {
            return $this->groups[$group] ??= new GroupProfiler($group);
        }
        if ($group instanceof GroupProfiler) {
            return $this->groups[$group->getName()] ??= $group;
        }
        throw new InvalidArgumentException('Invalid Group');
    }

    /**
     * Replace group
     *
     * @param GroupProfiler $groupProfiler
     * @return GroupProfiler
     */
    public function replaceGroup(GroupProfiler $groupProfiler): GroupProfiler
    {
        return $this->groups[$groupProfiler->getName()] = $groupProfiler;
    }

    /**
     * Add group
     *
     * @param string $name
     * @return GroupProfiler
     */
    public function getGroup(string $name): ?GroupProfiler
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * Add group
     *
     * @param string|GroupProfiler $group
     * @return void
     */
    public function removeGroup($group): void
    {
        if (is_string($group)) {
            unset($this->groups[$group]);
            return;
        }
        if ($group instanceof GroupProfiler) {
            $key = array_search($group, $this->groups, true);
            if ($key !== false) {
                unset($this->groups[$key]);
            }
        }
    }

    /**
     * Check if group exists
     *
     * @param string|GroupProfiler $group
     * @return bool
     */
    public function hasGroup($group): bool
    {
        if (is_string($group)) {
            return isset($this->groups[$group]);
        }
        if ($group instanceof GroupProfiler) {
            return in_array($group, $this->groups, true);
        }
        return false;
    }

    /**
     * Migrate the profiler from given group
     *
     * @param GroupProfiler $groupProfiler
     * @return void
     */
    public function migrateProfiler(GroupProfiler $groupProfiler) : GroupProfiler
    {
        $group = $this->getGroup($groupProfiler->getName());
        if (!$group) {
            $this->addGroup($groupProfiler);
            return $groupProfiler;
        }
        foreach ($groupProfiler as $profiler) {
            $profiler->migrate($group);
        }
        return $group;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable performance
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable performance
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Start profiling
     *
     * @param string $name
     * @param array $data
     * @param string $groupName
     * @return Profiler
     */
    public function start(string $name, string $groupName = self::DEFAULT_GROUP, array $data = []): Profiler
    {
        if (!$this->isEnabled()) {
            return $this->dummyProfiler ??= Profiler::create(new GroupProfiler(self::DEFAULT_GROUP), $name, $data);
        }
        return $this->addGroup($groupName)->profile($name, $data);
    }

    /**
     * Get instance
     *
     * @return Performance
     */
    public static function getInstance() : self
    {
        return self::$instance ??= new self();
    }

    /**
     * Perform profiling
     *
     * @param string $name
     * @param array $data
     * @param string $groupName
     * @return Profiler
     */
    public static function profile(string $name, string $groupName = self::DEFAULT_GROUP, array $data = []): Profiler
    {
        return self::getInstance()->start($name, $groupName, $data);
    }

    /**
     * @param string $groupName
     * @return GroupProfiler
     */
    public static function add(string $groupName): GroupProfiler
    {
        return self::getInstance()->addGroup($groupName);
    }

    /**
     * Get group profiler
     *
     * @param string $groupName
     * @return GroupProfiler
     */
    public static function get(string $groupName): GroupProfiler
    {
        return self::getInstance()->getGroup($groupName);
    }

    /**
     * Check if group exists
     *
     * @param string|GroupProfiler $group
     * @return bool
     */
    public static function has($group): bool
    {
        return self::getInstance()->hasGroup($group);
    }

    /**
     * Remove group
     *
     * @param string|GroupProfiler $group
     * @return void
     */
    public static function remove($group): void
    {
        self::getInstance()->removeGroup($group);
    }

    /**
     * Replace group
     *
     * @uses Performance::replaceGroup
     * @param GroupProfiler $groupProfiler
     * @return GroupProfiler
     */
    public static function replace(GroupProfiler $groupProfiler): GroupProfiler
    {
        return self::getInstance()->replaceGroup($groupProfiler);
    }
}
