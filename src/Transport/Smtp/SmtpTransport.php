<?php

namespace SureLv\Emails\Transport\Smtp;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Transport\AbstractTransport;

class SmtpTransport extends AbstractTransport
{

    public function __construct(array $transportConfig, private EmailsLogger $logger)
    {
        echo '<pre>' . print_r(['smtp', $transportConfig], true) . '</pre>'; die;
        
        parent::__construct();
    }

    public function send(EmailMessage $emailMessage): void
    {
        throw new \Exception('Not implemented');
    }

}