<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Config\EmailsConfig;
use SureLv\Emails\Transport as Transport;

class EmailSenderService
{
    
    private Transport\TransportInterface $transport;

    public function __construct(EmailsConfig $emailsConfig, EmailsLogger $logger)
    {
        $transport = $emailsConfig->transport;
        $transportConfig = $emailsConfig->transportConfig;

        if ($transport === 'ses') {
            $this->transport = new Transport\Ses\SesTransport($transportConfig, $logger);
        } elseif ($transport === 'smtp') {
            $this->transport = new Transport\Smtp\SmtpTransport($transportConfig, $logger);
        }
    }

    public function getProvider(): Transport\TransportInterface
    {
        return $this->transport;
    }

}