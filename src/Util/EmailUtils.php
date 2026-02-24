<?php

namespace SureLv\Emails\Util;

class EmailUtils
{

    /**
     * Normalize email
     * 
     * @param string $email
     * @return string
     * @throws \Exception
     */
    public static function normalizeEmail(string $email): string
    {
        $email = trim(strtolower($email)); // normalize case + trim spaces
        if ($email === '' || strpos($email, '@') === false) {
            throw new \Exception('Invalid email');
        }
    
        [$local, $domain] = explode('@', $email, 2);
    
        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            $local = preg_replace('/\+.*/', '', $local);  // strip +tag
            $local = str_replace('.', '', $local ?? '');        // remove dots
            $domain = 'gmail.com';                        // canonical domain
        }
    
        return $local . '@' . $domain;
    }

    /**
     * Extract email address
     * 
     * @param string $data
     * @return string
     */
    public static function extractEmailAddress(string $data): string
    {
        $pattern = '/[\w.-]+@[\w.-]+\.[A-Za-z]{2,}/';
        if (preg_match($pattern, $data, $matches)) {
            return $matches[0];
        }
        return '';
    }

}
