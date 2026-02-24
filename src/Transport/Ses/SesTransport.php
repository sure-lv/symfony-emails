<?php

namespace SureLv\Emails\Transport\Ses;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Exception\Aws as AwsExceptions;
use SureLv\Emails\Exception\Sender\SenderSendException;
use SureLv\Emails\Exception\Sender\SenderSendFailedException;
use SureLv\Emails\Exception\Sender\SenderSendInvalidArgumentException;
use SureLv\Emails\Exception\Sender\SenderSendWarningException;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Transport\AbstractTransport;

class SesTransport extends AbstractTransport
{

    private SesAdapter $adapter;

    public function __construct(array $transportConfig, private EmailsLogger $logger)
    {
        if (!class_exists(\Aws\Ses\SesClient::class)) {
            throw new \Exception('AWS SES PHP SDK not found');
        }
        
        if (!isset($transportConfig['key']) || !isset($transportConfig['secret']) || !isset($transportConfig['region'])) {
            throw new \InvalidArgumentException('Invalid transport configuration');
        }
        
        $this->adapter = new SesAdapter($transportConfig['key'], $transportConfig['secret'], $transportConfig['region'], $this->logger);

        parent::__construct();
    }

    /**
     * Send a Message entity via SES
     * 
     * @param EmailMessage $emailMessage
     * @return void
     * @throws SenderSendWarningException
     * @throws SenderSendFailedException
     * @throws SenderSendInvalidArgumentException
     * @throws SenderSendException
     */
    public function send(EmailMessage $emailMessage): void
    {
        $adapter = $this->adapter;
        
        try {
     
            $emailData = $this->convertMessageToRawEmailData($emailMessage);
            $this->messageId = $adapter->sendRaw($emailData['raw_message'], $emailData['options']);

        } catch (AwsExceptions\SesThrottlingException $e) {

            throw new SenderSendWarningException('SES throttling, will retry', $e, true);
            
        } catch (AwsExceptions\SesTemporaryFailureException $e) {

            throw new SenderSendWarningException('SES temporary failure, will retry', $e, true);
            
        } catch (AwsExceptions\SesConfigurationException $e) {

            throw new SenderSendFailedException('SES configuration error - requires admin attention', $e, false);
            
        } catch (AwsExceptions\SesPermanentFailureException $e) {

            throw new SenderSendFailedException('SES permanent failure - will not retry', $e, false);
            
        } catch (\InvalidArgumentException $e) {

            throw new SenderSendInvalidArgumentException('Invalid message data', $e, false);
            
        } catch (\Throwable $e) {

            throw new SenderSendException('Unexpected error sending email', $e, true);
            
        }
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Convert Message entity to email data array
     * 
     * @param EmailMessage $emailMessage
     * @return array<string, mixed>
     */
    private function convertMessageToEmailData(EmailMessage $emailMessage): array
    {
        $emailData = [
            'from' => $emailMessage->getFromEmail(),
            'to' => $emailMessage->getToEmail(),
            'subject' => $emailMessage->getSubject(),
            'html' => $emailMessage->getBodyHtml(),
        ];

        // Add optional fields
        if ($emailMessage->getBodyPlain()) {
            $emailData['text'] = $emailMessage->getBodyPlain();
        }

        if ($emailMessage->getReplyTo()) {
            $emailData['reply_to'] = $emailMessage->getReplyTo();
        }

        // Add tags from template metadata
        if ($emailMessage->getTemplateKey()) {
            $emailData['tags'] = [
                ['name' => 'template_key', 'value' => $emailMessage->getTemplateKey()],
            ];

            if ($emailMessage->getTemplateVersion()) {
                $emailData['tags'][] = [
                    'name' => 'template_version',
                    'value' => (string)$emailMessage->getTemplateVersion(),
                ];
            }
        }

        // Add message ID as tag for tracking
        if ($emailMessage->getId()) {
            $emailData['tags'][] = [
                'name' => 'message_id',
                'value' => (string)$emailMessage->getId(),
            ];
        }

        // Add custom headers if present
        $headers = $emailMessage->getHeaders();
        if (!empty($headers) && is_array($headers)) {
            $emailData['headers'] = $headers;
        }

        return $emailData;
    }

    /**
     * Convert Message entity to raw email data array
     * 
     * @param EmailMessage $emailMessage
     * @return array{raw_message: string, options: array<string, mixed>}
     */
    private function convertMessageToRawEmailData(EmailMessage $emailMessage): array
    {
        // $rawMessage RFC 822 formatted message
        $headerLines = [];
        $bodyLines = [];

        // Required headers
        $headerLines[] = 'From: ' . $emailMessage->getFromEmail();
        $headerLines[] = 'To: ' . $emailMessage->getToEmail();
        $headerLines[] = 'Subject: ' . $emailMessage->getSubject();

        // Add custom headers
        foreach ($emailMessage->getHeaders() ?? [] as $headerName => $headerValue) {
            $headerLines[] = $headerName . ': ' . $headerValue;
        }

        $bodyPlainLines = [];
        if ($emailMessage->getBodyPlain()) {
            $bodyPlainLines[] = 'Content-Type: text/plain; charset=UTF-8';
            $bodyPlainLines[] = 'Content-Transfer-Encoding: quoted-printable';
            $bodyPlainLines[] = '';
            $bodyPlainLines[] = $this->encodeQuotedPrintable($emailMessage->getBodyPlain());
        }

        $bodyHtmlLines = [];
        if ($emailMessage->getBodyHtml()) {
            $bodyHtmlLines[] = 'Content-Type: text/html; charset=UTF-8';
            $bodyHtmlLines[] = 'Content-Transfer-Encoding: base64';
            $bodyHtmlLines[] = '';
            $bodyHtmlLines[] = rtrim(chunk_split(base64_encode($emailMessage->getBodyHtml()), 76, "\r\n"));
        }

        if (count($bodyPlainLines) > 0 && count($bodyHtmlLines) > 0) {
            // Multipart alternative
            $boundaryId = 'boundary_' . md5(uniqid((string)time()));
            $headerLines[] = 'Content-Type: multipart/alternative; boundary="' . $boundaryId . '"';
            $headerLines[] = '';
            
            // Plain text part FIRST
            $bodyLines[] = '';
            $bodyLines[] = '--' . $boundaryId;
            $bodyLines = array_merge($bodyLines, $bodyPlainLines);
            
            // HTML part LAST
            $bodyLines[] = '';
            $bodyLines[] = '--' . $boundaryId;
            $bodyLines = array_merge($bodyLines, $bodyHtmlLines);
            
            // Closing boundary
            $bodyLines[] = '';
            $bodyLines[] = '--' . $boundaryId . '--';
        } elseif (count($bodyPlainLines) > 0) {
            // Plain text only
            $bodyLines =  array_merge($bodyLines, $bodyPlainLines);
        } elseif (count($bodyHtmlLines) > 0) {
            // HTML only
            $bodyLines = array_merge($bodyLines, $bodyHtmlLines);
        }

        $rawMessage = implode("\r\n", $headerLines) . "\r\n" . implode("\r\n", $bodyLines);

        // echo '<pre>' . print_r($rawMessage, true) . '</pre>'; die;

        $options = [];

        return [
            'raw_message' => $rawMessage,
            'options' => $options,
        ];
    }

    /**
     * Encode a string using quoted-printable encoding
     * 
     * @param string $str
     * @return string
     */
    private function encodeQuotedPrintable(string $str): string
    {
        return quoted_printable_encode($str);
    }

}