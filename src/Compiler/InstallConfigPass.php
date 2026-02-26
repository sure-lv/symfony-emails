<?php
namespace SureLv\Emails\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InstallConfigPass implements CompilerPassInterface
{
    
    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        
        // Get config directory
        $configDir = $this->getConfigDir($projectDir);
        if (!$configDir) {
            throw new \Exception('Could not find Symfony config directory.');
        }

        // Create packages directory if it doesn't exist
        $this->addConfigFile($configDir);

        // Add routes to routes.yaml if not already present
        $this->addRoutes($configDir);
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */

    
    private function addConfigFile(string $configDir): void
    {
        $files = [
            $configDir . '/packages/sure_lv_emails.yaml' => $this->getPackageConfig(),
        ];
        foreach ($files as $path => $content) {
            if (file_exists($path)) {
                continue;
            }
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, $content);
        }
    }

    private function addRoutes(string $configDir): void
    {
        $routesFile = $configDir . '/routes.yaml';
        if (!file_exists($routesFile)) {
            throw new \Exception('Could not find routes.yaml file.');
        }
        $currentRoutes = file_get_contents($routesFile);
        if (strpos($currentRoutes, 'sure_lv_emails:') !== false) {
            return;
        }
        $routesContent = $this->getRoutesConfig();
        file_put_contents($routesFile, $routesContent . "\n\n" . $currentRoutes);
    }

    private function getConfigDir(string $projectDir): ?string
    {
        if (is_dir($projectDir . '/config/packages')) {
            return $projectDir . '/config';
        }
        return null;
    }

    private function getPackageConfig(): string
    {
        return <<<YAML
sure_lv_emails:
    from_email: ''
    from_email_formated: ''
    secret: '%env(APP_SECRET)%'
    table_prefix: 'emails_'
    logger: null
    message_on_list_member_status_change: ''
    recipes:
        transactional: []
        list: []
YAML;
    }

    private function getRoutesConfig(): string
    {
        return <<<YAML
sure_lv_emails:
    resource: '@SureLvEmailsBundle/config/routes.yaml'
    prefix: /emails/
YAML;
    }
}