<?php

namespace SureLv\Emails\Exception\Aws;

/**
 * Thrown for temporary failures that should be retried
 * Examples: service unavailable, internal AWS failure
 */
class SesTemporaryFailureException extends SesException
{
}