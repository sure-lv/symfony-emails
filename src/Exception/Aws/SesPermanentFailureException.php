<?php

namespace SureLv\Emails\Exception\Aws;

/**
 * Thrown for permanent failures that should not be retried
 * Examples: invalid email address, message rejected, malformed content
 */
class SesPermanentFailureException extends SesException
{
}