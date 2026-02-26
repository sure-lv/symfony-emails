<?php

namespace SureLv\Emails\Service;

// use SureLv\Emails\Config\EmailsConfig;
use SureLv\Emails\Transport as Transport;
use Symfony\Component\Mailer\MailerInterface;

class EmailSenderService
{
    
    private Transport\TransportInterface $transport;

    public function __construct(MailerInterface $mailer, EmailsLogger $logger)
    {
        $this->transport = new Transport\MailerTransport($mailer, $logger);
    }

    public function getTransport(): Transport\TransportInterface
    {
        return $this->transport;
    }

}