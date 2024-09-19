<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\LoggerHandler;

use Monolog\Handler\AbstractProcessingHandler;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\LogWriterInterface;

/**
 * Log Handler - the default log handler
 */
class LogHandler extends AbstractProcessingHandler
{
    /**
     * @var array<LogWriterInterface> $writers
     */
    protected array $writers = [];

    /**
     * Push the writer
     *
     * @param LogWriterInterface $writer
     * @return void
     */
    public function pushWriter(LogWriterInterface $writer): void
    {
        $this->writers[] = $writer;
    }

    /**
     * Pop the writer
     *
     * @return void
     */
    public function popWriter(): void
    {
        array_pop($this->writers);
    }

    /**
     * Get the writers
     *
     * @return LogWriterInterface[]
     */
    public function getWriters(): array
    {
        return $this->writers;
    }

    /**
     * @inheritDoc
     */
    protected function write(array $record): void
    {
        foreach ($this->writers as $writer) {
            $writer->write($record);
        }
    }
}
