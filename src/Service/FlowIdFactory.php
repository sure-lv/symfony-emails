<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use SureLv\Emails\Entity\Recipe;
use Ramsey\Uuid\Uuid;

final class FlowIdFactory
{
    private const NS = '6ba7b811-9dad-11d1-80b4-00c04fd430c8'; // DNS namespace

    public function __construct(private DedupeKeyFactory $dedupe) {}

    /**
     * For recipe
     * 
     * @param Recipe $recipe
     * @param array<string, mixed> $params
     * @return string
     */
    public function forRecipe(Recipe $recipe, array $params): string
    {
        $flowKey  = $recipe->getFlowKey();
        $stableId = $this->dedupe->stableBusinessIdFrom($recipe, $params);
        return Uuid::uuid5(self::NS, $flowKey . ':' . $stableId)->toString();
    }
}
