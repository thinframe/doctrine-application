<?php

require_once 'vendor/autoload.php';

use ThinFrame\Doctrine\DoctrineApplication;

$app = new DoctrineApplication();

$container = $app->getApplicationContainer();

$em = $container->get('thinframe.doctrine.entity_manager_factory');
/* @var $em \ThinFrame\Doctrine\EntityManagerFactory */

$em->createEntityManager();
