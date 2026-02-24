<?php

namespace SureLv\Emails\Service;

class EmailsHelperService
{

    public function __construct(private RegistryService $registryService) {}

    /**
     * Get payload token
     * 
     * @param array<string, mixed> $params
     * @return string
     * @throws \RuntimeException if failed to encode params to JSON
     */
    public function getPayloadToken(array $params): string
    {
        $tokenParts = $this->getPayloadTokenParts($params, 32);
        if (!$tokenParts || count($tokenParts) !== 2) {
            throw new \RuntimeException('Failed to get payload token parts');
        }
        
        return $tokenParts[0] . '~' . $tokenParts[1];
    }

    /**
     * Get signature and payload parts
     * 
     * @param array<string, mixed> $params
     * @return array<string>
     * @throws \RuntimeException if failed to encode params to JSON
     */
    public function getPayloadTokenParts(array $params, ?int $length = null): array
    {
        $json = json_encode($params);
        if (!$json) {
            throw new \RuntimeException('Failed to encode params to JSON');
        }

        // base64url, no padding
        $payload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, $this->registryService->getSecret());
        if ($length) {
            $signature = substr($signature, 0, $length);
        }

        return [$payload, $signature];
    }

    /**
     * Get params from payload token
     * 
     * @param string $token
     * @param int|null $length
     * @param string|null &$error
     * @return array<string, mixed>|null
     */
    public function getParamsFromPayloadToken(string $token, ?int $length = null, ?string &$error = null): ?array
    {
        // Detect format by separator
        if (str_contains($token, '~')) {
            return $this->getParamsFromNewPayloadToken($token, $length, $error);
        }
        
        return $this->getParamsFromLegacyPayloadToken($token, $error);
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Get params from legacy payload token
     * 
     * @param string $token
     * @param string|null &$error
     * @return array<string, mixed>|null
     */
    private function getParamsFromLegacyPayloadToken(string $token, ?string &$error = null): ?array
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 2) {
            $error = 'Not enough parts in token';
            return null;
        }
        $signature = $tokenParts[1];
        $calculatedSignature = hash_hmac('sha256', $tokenParts[0], $this->registryService->getSecret());
        if (!hash_equals($calculatedSignature, $signature)) {
            $error = 'Invalid signature';
            return null;
        }
        try {
            $payload = base64_decode($tokenParts[0]);
            $res = json_decode($payload, true);
            if (!is_array($res)) {
                $error = 'Invalid payload json';
                return null;
            }
        } catch (\Exception $e) {
            $error = 'Invalid payload encoding';
            return null;
        }
        return $res;
    }

    /**
     * Get params from new payload token
     * 
     * @param string $token
     * @param int|null $length
     * @param string|null &$error
     * @return array<string, mixed>|null
     */
    private function getParamsFromNewPayloadToken(string $token, ?int $length = null, ?string &$error = null): ?array
    {
        $tokenParts = explode('~', $token);
        if (count($tokenParts) !== 2) {
            $error = 'Not enough parts in token';
            return null;
        }
        
        $signature = $tokenParts[1];
        $calculatedSignature = hash_hmac('sha256', $tokenParts[0], $this->registryService->getSecret());
        if ($length) {
            $calculatedSignature = substr($calculatedSignature, 0, $length);
        }
        if (!hash_equals($calculatedSignature, $signature)) {
            $error = 'Invalid signature';
            return null;
        }

        // Restore base64url → standard base64
        $base64 = strtr($tokenParts[0], '-_', '+/');
        $padded = str_pad($base64, strlen($base64) + (4 - strlen($base64) % 4) % 4, '=');
        $payload = base64_decode($padded, strict: true);

        if ($payload === false) {
            $error = 'Invalid payload encoding';
            return null;
        }

        $res = json_decode($payload, true);
        if (!is_array($res)) {
            $error = 'Invalid payload json';
            return null;
        }
        return $res;
    }

}