<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter;

use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;
use function is_bool;

class SettingConverter
{
    /**
     * @var ObjectItemContract $contract Contract of the tag
     */
    protected ObjectItemContract $contract;

    /**
     * @var Schema $refSchema Reference Schema
     */
    protected Schema $refSchema;

    /**
     * @var bool|null $valid Cache of the valid
     */
    private ?bool $valid = null;

    /**
     * @param Schema $refSchema
     * @param ObjectItemContract $contract
     */
    public function __construct(Schema $refSchema, ObjectItemContract $contract)
    {
        $this->contract = $contract;
        $this->refSchema = $refSchema;
    }

    /**
     * @return Schema
     */
    public function getRefSchema(): Schema
    {
        return $this->refSchema;
    }

    /**
     * @return ObjectItemContract
     */
    public function getContract(): ObjectItemContract
    {
        return $this->contract;
    }

    public function createObjectContract(ObjectItemContract $contract)
    {
        // todo complete this
    }

    /**
     * Get the contract
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }
        $this->valid = $this->contract->valid();
        if (!$this->valid) {
            return false;
        }
        try {
            $this->getRefSchema()->in($this->contract);
        } catch (Throwable $e) {
            $this->valid = false;
        }
        return $this->valid;
    }
}
