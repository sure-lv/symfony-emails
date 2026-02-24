<?php

namespace SureLv\Emails\Transport\Ses;

use SureLv\Emails\Exception\Aws\SesConfigurationException;
use SureLv\Emails\Exception\Aws\SesPermanentFailureException;
use SureLv\Emails\Exception\Aws\SesTemporaryFailureException;
use SureLv\Emails\Exception\Aws\SesThrottlingException;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use SureLv\Emails\Service\EmailsLogger;

class SesAdapter
{
    private SesClient $sesClient;

    public function __construct(string $key, string $secret, string $region, private EmailsLogger $logger)
    {
        $config = [
            'version' => 'latest',
            'region'  => $region,
        ];

        // Add credentials if provided
        if (!empty($key) && !empty($secret)) {
            $config['credentials'] = [
                'key'    => $key,
                'secret' => $secret,
            ];
        }

        $this->sesClient = new SesClient($config);
    }

    /**
     * Send email via AWS SES
     * 
     * @param array<string, mixed> $emailData Email data structure
     * @return string SES MessageId
     * @throws SesThrottlingException When rate limit exceeded (retryable)
     * @throws SesTemporaryFailureException When temporary failure (retryable)
     * @throws SesPermanentFailureException When permanent failure (not retryable)
     * @throws SesConfigurationException When configuration issue
     * 
     * Example $emailData structure:
     * [
     *     'from' => 'sender@example.com',
     *     'to' => 'recipient@example.com',  // or ['email1@example.com', 'email2@example.com']
     *     'subject' => 'Email subject',
     *     'html' => '<html>...</html>',
     *     'text' => 'Plain text version',  // Optional
     *     'reply_to' => 'reply@example.com',  // Optional
     *     'cc' => ['cc@example.com'],  // Optional
     *     'bcc' => ['bcc@example.com'],  // Optional
     *     'tags' => [  // Optional
     *         ['name' => 'key1', 'value' => 'value1'],
     *         ['name' => 'key2', 'value' => 'value2'],
     *     ],
     *     'configuration_set' => 'my-config-set',  // Optional, overrides default
     * ]
     */
    public function send(array $emailData): string
    {
        $this->validateEmailData($emailData);

        try {
            $payload = $this->buildSesPayload($emailData);
            
            $this->logger->logInfo('Sending email via SES', [
                'from' => $emailData['from'],
                'to' => is_array($emailData['to']) ? implode(',', $emailData['to']) : $emailData['to'],
                'subject' => $emailData['subject'],
            ]);

            $result = $this->sesClient->sendEmail($payload);
            
            $sesMessageId = $result->get('MessageId');
            
            $this->logger->logInfo('Email sent successfully via SES', [
                'ses_message_id' => $sesMessageId,
            ]);

            return $sesMessageId;

        } catch (AwsException $e) {
            $this->handleSesError($e, $emailData);
            // handleSesError throws specific exceptions, this line won't be reached
            throw new \RuntimeException('Unexpected state after SES error');
        }
    }

    /**
     * Validate email data structure
     * 
     * @param array<string, mixed> $emailData Email data structure
     * @throws \InvalidArgumentException
     */
    private function validateEmailData(array $emailData): void
    {
        $required = ['from', 'to', 'subject', 'html'];
        
        foreach ($required as $field) {
            if (empty($emailData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate from email (can be formatted like "Name <email@example.com>")
        $fromEmail = $this->extractEmail($emailData['from']);
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid from email: {$emailData['from']}");
        }

        // Validate to emails
        $toEmails = is_array($emailData['to']) ? $emailData['to'] : [$emailData['to']];
        foreach ($toEmails as $email) {
            $cleanEmail = $this->extractEmail($email);
            if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid to email: {$email}");
            }
        }

        // Validate reply_to if provided
        if (!empty($emailData['reply_to'])) {
            $replyToEmails = is_array($emailData['reply_to']) ? $emailData['reply_to'] : [$emailData['reply_to']];
            foreach ($replyToEmails as $email) {
                $cleanEmail = $this->extractEmail($email);
                if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid reply_to email: {$email}");
                }
            }
        }

        // Validate CC if provided
        if (!empty($emailData['cc'])) {
            $ccEmails = is_array($emailData['cc']) ? $emailData['cc'] : [$emailData['cc']];
            foreach ($ccEmails as $email) {
                $cleanEmail = $this->extractEmail($email);
                if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid cc email: {$email}");
                }
            }
        }

        // Validate BCC if provided
        if (!empty($emailData['bcc'])) {
            $bccEmails = is_array($emailData['bcc']) ? $emailData['bcc'] : [$emailData['bcc']];
            foreach ($bccEmails as $email) {
                $cleanEmail = $this->extractEmail($email);
                if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid bcc email: {$email}");
                }
            }
        }
    }

    /**
     * Extract email address from formatted string
     * Handles both "email@example.com" and "Name <email@example.com>" formats
     * 
     * @param string $email
     * @return string
     */
    private function extractEmail(string $email): string
    {
        // Check if email is in format "Name <email@example.com>"
        if (preg_match('/<(.+?)>/', $email, $matches)) {
            return trim($matches[1]);
        }
        
        // Return as-is if no angle brackets found
        return trim($email);
    }

    /**
     * Build SES API payload from email data
     * 
     * @param array<string, mixed> $emailData Email data structure
     * @return array<string, mixed> SES API payload
     */
    private function buildSesPayload(array $emailData): array
    {
        $payload = [
            'Source' => $emailData['from'],
            'Destination' => [
                'ToAddresses' => is_array($emailData['to']) ? $emailData['to'] : [$emailData['to']],
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $emailData['subject'],
                    'Charset' => 'UTF-8',
                ],
                'Body' => [
                    'Html' => [
                        'Data' => $emailData['html'],
                        'Charset' => 'UTF-8',
                    ],
                ],
            ],
        ];

        // Add plain text body if provided
        if (!empty($emailData['text'])) {
            $payload['Message']['Body']['Text'] = [
                'Data' => $emailData['text'],
                'Charset' => 'UTF-8',
            ];
        }

        // Add CC addresses if provided
        if (!empty($emailData['cc'])) {
            $payload['Destination']['CcAddresses'] = is_array($emailData['cc']) 
                ? $emailData['cc'] 
                : [$emailData['cc']];
        }

        // Add BCC addresses if provided
        if (!empty($emailData['bcc'])) {
            $payload['Destination']['BccAddresses'] = is_array($emailData['bcc']) 
                ? $emailData['bcc'] 
                : [$emailData['bcc']];
        }

        // Add Reply-To if provided
        if (!empty($emailData['reply_to'])) {
            $payload['ReplyToAddresses'] = is_array($emailData['reply_to']) 
                ? $emailData['reply_to'] 
                : [$emailData['reply_to']];
        }

        // Add Configuration Set (use provided or default)
        $configSet = $emailData['configuration_set'] ?? '';
        if (!empty($configSet)) {
            $payload['ConfigurationSetName'] = $configSet;
        }

        // Add tags if provided
        if (!empty($emailData['tags']) && is_array($emailData['tags'])) {
            $tags = [];
            foreach ($emailData['tags'] as $tag) {
                if (isset($tag['name']) && isset($tag['value'])) {
                    $tags[] = [
                        'Name' => (string)$tag['name'],
                        'Value' => (string)$tag['value'],
                    ];
                }
            }
            if (!empty($tags)) {
                $payload['Tags'] = $tags;
            }
        }

        return $payload;
    }

    /**
     * Handle SES-specific errors and throw appropriate exceptions
     * 
     * @param AwsException $e
     * @param array<string, mixed> $emailData Email data structure
     * @return void
     * @throws SesThrottlingException
     * @throws SesTemporaryFailureException
     * @throws SesPermanentFailureException
     * @throws SesConfigurationException
     */
    private function handleSesError(AwsException $e, array $emailData): void
    {
        $errorCode = $e->getAwsErrorCode();
        $errorMessage = $e->getAwsErrorMessage();

        $logContext = [
            'from' => $emailData['from'] ?? 'unknown',
            'to' => $emailData['to'] ?? 'unknown',
            'subject' => $emailData['subject'] ?? 'unknown',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ];

        switch ($errorCode) {
            // Rate limiting - should retry with backoff
            case 'Throttling':
                $this->logger->logWarning('SES throttling - rate limit exceeded', $logContext);
                throw new SesThrottlingException(
                    "SES rate limit exceeded: {$errorMessage}",
                    0,
                    $e
                );

            // Temporary failures - should retry
            case 'ServiceUnavailable':
            case 'InternalFailure':
                $this->logger->logWarning('SES temporary failure', $logContext);
                throw new SesTemporaryFailureException(
                    "SES temporary failure: {$errorMessage}",
                    0,
                    $e
                );

            // Configuration issues - should not retry, needs fixing
            case 'MailFromDomainNotVerified':
            case 'ConfigurationSetDoesNotExist':
            case 'AccountSendingPausedException':
                $this->logger->logCritical('SES configuration error', $logContext);
                throw new SesConfigurationException(
                    "SES configuration error: {$errorMessage}",
                    0,
                    $e
                );

            // Permanent failures - don't retry
            case 'MessageRejected':
            case 'InvalidParameterValue':
            case 'MailFromDomainNotVerifiedException':
                $this->logger->logCritical('SES permanent failure', $logContext);
                throw new SesPermanentFailureException(
                    "SES permanent failure: {$errorMessage}",
                    0,
                    $e
                );

            // Unknown error - treat as temporary
            default:
                $this->logger->logError('SES unknown error', $logContext);
                throw new SesTemporaryFailureException(
                    "SES unknown error: {$errorMessage}",
                    0,
                    $e
                );
        }
    }

    /**
     * Send raw email (for advanced use cases with attachments, custom headers, etc.)
     * 
     * @param string $rawMessage RFC 822 formatted message
     * @param array<string, mixed> $options Optional configuration
     * @return string SES MessageId
     */
    public function sendRaw(string $rawMessage, array $options = []): string
    {
        try {
            $payload = [
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
            ];

            // Add source if provided
            if (!empty($options['source'])) {
                $payload['Source'] = $options['source'];
            }

            // Add destinations if provided
            if (!empty($options['destinations'])) {
                $payload['Destinations'] = is_array($options['destinations']) 
                    ? $options['destinations'] 
                    : [$options['destinations']];
            }

            // Add Configuration Set
            $configSet = $options['configuration_set'] ?? '';
            if (!empty($configSet)) {
                $payload['ConfigurationSetName'] = $configSet;
            }

            $this->logger->logInfo('Sending raw email via SES');

            $result = $this->sesClient->sendRawEmail($payload);
            
            $sesMessageId = $result->get('MessageId');
            
            $this->logger->logInfo('Raw email sent successfully', [
                'ses_message_id' => $sesMessageId,
            ]);

            return $sesMessageId;

        } catch (AwsException $e) {
            $this->handleSesError($e, $options);
            throw new \RuntimeException('Unexpected state after SES error');
        }
    }

    /**
     * Verify an email address (useful for SES sandbox)
     */
    public function verifyEmailAddress(string $email): bool
    {
        try {
            $this->sesClient->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            $this->logger->logInfo('Email verification initiated', ['email' => $email]);
            return true;
        } catch (AwsException $e) {
            $this->logger->logError('Failed to verify email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if email address is verified
     */
    public function isEmailVerified(string $email): bool
    {
        try {
            $result = $this->sesClient->getIdentityVerificationAttributes([
                'Identities' => [$email],
            ]);

            $attributes = $result->get('VerificationAttributes');
            
            if (isset($attributes[$email])) {
                return $attributes[$email]['VerificationStatus'] === 'Success';
            }

            return false;
        } catch (AwsException $e) {
            $this->logger->logError('Failed to check verification status', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify a domain (for sending from any address @domain)
     */
    public function verifyDomain(string $domain): bool
    {
        try {
            $this->sesClient->verifyDomainIdentity([
                'Domain' => $domain,
            ]);

            $this->logger->logInfo('Domain verification initiated', ['domain' => $domain]);
            return true;
        } catch (AwsException $e) {
            $this->logger->logError('Failed to verify domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get send quota (useful for monitoring)
     * 
     * @return array<string, mixed>|null
     */
    public function getSendQuota(): ?array
    {
        try {
            $result = $this->sesClient->getSendQuota();
            return [
                'max_send_rate' => $result->get('MaxSendRate'),
                'max_24_hour_send' => $result->get('Max24HourSend'),
                'sent_last_24_hours' => $result->get('SentLast24Hours'),
            ];
        } catch (AwsException $e) {
            $this->logger->logError('Failed to get send quota', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get sending statistics
     * 
     * @return array<string, mixed>|null
     */
    public function getSendingStatistics(): ?array
    {
        try {
            $result = $this->sesClient->getSendStatistics();
            return $result->get('SendDataPoints');
        } catch (AwsException $e) {
            $this->logger->logError('Failed to get sending statistics', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * List verified email addresses
     * 
     * @return array<string, mixed>
     */
    public function listVerifiedEmails(): array
    {
        try {
            $result = $this->sesClient->listIdentities([
                'IdentityType' => 'EmailAddress',
            ]);
            return $result->get('Identities') ?? [];
        } catch (AwsException $e) {
            $this->logger->logError('Failed to list verified emails', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}