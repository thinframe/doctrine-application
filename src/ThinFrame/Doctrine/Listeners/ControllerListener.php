<?php

namespace ThinFrame\Doctrine\Listeners;

use Doctrine\ORM\EntityManager;
use ThinFrame\Events\ListenerInterface;
use ThinFrame\Karma\Events\ControllerActionEvent;

/**
 * Class ControllerListener
 *
 * @package ThinFrame\Doctrine\Entities
 * @since   0.2
 */
class ControllerListener implements ListenerInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get event mappings ["event"=>["method"=>"methodName","priority"=>1]]
     *
     * @return array
     */
    public function getEventMappings()
    {
        if (!class_exists('ThinFrame\Karma\Events\ControllerActionEvent')) {
            return [];
        }
        //karma integration
        return [
            ControllerActionEvent::EVENT_ID => [
                'method' => 'onControllerAction'
            ]
        ];
    }

    /**
     * Attache the entity manager to the controller
     *
     * @param ControllerActionEvent $event
     */
    public function onControllerAction(ControllerActionEvent $event)
    {
        $event->getController()->getServices()->set('entityManager', $this->entityManager);
    }
}
