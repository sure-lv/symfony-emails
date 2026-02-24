<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Entity\Tracking;
use SureLv\Emails\Enum\EmailTrackingType;
use SureLv\Emails\Model\TrackingModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailTrackingService
{

    private string $secret;

    private ?TrackingModel $tracking_model = null;

    /**
     * @var array<string>
     */
    private array $excluded_urls = [];

    /**
     * Constructor
     * 
     * @param RegistryService $registryService
     * @param UrlGeneratorInterface $router
     */
    public function __construct(RegistryService $registryService, private UrlGeneratorInterface $router, private ModelService $modelService)
    {
        $this->secret = sprintf('tracking_%s', $registryService->getSecret());

        // Set router context
        $routerContext = $this->router->getContext();
        $routerContext->setHost($registryService->getUrlDomain());
        $routerContext->setScheme($registryService->getUrlScheme());

        // Add tracking urls to excluded urls
        $emailTrackClickUrl = preg_replace('/0\/HASH\/?$/', '', $this->router->generate('sure_lv_emails_track_click', ['id' => 0, 'hash' => 'HASH'], UrlGeneratorInterface::ABSOLUTE_URL));
        if ($emailTrackClickUrl) {
            $this->excluded_urls[] = $emailTrackClickUrl;
        }
        $emailTrackOpenUrl = preg_replace('/0\/HASH\/?$/', '', $this->router->generate('sure_lv_emails_track_open', ['id' => 0, 'hash' => 'HASH'], UrlGeneratorInterface::ABSOLUTE_URL));
        if ($emailTrackOpenUrl) {
            $this->excluded_urls[] = $emailTrackOpenUrl;
        }
        
        // Add unsubscribe urls to excluded urls
        $unsubscribeUrl = $this->router->generate('sure_lv_emails_unsubscribe', ['memberId' => 0, 'messageId' => 0, 'payload' => 'PAYLOAD', 'signature' => 'SIGNATURE'], UrlGeneratorInterface::ABSOLUTE_URL);
        $unsubscribeUrl = preg_replace('/0\/0\/PAYLOAD\/SIGNATURE\/?$/', '', $unsubscribeUrl);
        if ($unsubscribeUrl) {
            $this->excluded_urls[] = $unsubscribeUrl;
        }
    }

    /**
     * Add tracking to html
     * 
     * @param string $html
     * @param int $messageId
     * @return string
     */
    public function addTracking(string $html, int $messageId): string
    {
        // Add click tracking to all links
        $html = $this->addClickTracking($html, $messageId);
        
        // Add open tracking pixel
        $html = $this->addOpenTracking($html, $messageId);
        
        return $html;
    }

    /**
     * Get payload token
     * 
     * @param Tracking $tracking
     * @return string
     * @throws \Exception if failed to encode params to JSON
     */
    public function getPayloadTokenFromTracking(Tracking $tracking): string
    {
        $signatureParams = [
            'i' => $tracking->getId(),
        ];
        $json = json_encode($signatureParams);
        if (!$json) {
            throw new \RuntimeException('Failed to encode params to JSON');
        }
        
        // base64url, no padding
        $payload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = substr(hash_hmac('sha256', $payload, $this->secret . '_' . $tracking->getHash()), 0, 32);
        
        // Use ~ as separator instead of dot
        return $payload . '~' . $tracking->getHash() . '~' . $signature;
    }

    /**
     * Get params from payload token
     * 
     * @param string $token
     * @param EmailTrackingType $type
     * @param string|null &$error
     * @return Tracking|null
     */
    public function getTrackingFromPayloadToken(string $token, EmailTrackingType $type, ?string &$error = null): ?Tracking
    {
        // Detect format by separator
        if (str_contains($token, '~')) {
            return $this->decodeNewToken($token, $type, $error);
        }
        
        return $this->decodeLegacyToken($token, $type, $error);
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Add click tracking to html
     * 
     * @param string $html
     * @param int $messageId
     * @return string
     */
    private function addClickTracking(string $html, int $messageId): string
    {
        // Pattern to match href attributes
        // This handles various cases including URLs with &, quotes, etc.
        $pattern = '/(<a[^>]+href=)(["\'])([^"\']+)\2/i';

        $trackingModel = $this->getTrackingModel();
        $excludedUrls = $this->excluded_urls;
        
        $html = preg_replace_callback($pattern, function($matches) use ($messageId, $excludedUrls, $trackingModel) {
            $fullTag = $matches[1];
            $quote = $matches[2];
            $originalUrl = $matches[3];

            // Skip if it's an excluded url
            foreach ($excludedUrls as $excludedUrl) {
                if (strpos($originalUrl, $excludedUrl) !== false) {
                    return $matches[0];
                }
            }
            
            // Skip if it's an anchor link or mailto link
            if (strpos($originalUrl, '#') === 0 ||
                strpos($originalUrl, 'mailto:') === 0) {
                return $matches[0];
            }

            $tracking = new Tracking(EmailTrackingType::CLICK, $messageId, ['url' => $originalUrl]);
            $res = $trackingModel->add($tracking);
            if (!$res) {
                return $matches[0];
            }
            
            // Generate tracking URL
            $trackingUrl = $this->generateTrackingUrl($tracking);
            
            return $fullTag . $quote . $trackingUrl . $quote;
        }, $html);

        if (!$html) {
            return '';
        }
        
        return $html;
    }

    /**
     * Add open tracking to html
     * 
     * @param string $html
     * @param int $messageId
     * @return string
     */
    private function addOpenTracking(string $html, int $messageId): string
    {
        $tracking = new Tracking(EmailTrackingType::OPEN, $messageId);

        $res = $this->getTrackingModel()->add($tracking);
        if (!$res) {
            return $html;
        }
        
        // Generate tracking url
        $trackingPixelUrl = $this->generateTrackingUrl($tracking);
        
        // Create tracking pixel
        $trackingPixel = sprintf(
            '<img src="%s" alt="" width="1" height="1" style="display:block;width:1px;height:1px;border:0;" />',
            $trackingPixelUrl
        );
        
        // Try to insert before closing body tag
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $trackingPixel . '</body>', $html);
        } else {
            // If no body tag, append at the end
            $html .= $trackingPixel;
        }
        
        return $html;
    }

    /**
     * Decode new token
     * 
     * @param string $token
     * @param EmailTrackingType $type
     * @param string|null &$error
     * @return Tracking|null
     */
    private function decodeNewToken(string $token, EmailTrackingType $type, ?string &$error = null): ?Tracking
    {
        $parts = explode('~', $token);
        if (count($parts) !== 3) {
            $error = 'Not enough parts in token';
            return null;
        }

        [$payloadEncoded, $hash, $signature] = $parts;

        $calculatedSignature = substr(hash_hmac('sha256', $payloadEncoded, $this->secret . '_' . $hash), 0, 32);
        if (!hash_equals($calculatedSignature, $signature)) {
            $error = 'Invalid signature';
            return null;
        }

        // Restore base64url → standard base64
        $base64 = strtr($payloadEncoded, '-_', '+/');
        $padded = str_pad($base64, strlen($base64) + (4 - strlen($base64) % 4) % 4, '=');
        $payload = base64_decode($padded, strict: true);
        
        if ($payload === false) {
            $error = 'Invalid payload encoding';
            return null;
        }

        return $this->buildTrackingFromPayload($payload, $hash, $type, $error);
    }

    /**
     * Decode legacy token
     * 
     * @param string $token
     * @param EmailTrackingType $type
     * @param string|null &$error
     * @return Tracking|null
     */
    private function decodeLegacyToken(string $token, EmailTrackingType $type, ?string &$error = null): ?Tracking
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            $error = 'Not enough parts in token';
            return null;
        }

        [$payloadEncoded, $hash, $signature] = $parts;

        $calculatedSignature = hash_hmac('sha256', $payloadEncoded, $this->secret . '_' . $hash);
        if (!hash_equals($calculatedSignature, $signature)) {
            $error = 'Invalid signature';
            return null;
        }

        $payload = base64_decode($payloadEncoded);
        if ($payload === false) {
            $error = 'Invalid payload encoding';
            return null;
        }

        return $this->buildTrackingFromPayload($payload, $hash, $type, $error);
    }

    /**
     * Build tracking from payload
     * 
     * @param string $payload
     * @param string $hash
     * @param string|null &$error
     * @return Tracking|null
     */
    private function buildTrackingFromPayload(string $payload, string $hash, EmailTrackingType $type, ?string &$error = null): ?Tracking
    {
        $res = json_decode($payload, true);
        if (!is_array($res)) {
            $error = 'Invalid payload json';
            return null;
        }

        // $type = EmailTrackingType::tryFromString($res['t'] ?? '');
        // if (!$type) {
        //     $error = 'Invalid tracking type';
        //     return null;
        // }

        $id = intval($res['i'] ?? 0);
        $messageId = intval($res['m'] ?? 0);

        if ($id <= 0) {
            $error = 'Invalid tracking id';
            return null;
        }

        $tracking = new Tracking($type, $messageId);
        $tracking
            ->setId($id)
            ->setHash($hash)
            ;

        return $tracking;
    }

    /**
     * Generate tracking url
     * 
     * @param Tracking $tracking
     * @return string
     */
    private function generateTrackingUrl(Tracking $tracking): string
    {
        // $token = $this->getPayloadTokenFromTracking($tracking);
        $route = $tracking->getType() === EmailTrackingType::CLICK ? 'sure_lv_emails_track_click' : 'sure_lv_emails_track_open';
        
        return $this->router->generate($route, ['id' => $tracking->getId(), 'hash' => $tracking->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Get tracking model
     * 
     * @return TrackingModel
     */
    private function getTrackingModel(): TrackingModel
    {
        if (!$this->tracking_model) {
            $model = $this->modelService->getModel(TrackingModel::class); /** @var TrackingModel $model */
            $this->tracking_model = $model;
        }
        return $this->tracking_model;
    }

}