<?php

namespace SureLv\Emails\Provider\RateLimiter;

interface RateLimiterProviderInterface
{

    public function checkEmailLimit(string $email): void;

}