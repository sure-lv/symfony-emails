<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use Psr\Log\LoggerInterface;

class EmailsLogger
{
    private const LOG_ENABLED = true;
    private const PREFIX = 'EmailsLogger: ';

    private string $prefix = self::PREFIX;
    
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Set the prefix for the log messages
     * 
     * @param string $prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Reset the prefix to the default
     * 
     * @return self
     */
    public function resetPrefix(): self
    {
        $this->prefix = self::PREFIX;
        return $this;
    }

    /**
     * Log a message
     * 
     * @param string $message
     * @param string $type
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function log(string $message, string $type, array $data = []): void
    {
        /* @phpstan-ignore-next-line */
        if (!self::LOG_ENABLED || !$this->logger) {
            return;
        }

        $message = $this->prefix . $message;

        switch ($type) {
            case 'info':
                $this->logger->info($message, $data);
                break;
            case 'warning':
                $this->logger->warning($message, $data);
                break;
            case 'error':
                $this->logger->error($message, $data);
                break;
            case 'debug':
                $this->logger->debug($message, $data);
                break;
            default:
                throw new \InvalidArgumentException('Invalid log type');
        }
    }

    /**
     * 
     * Log an info message
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logInfo(string $message, array $data = []): void
    {
        $this->log($message, 'info', $data);
    }

    /**
     * Log a warning message
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logWarning(string $message, array $data = []): void
    {
        $this->log($message, 'warning', $data);
    }

    /**
     * Log an error message
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logError(string $message, array $data = []): void
    {
        $this->log($message, 'error', $data);
    }

    /**
     * Log a critical message
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logCritical(string $message, array $data = []): void
    {
        $this->log($message, 'critical', $data);
    }

    /**
     * Log a debug message
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logDebug(string $message, array $data = []): void
    {
        $this->log($message, 'debug', $data);
    }
}
