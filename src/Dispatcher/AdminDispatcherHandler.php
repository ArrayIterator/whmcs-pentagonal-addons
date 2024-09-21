<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherHandlerApiInterface;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherHandlerInterface;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;
use function is_object;
use function is_string;
use function iterator_to_array;
use function method_exists;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
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
     * Check if handler is processable
     *
     * @param DispatcherHandlerInterface $handler
     * @param $vars
     * @return bool
     */
    public function isProcessable(DispatcherHandlerInterface $handler, $vars) : bool
    {
        $isApi = $this->getAdminDispatcher()->isApiRequest();
        $routeQ = $this->getAdminDispatcher()->getRouteQuery();
        $handlerPage = $handler->getRoutePath();
        $isCaseSensitive = $handler->isCaseSensitivePage();
        $lowerPage = is_string($routeQ) ? strtolower($routeQ) : $routeQ;
        if ($handlerPage !== '*') { // process if *
            if ($isCaseSensitive) {
                if ($handlerPage !== $routeQ) {
                    return false;
                }
            } elseif (strtolower($handlerPage) !== $lowerPage) {
                return false;
            }
        }

        return $handler->isProcessable($vars) && (
            !$isApi && !$handler instanceof DispatcherHandlerApiInterface
            || $isApi && $handler instanceof DispatcherHandlerApiInterface
        );
    }

    /**
     * Clear buffer
     *
     * @param int $previousLevel
     * @return void
     */
    private function clearBuffer(int $previousLevel) : void
    {
        if ($previousLevel < ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Process the handler
     *
     * @param $vars
     * @param $handled
     * @param $error
     */
    public function process($vars, &$handled = null, &$error = null)
    {
        $handled = false;
        if ($this->isProcessed()) {
            $error = new RuntimeException(
                'Already processed'
            );
            return false;
        }
        $this->processed = true;
        if (!$this->getAdminDispatcher()->getCore()->getAddon()->isAllowedAccessAddonPage()) {
            $error = new RuntimeException(
                'Access Denied'
            );
            $this->setMessage('error', 'Access Denied');
            return false;
        }
        foreach ($this->getHandlers() as $handler) {
            if (!$this->isProcessable($handler, $vars)) {
                continue;
            }

            $handled = true;
            $level = ob_get_level();
            ob_start();
            try {
                $result = $handler->process($vars, $this);
            } catch (Throwable $e) {
                $error = $e;
                return false;
            }
            $httpFactory = $this->getAdminDispatcher()->getCore()->getHttpFactory();
            if ($result instanceof DispatcherResponseInterface
                || $result instanceof ResponseInterface
                || $result instanceof StreamInterface
            ) {
                $this->clearBuffer($level);
            } elseif (is_string($result) || is_object($result) && method_exists($result, '__toString')) {
                $this->clearBuffer($level);
                $result = $httpFactory->getStreamFactory()->createStream((string) $result);
            } else {
                if ($level < ob_get_level()) {
                    $result = $httpFactory->getStreamFactory()->createStream((string) ob_get_clean());
                }
            }
            $this->processedHandler = $handler;
            return $result;
        }
        $page = $this->getAdminDispatcher()->getRouteQuery()??'(null)';
        $error = new RuntimeException(
            'No handler found for page ' . $page
        );
        $this->setMessage('error', 'No handler found for page ' . $page);
        return false;
    }
}
