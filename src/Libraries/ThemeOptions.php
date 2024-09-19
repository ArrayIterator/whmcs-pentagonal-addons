<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Options;
use function is_array;
use function preg_replace;
use function strlen;

class ThemeOptions
{
    public const THEME_PREFIX = 'pentagonal_theme_option_';

    public const TOTAL_MAX_LENGTH = 255;

    private string $optionName;

    private string $originalOptionName;

    /**
     * @var ?array $options
     */
    protected ?array $options = null;

    /**
     * @var bool $needsUpdate
     */
    protected bool $needsUpdate = false;

    /**
     * ThemeOptions constructor.
     *
     * @param string $optionName
     */
    public function __construct(string $optionName)
    {
        $this->originalOptionName = $optionName;
        $optionName = preg_replace('/[^a-zA-Z0-9_]/', '', $optionName);
        $optionName = self::THEME_PREFIX . $optionName;
        if (strlen($optionName) > self::TOTAL_MAX_LENGTH) {
            $optionName = substr($optionName, 0, self::TOTAL_MAX_LENGTH);
        }
        $this->optionName = $optionName;
    }

    /**
     * Option Name
     *
     * @return string
     */
    public function getOptionName(): string
    {
        return $this->optionName;
    }

    /**
     * Original Option Name
     *
     * @return string
     */
    public function getOriginalOptionName(): string
    {
        return $this->originalOptionName;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getOptions(): array
    {
        if ($this->options === null) {
            $options = Options::get($this->getOptionName(), $exists);
            if ($exists && !is_array($options)) {
                $this->needsUpdate = true;
                $options = [];
            }
            $this->options = is_array($options) ? $options : [];
        }
        return $this->options;
    }

    /**
     * Set the option
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->getOptions();
        $this->options[$key] = $value;
        $this->needsUpdate = true;
        return $this;
    }

    /**
     * Get the option
     *
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        $options = $this->getOptions();
        return $options[$key] ?? $default;
    }

    /**
     * Remove the option
     *
     * @param string $key
     * @return $this
     */
    public function remove(string $key): self
    {
        $this->getOptions();
        unset($this->options[$key]);
        $this->needsUpdate = true;
        return $this;
    }

    /**
     * Save the options
     *
     * @return bool true if success
     */
    public function save(): bool
    {
        if ($this->needsUpdate) {
            $this->needsUpdate = false;
            return Options::set($this->getOptionName(), $this->options);
        }
        return true;
    }

    public function __destruct()
    {
        $this->save();
    }
}
