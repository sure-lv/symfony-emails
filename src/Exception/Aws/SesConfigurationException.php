<?php

namespace SureLv\Emails\Exception\Aws;

/**
 * Thrown for configuration issues
 * Examples: domain not verified, configuration set doesn't exist
 * These require manual intervention and should not be retried
 */
class SesConfigurationException extends SesException
{
}