<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\ContactSuppressionReason;
use SureLv\Emails\Util\DateTimeUtils;
use SureLv\Emails\Util\EmailUtils;

final class Contact
{

    private int $id = 0;

    private string $email_norm = '';

    private string $email = '';

    private string $first_name = '';

    private string $last_name = '';

    private bool $is_verified = false;

    private ?\DateTime $suppressed_until = null;

    private ?ContactSuppressionReason $suppression_reason = null;

    private ?\DateTime $last_email_at = null;

    private ?\DateTime $created_at = null;

    private ?\DateTime $updated_at = null;

    private ?string $bounce_type = null;

    private ?string $bounce_subtype = null;

    private ?string $bounce_diagnostic_code = null;

    private ?string $complaint_type = null;

    private ?string $complaint_subtype = null;

    private ?\DateTime $last_bounce_at = null;

    private ?\DateTime $last_complaint_at = null;

    private ?string $aws_feedback_id = null;

    private int $bounce_count = 0;

    private int $complaint_count = 0;

    public static function createFromEmail(string $email, string $firstName = '', string $lastName = '', bool $isVerified = true): self
    {
        $email = trim($email);
        $contact = new self();
        $contact
            ->setEmailNorm(EmailUtils::normalizeEmail($email))
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setIsVerified($isVerified)
            ;
        return $contact;
    }

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $contact = new self();
        $contact->fromArray($data);
        return $contact;
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

    public function setEmailNorm(string $email_norm): self
    {
        $this->email_norm = $email_norm;
        return $this;
    }

    public function getEmailNorm(): string
    {
        return $this->email_norm;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * Set is verified
     * 
     * @param bool|int<0, 1> $is_verified
     * @return self
     */
    public function setIsVerified($is_verified): self
    {
        $this->is_verified = (bool)$is_verified;
        return $this;
    }

    public function getIsVerified(): bool
    {
        return $this->is_verified;
    }

    public function setSuppressedUntil(?\DateTime $suppressed_until): self
    {
        $this->suppressed_until = $suppressed_until;
        return $this;
    }

    public function getSuppressedUntil(): ?\DateTime
    {
        return $this->suppressed_until;
    }

    public function setSuppressionReason(?ContactSuppressionReason $suppression_reason): self
    {
        $this->suppression_reason = $suppression_reason;
        return $this;
    }

    public function getSuppressionReason(): ?ContactSuppressionReason
    {
        return $this->suppression_reason;
    }

    public function setLastEmailAt(?\DateTime $last_email_at): self
    {
        $this->last_email_at = $last_email_at;
        return $this;
    }

    public function getLastEmailAt(): ?\DateTime
    {
        return $this->last_email_at;
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

    public function setUpdatedAt(?\DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setBounceType(?string $bounce_type): self
    {
        $this->bounce_type = $bounce_type;
        return $this;
    }

    public function getBounceType(): ?string
    {
        return $this->bounce_type;
    }

    public function setBounceSubtype(?string $bounce_subtype): self
    {
        $this->bounce_subtype = $bounce_subtype;
        return $this;
    }

    public function getBounceSubtype(): ?string
    {
        return $this->bounce_subtype;
    }

    public function setBounceDiagnosticCode(?string $bounce_diagnostic_code): self
    {
        $this->bounce_diagnostic_code = $bounce_diagnostic_code;
        return $this;
    }

    public function getBounceDiagnosticCode(): ?string
    {
        return $this->bounce_diagnostic_code;
    }

    public function setComplaintType(?string $complaint_type): self
    {
        $this->complaint_type = $complaint_type;
        return $this;
    }

    public function getComplaintType(): ?string
    {
        return $this->complaint_type;
    }

    public function setComplaintSubtype(?string $complaint_subtype): self
    {
        $this->complaint_subtype = $complaint_subtype;
        return $this;
    }

    public function getComplaintSubtype(): ?string
    {
        return $this->complaint_subtype;
    }

    public function setLastBounceAt(?\DateTime $last_bounce_at): self
    {
        $this->last_bounce_at = $last_bounce_at;
        return $this;
    }

    public function getLastBounceAt(): ?\DateTime
    {
        return $this->last_bounce_at;
    }

    public function setLastComplaintAt(?\DateTime $last_complaint_at): self
    {
        $this->last_complaint_at = $last_complaint_at;
        return $this;
    }

    public function getLastComplaintAt(): ?\DateTime
    {
        return $this->last_complaint_at;
    }

    public function setAwsFeedbackId(?string $aws_feedback_id): self
    {
        $this->aws_feedback_id = $aws_feedback_id;
        return $this;
    }

    public function getAwsFeedbackId(): ?string
    {
        return $this->aws_feedback_id;
    }

    public function setBounceCount(int $bounce_count): self
    {
        $this->bounce_count = $bounce_count;
        return $this;
    }

    public function getBounceCount(): int
    {
        return $this->bounce_count;
    }

    public function setComplaintCount(int $complaint_count): self
    {
        $this->complaint_count = $complaint_count;
        return $this;
    }

    public function getComplaintCount(): int
    {
        return $this->complaint_count;
    }

    public function prePersist(): self
    {
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
                case $prfx . 'email_norm':
                    $this->setEmailNorm($v);
                    break;
                case $prfx . 'email':
                    $this->setEmail($v);
                    break;
                case $prfx . 'first_name':
                    $this->setFirstName($v);
                    break;
                case $prfx . 'last_name':
                    $this->setLastName($v);
                    break;
                case $prfx . 'is_verified':
                    $this->setIsVerified($v);
                    break;
                case $prfx . 'suppressed_until':
                    $this->setSuppressedUntil(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'suppression_reason':
                    $this->setSuppressionReason(ContactSuppressionReason::tryFromString($v));
                    break;
                case $prfx . 'last_email_at':
                    $this->setLastEmailAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'updated_at':
                    $this->setUpdatedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'bounce_type':
                    $this->setBounceType($v);
                    break;
                case $prfx . 'bounce_subtype':
                    $this->setBounceSubtype($v);
                    break;
                case $prfx . 'bounce_diagnostic_code':
                    $this->setBounceDiagnosticCode($v);
                    break;
                case $prfx . 'complaint_type':
                    $this->setComplaintType($v);
                    break;
                case $prfx . 'complaint_subtype':
                    $this->setComplaintSubtype($v);
                    break;
                case $prfx . 'last_bounce_at':
                    $this->setLastBounceAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'last_complaint_at':
                    $this->setLastComplaintAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'aws_feedback_id':
                    $this->setAwsFeedbackId($v);
                    break;
                case $prfx . 'bounce_count':
                    $this->setBounceCount(intval($v));
                    break;
                case $prfx . 'complaint_count':
                    $this->setComplaintCount(intval($v));
                    break;
            }
        }
        return $this;
    }

    public function isSuppressed(): bool
    {
        return $this->suppressed_until !== null && $this->suppressed_until > new \DateTime();
    }

}