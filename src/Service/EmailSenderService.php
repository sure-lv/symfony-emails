<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Transport as Transport;
use SureLv\Emails\Transport\TransportInterface;

class EmailSenderService
{

    private ?TransportInterface $transport = null;

    public function __construct(?TransportInterface $transport, EmailsLogger $logger)
    {
        if ($transport) {
            $transport->setLogger($logger);
            $this->transport = $transport;
        }
    }

    public function getTransport(): Transport\TransportInterface
    {
        if (!$this->transport) {
            throw new \Exception('Transport not set');
        }
        return $this->transport;
    }

}