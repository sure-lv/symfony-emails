<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Transport\AbstractTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerTransport extends AbstractTransport
{

    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function processSend(array $emailData): ?string
    {
        $email = (new Email())
            ->from($emailData['from'])
            ->to($emailData['to'])
            ->subject($emailData['subject'])
            ;
        if (!empty($emailData['html'])) {
            $email->html($emailData['html']);
        }
        if (!empty($emailData['text'])) {
            $email->text($emailData['text']);
        }

        // Add reply to
        if (!empty($emailData['reply_to'])) {
            $email->replyTo($emailData['reply_to']);
        }

        // Add headers
        if (count($emailData['headers']) > 0) {
            foreach ($emailData['headers'] as $headerName => $headerValue) {
                $email->getHeaders()->addTextHeader($headerName, $headerValue);
            }
        }

        $this->mailer->send($email);

        return null;
    }

}