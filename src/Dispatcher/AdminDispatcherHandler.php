<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherHandlerApiInterface;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherHandlerInterface;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;
use function is_string;
use function iterator_to_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_length;
use function ob_get_level;
use function strtolower;

class AdminDispatcherHandler
{
    /**
     * @var AdminDispatcher $adminDispatcher the admin dispatcher
     */
    private AdminDispatcher $adminDispatcher;

    /**
     * @var SplObjectStorage<DispatcherHandlerInterface> $objectStorage
     */
    protected SplObjectStorage $objectStorage;

    /**
     * @var ?array{type: string, message: string} $message
     */
    protected ?array $message = null;

    /**
     * @var bool $processed if processed
     */
    private bool $processed = false;

    /**
     * @var ?DispatcherHandlerInterface $processedHandler the processed handler
     */
    private ?DispatcherHandlerInterface $processedHandler = null;

    /**
     * @param AdminDispatcher $dispatcher
     */
    public function __construct(AdminDispatcher $dispatcher)
    {
        $this->adminDispatcher = $dispatcher;
        $this->objectStorage = new SplObjectStorage();
    }

    /**
     * Get admin dispatcher
     *
     * @return AdminDispatcher
     */
    public function getAdminDispatcher(): AdminDispatcher
    {
        return $this->adminDispatcher;
    }

    /**
     * Detach handler
     *
     * @param DispatcherHandlerInterface $handler
     * @return void
     */
    public function remove(DispatcherHandlerInterface $handler) : void
    {
        $this->objectStorage->detach($handler);
    }

    /**
     * Check if the class has handler
     *
     * @param DispatcherHandlerInterface $handler
     * @return bool
     */
    public function has(DispatcherHandlerInterface $handler) : bool
    {
        return isset($this->objectStorage[$handler]);
    }

    /**
     * Add Handler
     *
     * @param DispatcherHandlerInterface $handler
     * @return void
     */
    public function add(DispatcherHandlerInterface $handler)
    {
        if (!$this->has($handler)) {
            $this->objectStorage->attach($handler);
        }
    }

    /**
     * @return array<DispatcherHandlerInterface>
     */
    public function getHandlers() : array
    {
        return iterator_to_array($this->objectStorage);
    }

    /**
     * Set the message
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public function setMessage(string $type, string $message)
    {
        $this->message = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function clearMessage()
    {
        $this->message = null;
    }

    /**
     * @return ?array{
     *     type: string,
     *     message: string
     * }
     */
    public function getMessage() : ?array
    {
        return $this->message;
    }

    /**
     * Check if the handler is processed
     *
     * @return bool
     */
    public function isProcessed() : bool
    {
        return $this->processed;
    }

    /**
     * Get processed handler
     *
     * @return ?DispatcherHandlerInterface
     * @noinspection PhpUnused
     */
    public function getProcessedHandler(): ?DispatcherHandlerInterface
    {
        return $this->processedHandler;
    }

    /**
     * Process the handler
     *
     * @param $vars
     * @param $handled
     * @param $error
     * @return DispatcherResponseInterface|string|bool
     */
    public function process($vars, &$handled = null, &$error = null)
    {
        if ($this->isProcessed()) {
            $error = new RuntimeException(
                'Already processed'
            );
            return false;
        }
        $this->processed = true;
        if (!$this->getAdminDispatcher()->getAdminService()->isAllowedAccessAddonPage()) {
            $error = new RuntimeException(
                'Access Denied'
            );
            $this->setMessage('error', 'Access Denied');
            return false;
        }
        $isApi = $this->getAdminDispatcher()->isApi();
        $page = $this->getAdminDispatcher()->getPage();
        $lowerPage = is_string($page) ? strtolower($page) : $page;
        $handled = false;
        foreach ($this->getHandlers() as $handler) {
            $isApiHandler = $handler instanceof DispatcherHandlerApiInterface;
            $handlerPage = $handler->getRoutePath();
            $isCaseSensitive = $handler->isCaseSensitivePage();
            $canBeProcess = ($isApi ? $isApiHandler : !$isApiHandler);
            if (!$canBeProcess) {
                continue;
            }
            if ($handlerPage !== '*') {
                if ($isCaseSensitive) {
                    if ($handlerPage !== $page) {
                        continue;
                    }
                } elseif (strtolower($handlerPage) !== $lowerPage) {
                    continue;
                }
            }
            // stop here
            if ($handler->isProcessable($vars)) {
                $handled = true;
                $level = ob_get_level();
                ob_start();
                try {
                    $result = $handler->process($vars, $this);
                } catch (Throwable $e) {
                    $error = $e;
                    return false;
                } finally {
                    /** @noinspection PhpConditionAlreadyCheckedInspection */
                    if (is_string($result)
                        || $result instanceof DispatcherResponseInterface
                        || ob_get_length() === 0
                    ) {
                        if ($level < ob_get_level()) {
                            ob_end_clean();
                        }
                    } else {
                        if ($level < ob_get_level()) {
                            $result = ob_get_clean();
                        }
                    }
                }
                $this->processedHandler = $handler;
                return $result;
            }
        }
        $error = new RuntimeException(
            'No handler found for page ' . $page
        );
        $this->setMessage('error', 'No handler found for page ' . $page);
        return false;
    }
}
