<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use SureLv\Emails\Config\EmailsConfig;
use SureLv\Emails\Entity\Recipe;
use SureLv\Emails\Enum\JobKind;
use SureLv\Emails\Mapper\EmailMessageMapperInterface;
use SureLv\Emails\Mapper\ListMapperInterface;

class RegistryService
{

    /**
     * @var array<string, Recipe>
     */
    private array $recipes = [];

    /**
     * @var array<string, Recipe>
     */
    private array $transactional_recipes = [];

    /**
     * @var array<string, Recipe>
     */
    private array $list_recipes = [];

    /**
     * Constructor
     * 
     * @param EmailsConfig $emailsConfig
     * @param EmailMessageMapperInterface $emailMessageMapper
     * @param ListMapperInterface $listMapper
     */
    public function __construct(private EmailsConfig $emailsConfig, private EmailMessageMapperInterface $emailMessageMapper, private ListMapperInterface $listMapper)
    {        
        // Recipes
        foreach ($emailsConfig->recipes as $recipeType => $typeRecipes) {
            $jobKind = JobKind::tryFromString($recipeType);
            if (!$jobKind) {
                throw new \InvalidArgumentException(sprintf('Invalid email type: %s', $recipeType));
            }

            foreach ($typeRecipes as $recipeName => $recipeConfig) {
                
                $recipe = new Recipe($recipeName, $jobKind);
                $recipe->fromArray($recipeConfig);

                $this->recipes[$recipe->getName()] = $recipe;
                if ($recipe->getType() == JobKind::TRANSACTIONAL) {
                    $this->transactional_recipes[$recipe->getName()] = $recipe;
                } else {
                    $this->list_recipes[$recipe->getName()] = $recipe;
                }
            }
        }
    }

    /**
     * Get URL domain
     * 
     * @return string
     */
    public function getUrlDomain(): string
    {
        return $this->emailsConfig->urlDomain;
    }

    /**
     * Get URL scheme
     * 
     * @return string
     */
    public function getUrlScheme(): string
    {
        return $this->emailsConfig->urlScheme;
    }
    
    /**
     * Get secret
     * 
     * @return string
     */
    public function getSecret(): string
    {
        return $this->emailsConfig->secret;
    }

    /**
     * Get table prefix
     * 
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->emailsConfig->tablePrefix;
    }

    /**
     * Get recipe
     * 
     * @param string $recipeName
     * @return Recipe
     * @throws \InvalidArgumentException if recipe is not found
     */
    public function getRecipe(string $recipeName): Recipe
    {
        $recipe = $this->recipes[$recipeName] ?? null;
        if (!$recipe) {
            throw new \InvalidArgumentException(sprintf('Recipe %s not found', $recipeName));
        }
        return $recipe;
    }

    /**
     * Has recipe
     * 
     * @param string $recipe
     * @return bool
     */
    public function hasRecipe(string $recipe): bool
    {
        return array_key_exists($recipe, $this->recipes);
    }

    /**
     * Get list of available recipe names
     * 
     * @return array<string>
     */
    public function getRecipeNames(): array
    {
        return array_keys($this->recipes);
    }

    /**
     * Get transactional recipes
     * 
     * @return array<string, Recipe>
     */
    public function getTransactionalRecipes(): array
    {
        return $this->transactional_recipes;
    }

    /**
     * Get list recipes
     * 
     * @return array<string, Recipe>
     */
    public function getListRecipes(): array
    {
        return $this->list_recipes;
    }

    /**
     * Get message on list member status change
     * 
     * @return ?string
     */
    public function getMessageOnListMemberStatusChange(): ?string
    {
        return $this->emailsConfig->messageOnListMemberStatusChange;
    }

    /**
     * Get email message mapper
     * 
     * @return EmailMessageMapperInterface
     */
    public function getEmailMessageMapper(): EmailMessageMapperInterface
    {
        return $this->emailMessageMapper;
    }

    /**
     * Get list mapper
     * 
     * @return ListMapperInterface
     */
    public function getListMapper(): ListMapperInterface
    {
        return $this->listMapper;
    }
    
}
