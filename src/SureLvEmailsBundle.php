<?php
// src/SureLvEmailsBundle.php
namespace SureLv\Emails;

use SureLv\Emails\Config\EmailsConfig;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class SureLvEmailsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('transport')->defaultValue('smtp')
                    ->validate()
                        ->ifNotInArray(['ses', 'smtp'])
                        ->thenInvalid('Invalid transport. Possible values: "ses", "smtp"')
                    ->end()
                ->end()
                ->arrayNode('transport_config')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()->end()
                ->end()
                ->scalarNode('url_domain')->defaultValue('')->end()
                ->scalarNode('url_scheme')->defaultValue('https')->end()
                ->scalarNode('secret')->defaultValue('')->end()
                ->scalarNode('table_prefix')->defaultValue('emails_')->end()
                ->arrayNode('recipes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('transactional')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('flow_key')->end()
                                    ->arrayNode('stable_keys')->scalarPrototype()->end()->end()
                                    ->scalarNode('dedupe_template')->end()
                                    ->arrayNode('dedupe_params')->scalarPrototype()->end()->end()
                                    ->integerNode('default_delay_seconds')->defaultValue(0)->end()
                                    ->integerNode('default_priority')->defaultValue(10)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('list')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('flow_key')->end()
                                    ->arrayNode('stable_keys')->scalarPrototype()->end()->end()
                                    ->scalarNode('dedupe_template')->end()
                                    ->arrayNode('dedupe_params')->scalarPrototype()->end()->end()
                                    ->integerNode('default_delay_seconds')->defaultValue(0)->end()
                                    ->integerNode('default_priority')->defaultValue(0)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('message_on_list_member_status_change')->defaultValue('')->end()
                ->scalarNode('logger')->defaultValue(null)->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('emails.config', $config);

        $container->services()
            ->set(EmailsConfig::class)
            ->args([
                $config['transport'],
                $config['transport_config'],
                $config['url_domain'],
                $config['url_scheme'],
                $config['secret'],
                $config['table_prefix'],
                $config['recipes'],
                $config['message_on_list_member_status_change'],
            ])
            ;

        // Wire logger only if configured
        $loggerServiceId = $config['logger'] ?? null;

        $emailsLogger = $container->services()
            ->set(\SureLv\Emails\Service\EmailsLogger::class)
            ->lazy();

        if ($loggerServiceId) {
            // Strip leading @ if app passes '@monolog.logger.emails_transactional'
            $loggerServiceId = ltrim($loggerServiceId, '@');
            $emailsLogger->arg('$logger', service($loggerServiceId));
        }

        $container->import('../config/services.yaml');

        // Only load rate limiter services if the package is installed
        if (class_exists(\Symfony\Component\RateLimiter\RateLimiterFactory::class)) {
            $container->import('../config/services/rate_limiter.yaml');
        }
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__ . '/../config/routes.yaml');
    }
}