<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use SureLv\Emails\Entity\Recipe;

final class DedupeKeyFactory
{

    /**
     * Stable business ID from recipe and params
     * 
     * @param Recipe $recipe
     * @param array<string, mixed> $params
     * @return string
     */
    public function stableBusinessIdFrom(Recipe $recipe, array $params): string
    {
        $preferred = $recipe->getStableKeys();
        $vals = [];
        foreach ($preferred as $k) {
            if (array_key_exists($k, $params) && $params[$k] !== null && $params[$k] !== '') {
                $vals[] = (string)$params[$k];
            }
        }
        if ($vals) {
            return implode(':', $vals);
        }
        $clean = $this->stripVolatile($params);
        ksort($clean);
        $json = json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash('sha256', (string)$json);
    }

    /**
     * Build dedupe key
     * 
     * @param Recipe $recipe
     * @param array<string, mixed> $params
     * @param int|null $contactId
     * @param int|null $stepOrder
     * @param \DateTimeInterface|null $date
     * @return string
     */
    public function build(
        Recipe $recipe,
        array $params,
        ?int $contactId = null,
        ?int $stepOrder = null,
        ?\DateTimeInterface $date = null
    ): string {
        $stableId = $this->stableBusinessIdFrom($recipe, $params);
        $dateYmd = ($date ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Ymd');
        $dateYmdHm = ($date ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('YmdHi');
        $timestamp = ($date ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp();
        $tpl = $recipe->getDedupeTemplate();
        $dedupeParams = $recipe->getDedupeParams();

        // If template and params are configured, use config-driven formatting
        if ($tpl !== null && !empty($dedupeParams)) {
            $paramValues = [];
            foreach ($dedupeParams as $paramName) {
                $paramValues[] = match ($paramName) {
                    'stableId' => $stableId,
                    'contactId' => $contactId ?? 0,
                    'stepOrder' => $stepOrder ?? 0,
                    'dateYmd' => $dateYmd,
                    'dateYmdHm' => $dateYmdHm,
                    'timestamp' => $timestamp,
                    default => null,
                };
            }
            return sprintf($tpl, ...$paramValues);
        }

        // Fall back to default implode behavior if no template/params configured
        return implode(':', array_values(array_filter([
            $recipe->getName(), $stableId,
            $contactId !== null ? (string)$contactId : null,
            $dateYmd,
            $stepOrder !== null ? 'step'.$stepOrder : null,
        ])));
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Strip volatile params
     * 
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function stripVolatile(array $params): array
    {
        foreach (['now','timestamp','ts','request_id','trace_id','nonce'] as $k) {
            unset($params[$k]);
        }
        return $params;
    }
}
