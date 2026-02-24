<?php

namespace SureLv\Emails\Config;

use SureLv\Emails\Enum\EmailMessageKind;

class EmailsConfig
{
    public function __construct(
        public readonly string $transport,
        public readonly array $transportConfig,
        public readonly string $urlDomain,
        public readonly string $urlScheme,
        public readonly string $secret,
        public readonly string $tablePrefix,
        public readonly array  $recipes,
        public readonly ?string $messageOnListMemberStatusChange,
    ) {}

    public function getRecipe(EmailMessageKind $type, string $name): ?array
    {
        return $this->recipes[$type->value][$name] ?? null;
    }

    public function getTransactionalRecipe(string $name): ?array
    {
        return $this->getRecipe(EmailMessageKind::TRANSACTIONAL, $name);
    }

    public function getListRecipe(string $name): ?array
    {
        return $this->getRecipe(EmailMessageKind::LIST, $name);
    }

}