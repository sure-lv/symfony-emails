<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Exception\RateLimitExceededException;
use SureLv\Emails\Provider\RateLimiter\RateLimiterProviderInterface;

class RateLimiterService
{

    public function __construct(private ?RateLimiterProviderInterface $rateLimiterProvider = null) {}

    /**
     * Check email rate limit
     * 
     * @param string $email
     * @return void
     * @throws RateLimitExceededException if rate limit is exceeded
     */
    public function checkEmailLimit(string $email): void
    {
        if (!$this->rateLimiterProvider) {
            return;
        }
        
        try {
            $this->rateLimiterProvider->checkEmailLimit($email);
        } catch (\Exception $e) {
            throw new RateLimitExceededException('Email rate limit exceeded', 0, $e);
        }
    }

}