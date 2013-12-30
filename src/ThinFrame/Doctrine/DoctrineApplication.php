<?php

namespace ThinFrame\Doctrine;

use ThinFrame\Applications\AbstractApplication;
use ThinFrame\Applications\DependencyInjection\ContainerConfigurator;
use ThinFrame\Applications\DependencyInjection\Extensions\ConfigurationManager;

/**
 * Class DoctrineApplication
 *
 * @package ThinFrame\Doctrine
 * @since   0.1
 */
class DoctrineApplication extends AbstractApplication
{
    /**
     * Get parent applications
     *
     * @return AbstractApplication[]
     */
    protected function getParentApplications()
    {
        return [];
    }

    /**
     * initialize configurator
     *
     * @param ContainerConfigurator $configurator
     *
     * @return mixed
     */
    public function initializeConfigurator(ContainerConfigurator $configurator)
    {
        $configurator->addConfigurationManager(
            new ConfigurationManager('doctrine', 'thinframe.doctrine.entity_manager_factory')
        );
    }

    /**
     * Get configuration files
     *
     * @return mixed
     */
    public function getConfigurationFiles()
    {
        return [
            'resources/config.yml',
            'resources/services.yml'
        ];
    }

    /**
     * Get application name
     *
     * @return string
     */
    public function getApplicationName()
    {
        return 'DoctrineApplication';
    }

    /**
     * @return array
     */
    protected function metaData()
    {
        return [
            'doctrine-entities'        => 'Entities',
            'doctrine-alias-namespace' => 'ThinFrame\Doctrine\Entities'
        ];
    }
}
