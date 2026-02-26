<?php

namespace SureLv\Emails\Dto;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Entity\Job;

class EmailMessageDto
{
    
    private EmailMessageParamsDto $paramsDto;

    private ?Job $job = null;

    private ?EmailsListMember $member = null;

    private ?string $template_key = null;

    private ?int $template_version = null;

    private ?string $from_email = null;

    private string $subject = '';

    /**
     * @var array<string, mixed>
     */
    private array $headers = [];

    /**
     * @var array<string, mixed>
     */
    private array $global_context = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private ?array $html_context = null;

    private ?array $plain_context = null;

    private string $body_html_path = '';

    private string $body_plain_path = '';

    private string $body_html = '';

    private string $body_plain = '';

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    private bool $with_unsubscribe = false;

    /**
     * Constructor
     * 
     * @param Contact $contact
     * @param EmailMessage $emailMessage
     * @param ?EmailMessageParamsDto $paramsDto
     * @param array<string, mixed>|null $params
     * @param ?Job $job
     * @param ?EmailsListMember $member
     */
    public function __construct(private Contact $contact, private EmailMessage $emailMessage, ?EmailMessageParamsDto $paramsDto = null, ?array $params = null, ?Job $job = null, ?EmailsListMember $member = null)
    {
        if (is_null($paramsDto)) {
            $paramsDto = new EmailMessageParamsDto($params ?? []);
        }
        $paramsDto->setEmailMessageDto($this);
        $this->paramsDto = $paramsDto;
        $this->job = $job;
        $this->member = $member;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getEmailMessage(): EmailMessage
    {
        return $this->emailMessage;
    }

    public function getParamsDto(): EmailMessageParamsDto
    {
        return $this->paramsDto;
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

    public function setMember(?EmailsListMember $member): self
    {
        $this->member = $member;
        return $this;
    }

    public function getMember(): ?EmailsListMember
    {
        return $this->member;
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

    public function setFromEmail(?string $from_email): self
    {
        $this->from_email = $from_email;
        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->from_email;
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

    /**
     * Set headers
     * 
     * @param array<string, mixed> $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get headers
     * 
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function addHeader(string $key, mixed $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set global context
     * 
     * @param array<string, mixed> $global_context
     * @return self
     */
    public function setGlobalContext(array $global_context): self
    {
        $this->global_context = $global_context;
        return $this;
    }

    /**
     * Get global context
     * 
     * @return array<string, mixed>
     */
    public function getGlobalContext(): array
    {
        return $this->global_context;
    }

    public function addGlobalContextValue(string $key, mixed $value): self
    {
        $this->global_context[$key] = $value;
        return $this;
    }

    public function getGlobalContextValue(string $key, mixed $default = null): mixed
    {
        return $this->global_context[$key] ?? $default;
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

    /**
     * Set html context
     * 
     * @param array<string, mixed>|null $html_context
     * @return self
     */
    public function setHtmlContext(?array $html_context): self
    {
        $this->html_context = $html_context;
        return $this;
    }

    /**
     * Get html context
     * 
     * @param bool $for_render
     * @return array<string, mixed>
     */
    public function getHtmlContext(bool $for_render = false): array
    {
        if ($for_render) {
            return array_merge($this->global_context, $this->html_context ?? $this->context);
        }
        return $this->html_context ?? [];
    }

    /**
     * Set plain context
     * 
     * @param array<string, mixed>|null $plain_context
     * @return self
     */
    public function setPlainContext(?array $plain_context): self
    {
        $this->plain_context = $plain_context;
        return $this;
    }

    /**
     * Get plain context
     * 
     * @param bool $for_render
     * @return array<string, mixed>
     */
    public function getPlainContext(bool $for_render = false): array
    {
        if ($for_render) {
            return array_merge($this->global_context, $this->plain_context ?? $this->context);
        }
        return $this->plain_context ?? [];
    }

    public function setBodyHtmlPath(string $body_html_path): self
    {
        $this->body_html_path = $body_html_path;
        return $this;
    }

    public function getBodyHtmlPath(): string
    {
        return $this->body_html_path;
    }

    public function setBodyPlainPath(string $body_plain_path): self
    {
        $this->body_plain_path = $body_plain_path;
        return $this;
    }

    public function getBodyPlainPath(): string
    {
        return $this->body_plain_path;
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

    public function setBodyPlain(string $body_plain): self
    {
        $this->body_plain = $body_plain;
        return $this;
    }

    public function getBodyPlain(): string
    {
        return $this->body_plain;
    }

    /**
     * Set data
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     * 
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function addDataValue(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function setWithUnsubscribe(bool $with_unsubscribe): self
    {
        $this->with_unsubscribe = $with_unsubscribe;
        return $this;
    }

    public function getWithUnsubscribe(): bool
    {
        return $this->with_unsubscribe;
    }

    public function getSystemParams(): ?JobSystemParamsDto
    {
        return $this->job ? $this->job->getSystemParams() : null;
    }

}