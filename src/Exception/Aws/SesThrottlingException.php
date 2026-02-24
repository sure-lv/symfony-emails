<?php

namespace SureLv\Emails\Exception\Aws;

/**
 * Thrown when SES rate limits are exceeded
 * This is retryable - should use exponential backoff
 */
class SesThrottlingException extends SesException
{
}