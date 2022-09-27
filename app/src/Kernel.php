<?php

namespace Instacar\ExtraFiltersBundle\App;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Instacar\ExtraFiltersBundle\App\Entity\User;
use Instacar\ExtraFiltersBundle\InstacarExtraFiltersBundle;
use Instacar\ExtraFiltersBundle\Test\Util\PackageVersion;
use Liip\TestFixturesBundle\LiipTestFixturesBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new DoctrineFixturesBundle(),
            new LiipTestFixturesBundle(),
            new TwigBundle(),
            new ApiPlatformBundle(),
            new InstacarExtraFiltersBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $isLegacyApiPlatform = PackageVersion::isLegacyApiPlatform();
        $apiPlatformConfig = [
            'mapping' => [
                'paths' => ['%kernel.project_dir%/src/Entity'],
            ],
            'formats' => [
                'json' => ['application/json'],
            ],
        ];

        if ($isLegacyApiPlatform) {
            $apiPlatformConfig['metadata_backward_compatibility_layer'] = false;
        }

        $loader->load(dirname(__DIR__) . '/config/services.xml');
        $loader->load(function (ContainerBuilder $container) use ($apiPlatformConfig) {
            $container->loadFromExtension('doctrine', [
                'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
                'orm' => [
                    'mappings' => [
                        'Test' => [
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/src/Entity',
                            'prefix' => 'Instacar\ExtraFiltersBundle\App\Entity',
                        ],
                    ],
                ],
            ]);
            $container->loadFromExtension('framework', [
                'router' => ['resource' => '%kernel.project_dir%/config/routes.xml'],
                'trusted_hosts' => '%env(TRUSTED_HOSTS)%',
                'secret' => '%env(APP_SECRET)%',
                'http_method_override' => false,
                'test' => true,
            ]);
            $container->loadFromExtension('security', [
                'providers' => [
                    'app_user_provider' => [
                        'entity' => [
                            'class' => User::class,
                            'property' => 'username',
                        ],
                    ],
                ],
                'firewalls' => [
                    'main' => [
                        'stateless' => true,
                        'provider' => 'app_user_provider',
                    ],
                ],
            ]);
            $container->loadFromExtension('api_platform', $apiPlatformConfig);
        });
    }
}
