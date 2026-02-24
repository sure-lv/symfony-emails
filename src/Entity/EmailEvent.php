<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\EmailEventType;
use SureLv\Emails\Util\DateTimeUtils;

final class EmailEvent
{

    private int $id = 0;

    private int $message_id = 0;

    private ?EmailEventType $event_type = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $payload = null;

    private ?\DateTime $occurred_at = null;

    private ?\DateTime $created_at = null;

    private ?EmailMessage $email_message = null;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $event = new self();
        $event->fromArray($data);
        return $event;
    }

    public function __construct() {}

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setMessageId(int $message_id): self
    {
        $this->message_id = $message_id;
        return $this;
    }

    public function getMessageId(): int
    {
        return $this->message_id;
    }

    public function setEventType(?EmailEventType $event_type): self
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function getEventType(): ?EmailEventType
    {
        return $this->event_type;
    }

    /**
     * Set payload
     * 
     * @param array<string, mixed>|string|null $payload
     * @return self
     */
    public function setPayload($payload): self
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        if (!is_array($payload)) {
            $payload = null;
        }
        $this->payload = $payload;
        return $this;
    }

    /**
     * Get payload
     * 
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setOccurredAt(?\DateTime $occurred_at): self
    {
        $this->occurred_at = $occurred_at;
        return $this;
    }

    public function getOccurredAt(): ?\DateTime
    {
        return $this->occurred_at;
    }

    public function setCreatedAt(?\DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setEmailMessage(?EmailMessage $email_message): self
    {
        $this->email_message = $email_message;
        return $this;
    }

    public function getEmailMessage(): ?EmailMessage
    {
        return $this->email_message;
    }

    public function prePersist(): self
    {
        if (!$this->occurred_at instanceof \DateTime) {
            $this->occurred_at = new \DateTime();
        }
        if (!$this->created_at instanceof \DateTime) {
            $this->created_at = new \DateTime();
        }
        return $this;
    }

    /**
	 * Set data from array
	 * 
	 * @param array<string, mixed> $data
	 * @param string $prfx
	 * @return self
	 */
	public function fromArray(array $data, string $prfx = ''): self
	{
        foreach ($data as $k => $v) {
            switch ($k) {
                case $prfx . 'id':
                    $this->setId($v);
                    break;
                case $prfx . 'message_id':
                    $this->setMessageId($v);
                    break;
                case $prfx . 'event_type':
                    $this->setEventType(EmailEventType::tryFromString($v));
                    break;
                case $prfx . 'payload':
                    $this->setPayload($v);
                    break;
                case $prfx . 'occurred_at':
                    $this->setOccurredAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v));
                    break;
            }
        }
        return $this;
    }

}

