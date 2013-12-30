<?php

namespace ThinFrame\Doctrine;

use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ThinFrame\Applications\AbstractApplication;
use ThinFrame\Applications\DependencyInjection\ApplicationAwareInterface;
use ThinFrame\Applications\DependencyInjection\ApplicationContainerBuilder;
use ThinFrame\Applications\DependencyInjection\Extensions\ConfigurationAwareInterface;
use ThinFrame\Foundation\Exceptions\InvalidArgumentException;
use ThinFrame\Foundation\Exceptions\LogicException;
use ThinFrame\Foundation\Exceptions\RuntimeException;

/**
 * Class EntityManagerFactory
 *
 * @package ThinFrame\Doctrine
 * @since   0.2
 */
class EntityManagerFactory implements ContainerAwareInterface, ConfigurationAwareInterface, ApplicationAwareInterface
{
    /**
     * @var AbstractApplication
     */
    private $application;
    /**
     * @var ApplicationContainerBuilder
     */
    private $container;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * Attach application to current instance
     *
     * @param AbstractApplication $application
     *
     * @return mixed
     */
    public function setApplication(AbstractApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param array $configuration
     *
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get project environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        try {
            return (string)$this->container->get('thinframe.karma.environment');
        } catch (\Exception $e) {
            return 'production';
        }
    }

    /**
     * Create a new entity manager
     */
    public function createEntityManager()
    {
        if (!isset($this->configuration['connections'][$this->getEnvironment()])) {
            throw new LogicException('Missing configuration for specified environment');
        }
        $databaseParams = $this->configuration['connections'][$this->getEnvironment()];
        $entitiesPaths  = [];
        $aliases        = [];
        foreach ($this->application->getMetadata() as $appName => $metadata) {
            /* @var $metadata \PhpCollection\Map */
            if ($metadata->containsKey('doctrine-entities')) {
                $entitiesPaths[] = realpath(
                    $metadata->get('application_path')->get() . DIRECTORY_SEPARATOR . $metadata->get(
                        'doctrine-entities'
                    )->get()
                );
                if ($metadata->containsKey('doctrine-alias-namespace')) {
                    $aliases[$appName] = $metadata->get('doctrine-alias-namespace')->get();
                }
            }
        }

        $doctrineConfig = Setup::createAnnotationMetadataConfiguration(
            $entitiesPaths,
            $this->getEnvironment() == 'development'
        );

        $this->setCacheDrivers($doctrineConfig);
        $this->setFilters($doctrineConfig);

        foreach ($aliases as $alias => $namespace) {
            $doctrineConfig->addEntityNamespace($alias, $namespace);
        }

        return EntityManager::create($databaseParams, $doctrineConfig);
    }

    /**
     * @param Configuration $config
     */
    private function setFilters(Configuration $config)
    {
        if (isset($this->configuration['filters']) && is_array($this->configuration['filters'])) {
            foreach ($this->configuration['filters'] as $name => $filter) {
                $config->addFilter($name, $filter);
            }
        }
    }

    /**
     * @param Configuration $config
     *
     * @throws \ThinFrame\Foundation\Exceptions\RuntimeException
     */
    private function setCacheDrivers(Configuration $config)
    {
        if (isset($this->configuration['caching']) && is_array($this->configuration['caching'])) {
            foreach ($this->configuration['caching'] as $mode => $driver) {
                if (trim($driver) == '') {
                    continue;
                }
                if (is_null($cacheDriver = $this->getCacheDriver($driver))) {
                    throw new RuntimeException('Bad configuration for cache driver: ' . $driver);
                }
                switch ($mode) {
                    case 'result':
                        $config->setResultCacheImpl($cacheDriver);
                        break;
                    case 'metadata':
                        $config->setMetadataCacheImpl($cacheDriver);
                        break;
                    case 'query':
                        $config->setQueryCacheImpl($cacheDriver);
                        break;
                    case 'hydration':
                        $config->setHydrationCacheImpl($cacheDriver);
                        break;
                    default:
                }
            }
        }
    }

    /**
     * @param $driver
     *
     * @return MemcacheCache|MemcachedCache|RedisCache|XcacheCache|null
     * @throws \ThinFrame\Foundation\Exceptions\InvalidArgumentException
     */
    private
    function getCacheDriver(
        $driver
    ) {
        switch ($driver) {
            case 'memcache':
                return $this->getMemcache();
            case 'memcached':
                return $this->getMemcached();
            case 'redis':
                return $this->getRedis();
            case 'xcache':
                return $this->getXcache();
            default:
                throw new InvalidArgumentException('Unknow cache driver requested: ' . $driver);
        }
    }

    /**
     * Get memcache driver
     *
     * @return MemcacheCache|null
     */
    private
    function getMemcache()
    {
        if (
            isset($this->configuration['memcache'])
            && isset($this->configuration['memcache']['host'])
            && isset($this->configuration['memcache']['port'])
        ) {
            $memcache = new \Memcache();
            $memcache->connect($this->configuration['memcache']['host'], $this->configuration['memcache']['port']);

            $cacheDriver = new MemcacheCache();
            $cacheDriver->setMemcache($memcache);
            return $cacheDriver;
        }
        return null;
    }

    /**
     * Get memcached driver
     *
     * @return MemcachedCache|null
     */
    private
    function getMemcached()
    {
        if (isset($this->configuration['memcached']) && is_array($this->configuration['memcached'])) {
            $memcached = new \Memcached();
            foreach ($this->configuration['memcached'] as $host => $port) {
                $memcached->addServer($host, $port);
            }
            $cacheDriver = new MemcachedCache();
            $cacheDriver->setMemcached($memcached);
            return $cacheDriver;
        }
        return null;
    }

    /**
     * Get xcache driver
     *
     * @return XcacheCache
     */
    private
    function getXcache()
    {
        return new XcacheCache();
    }

    /**
     * Get redis cache driver
     *
     * @return RedisCache|null
     */
    private
    function getRedis()
    {
        if (
            isset($this->configuration['redis'])
            && isset($this->configuration['redis']['host'])
            && isset($this->configuration['redis']['port'])
        ) {
            $redis = new \Redis();
            $redis->connect($this->configuration['redis']['host'], $this->configuration['redis']['port']);

            $cacheDriver = new RedisCache();
            $cacheDriver->setRedis($redis);
            return $cacheDriver;
        }
        return null;
    }
}
