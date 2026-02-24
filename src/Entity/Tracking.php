<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\EmailTrackingType;
use SureLv\Emails\Util\DateTimeUtils;

class Tracking
{

    private int $id = 0;

    private string $hash = '';

    private EmailTrackingType $type;

    private int $message_id;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    private ?\DateTime $event_at = null;
    
    private int $event_count = 0;

    private ?\DateTime $last_event_at = null;

    private \DateTime $created_at;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $tracking = new self();
        $tracking->fromArray($data);
        return $tracking;
    }

    /**
     * Generate hash
     * 
     * @param EmailTrackingType $type
     * @param int $messageId
     * @param array<string, mixed> $context
     * @return string
     */
    public static function generateHash(EmailTrackingType $type, int $messageId, array $context): string
    {
        return hash('sha256', $type->value . $messageId . json_encode($context));
    }

    /**
     * Generate new hash
     * 
     * @param int $length
     * @return string
     */
    public static function generateNewHash(int $length = 16): string
    {
        return bin2hex(random_bytes(max(1, $length)));
    }

    /**
     * Constructor
     * 
     * @param EmailTrackingType $type
     * @param int $messageId
     * @param array<string, mixed> $context
     */
    public function __construct(EmailTrackingType $type = EmailTrackingType::CLICK, int $messageId = 0, array $context = [])
    {
        $this->type = $type;
        $this->message_id = $messageId;
        $this->context = $context;
        $this->created_at = new \DateTime();
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setType(EmailTrackingType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): EmailTrackingType
    {
        return $this->type;
    }

    public function setMessageId(int $messageId): self
    {
        $this->message_id = $messageId;
        return $this;
    }

    public function getMessageId(): int
    {
        return $this->message_id;
    }

    /**
     * Set context
     * 
     * @param array<string, mixed> $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get context
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function setEventAt(?\DateTime $eventAt): self
    {
        $this->event_at = $eventAt;
        return $this;
    }

    public function getEventAt(): ?\DateTime
    {
        return $this->event_at;
    }

    public function setEventCount(int $eventCount): self
    {
        $this->event_count = $eventCount;
        return $this;
    }

    public function getEventCount(): int
    {
        return $this->event_count;
    }

    public function setLastEventAt(?\DateTime $lastEventAt): self
    {
        $this->last_event_at = $lastEventAt;
        return $this;
    }

    public function getLastEventAt(): ?\DateTime
    {
        return $this->last_event_at;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function prePersist(): self
    {
        if (empty($this->hash)) {
            $this->hash = self::generateNewHash(16);
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
                case $prfx . 'hash':
                    $this->setHash($v);
                    break;
                case $prfx . 'type':
                    $type = EmailTrackingType::tryFromString($v);
                    if (!$type) {
                        throw new \Exception('Invalid tracking type: ' . $v);
                    }
                    $this->setType($type);
                    break;
                case $prfx . 'message_id':
                    $this->setMessageId($v);
                    break;
                case $prfx . 'context':
                    if (is_string($v)) {
                        $v = json_decode($v, true);
                    }
                    if (!is_array($v)) {
                        $v = [];
                    }
                    $this->setContext($v);
                    break;
                case $prfx . 'event_at':
                    $this->setEventAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'event_count':
                    $this->setEventCount($v);
                    break;
                case $prfx . 'last_event_at':
                    $this->setLastEventAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v) ?? new \DateTime());
                    break;
            }
        }
        return $this;
    }

}