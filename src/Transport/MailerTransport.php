<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Transport\AbstractTransport;
use Symfony\Component\Mailer\MailerInterface;

class MailerTransport extends AbstractTransport
{

    public function __construct(private MailerInterface $mailer, private EmailsLogger $logger)
    {
        parent::__construct();
    }

    public function send(EmailMessage $emailMessage): void
    {
        throw new \Exception('Not implemented');
    }

}