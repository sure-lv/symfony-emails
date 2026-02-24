<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\EmailMessageKind;
use SureLv\Emails\Enum\EmailMessageSendStatus;
use SureLv\Emails\Util\DateTimeUtils;

final class EmailMessage
{  

    private int $id = 0;

    private ?int $job_id = null;

    private int $contact_id = 0;

    private string $subject = '';

    private string $from_email = '';

    private ?string $reply_to = null;

    private string $to_email = '';

    private string $body_html = '';

    private ?string $body_plain = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $headers = null;

    private ?string $sender_message_id = null;

    private EmailMessageKind $kind = EmailMessageKind::TRANSACTIONAL;

    private EmailMessageSendStatus $send_status = EmailMessageSendStatus::QUEUED;

    private ?\DateTimeInterface $sent_at = null;

    private ?\DateTimeInterface $failed_at = null;

    private ?string $fail_reason = null;

    private ?string $template_key = null;

    private ?int $template_version = null;

    private ?string $render_checksum_html = null;

    private ?string $render_checksum_text = null;

    private ?\DateTimeInterface $created_at = null;

    private ?Job $job = null;

    private ?Contact $contact = null;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $message = new self();
        $message->fromArray($data);
        return $message;
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

    public function setJobId(?int $job_id): self
    {
        $this->job_id = $job_id;
        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->job_id;
    }

    public function setContactId(int $contact_id): self
    {
        $this->contact_id = $contact_id;
        return $this;
    }

    public function getContactId(): int
    {
        return $this->contact_id;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setFromEmail(string $from_email): self
    {
        $this->from_email = $from_email;
        return $this;
    }

    public function getFromEmail(): string
    {
        return $this->from_email;
    }

    public function setReplyTo(?string $reply_to): self
    {
        $this->reply_to = $reply_to;
        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->reply_to;
    }

    public function setToEmail(string $to_email): self
    {
        $this->to_email = $to_email;
        return $this;
    }

    public function getToEmail(): string
    {
        return $this->to_email;
    }

    public function setBodyHtml(string $body_html): self
    {
        $this->body_html = $body_html;
        return $this;
    }

    public function getBodyHtml(): string
    {
        return $this->body_html;
    }

    public function setBodyPlain(?string $body_plain): self
    {
        $this->body_plain = $body_plain;
        return $this;
    }

    public function getBodyPlain(): ?string
    {
        return $this->body_plain;
    }

    /**
     * Set headers
     * 
     * @param array<string, mixed>|string|null $headers
     * @return self
     */
    public function setHeaders(mixed $headers): self
    {
        if (is_string($headers)) {
            $headers = json_decode($headers, true);
        }
        if (!is_array($headers) || count($headers) <= 0) {
            $headers = null;
        }
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get headers
     * 
     * @return array<string, mixed>|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setSenderMessageId(?string $sender_message_id): self
    {
        $this->sender_message_id = $sender_message_id;
        return $this;
    }

    public function getSenderMessageId(): ?string
    {
        return $this->sender_message_id;
    }

    public function setKind(EmailMessageKind $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    public function getKind(): EmailMessageKind
    {
        return $this->kind;
    }

    public function setSendStatus(EmailMessageSendStatus $send_status): self
    {
        $this->send_status = $send_status;
        return $this;
    }

    public function getSendStatus(): EmailMessageSendStatus
    {
        return $this->send_status;
    }

    public function setSentAt(?\DateTimeInterface $sent_at): self
    {
        $this->sent_at = $sent_at;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sent_at;
    }

    public function setFailedAt(?\DateTimeInterface $failed_at): self
    {
        $this->failed_at = $failed_at;
        return $this;
    }

    public function getFailedAt(): ?\DateTimeInterface
    {
        return $this->failed_at;
    }

    public function setFailReason(?string $fail_reason): self
    {
        $this->fail_reason = $fail_reason;
        return $this;
    }

    public function getFailReason(): ?string
    {
        return $this->fail_reason;
    }

    public function setTemplateKey(?string $template_key): self
    {
        $this->template_key = $template_key;
        return $this;
    }

    public function getTemplateKey(): ?string
    {
        return $this->template_key;
    }

    public function setTemplateVersion(?int $template_version): self
    {
        $this->template_version = $template_version;
        return $this;
    }

    public function getTemplateVersion(): ?int
    {
        return $this->template_version;
    }

    public function setRenderChecksumHtml(?string $render_checksum_html): self
    {
        $this->render_checksum_html = $render_checksum_html;
        return $this;
    }

    public function getRenderChecksumHtml(): ?string
    {
        return $this->render_checksum_html;
    }

    public function setRenderChecksumText(?string $render_checksum_text): self
    {
        $this->render_checksum_text = $render_checksum_text;
        return $this;
    }

    public function getRenderChecksumText(): ?string
    {
        return $this->render_checksum_text;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;
        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function prePersist(): self
    {
        if (!$this->created_at instanceof \DateTimeInterface) {
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
                case $prfx . 'job_id':
                    $this->setJobId($v);
                    break;
                case $prfx . 'contact_id':
                    $this->setContactId($v);
                    break;
                case $prfx . 'subject':
                    $this->setSubject($v);
                    break;
                case $prfx . 'from_email':
                    $this->setFromEmail($v);
                    break;
                case $prfx . 'reply_to':
                    $this->setReplyTo($v);
                    break;
                case $prfx . 'to_email':
                    $this->setToEmail($v);
                    break;
                case $prfx . 'body_html':
                    $this->setBodyHtml($v);
                    break;
                case $prfx . 'body_plain':
                    $this->setBodyPlain($v);
                    break;
                case $prfx . 'headers':
                    $this->setHeaders($v);
                    break;
                case $prfx . 'sender_message_id':
                    $this->setSenderMessageId($v);
                    break;
                case $prfx . 'kind':
                    $this->setKind(EmailMessageKind::tryFromString($v) ?? EmailMessageKind::TRANSACTIONAL);
                    break;
                case $prfx . 'send_status':
                    $this->setSendStatus(EmailMessageSendStatus::tryFromString($v) ?? EmailMessageSendStatus::QUEUED);
                    break;
                case $prfx . 'sent_at':
                    $this->setSentAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'failed_at':
                    $this->setFailedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'fail_reason':
                    $this->setFailReason($v);
                    break;
                case $prfx . 'template_key':
                    $this->setTemplateKey($v);
                    break;
                case $prfx . 'template_version':
                    $this->setTemplateVersion($v);
                    break;
                case $prfx . 'render_checksum_html':
                    $this->setRenderChecksumHtml($v);
                    break;
                case $prfx . 'render_checksum_text':
                    $this->setRenderChecksumText($v);
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v));
                    break;
            }
        }
        return $this;
    }

}

