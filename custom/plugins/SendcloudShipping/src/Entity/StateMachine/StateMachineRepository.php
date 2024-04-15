<?php

namespace Sendcloud\Shipping\Entity\StateMachine;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;

/**
 * Class StateMachineRepository
 *
 * @package Sendcloud\Shipping\Entity\StateMachine
 */
class StateMachineRepository
{
    /**
     * @var EntityRepository
     */
    private $stateMachineRepository;
    /**
     * @var EntityRepository
     */
    private $stateMachineStateRepository;

    /**
     * StateMachineRepository constructor.
     *
     * @param EntityRepository $stateMachineRepository
     * @param EntityRepository $stateMachineStateRepository
     */
    public function __construct(EntityRepository $stateMachineRepository, EntityRepository $stateMachineStateRepository)
    {
        $this->stateMachineRepository = $stateMachineRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
    }

    /**
     * @param Context $context
     *
     * @return StateMachineEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderDeliveryStateMachine(Context $context): ?StateMachineEntity
    {
        return $this->getStateMachine('order_delivery.state', $context);
    }

    /**
     * Returns state machine by technical name
     *
     * @param string $technicalName
     * @param Context $context
     *
     * @return StateMachineEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getStateMachine(string $technicalName, Context $context): ?StateMachineEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $this->stateMachineRepository->search($criteria, $context)->first();

        return $stateMachine;
    }

    /**
     * Return order delivery state
     *
     * @param string $technicalName
     * @param Context $context
     *
     * @return StateMachineStateEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderDeliveryState(string $technicalName, Context $context): ?StateMachineStateEntity
    {
        if ($stateMachine = $this->getOrderDeliveryStateMachine($context)) {
            return $this->getStateMachineState($technicalName, $stateMachine->getId(), $context);
        }

        return null;
    }

    /**
     * Return state
     *
     * @param string $technicalName
     * @param string $stateMachineId
     * @param Context $context
     *
     * @return StateMachineStateEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getStateMachineState(string $technicalName, string $stateMachineId, Context $context): ?StateMachineStateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('stateMachineId',  $stateMachineId));
        /** @var StateMachineStateEntity $stateMachineState */
        $stateMachineState = $this->stateMachineStateRepository->search($criteria, $context)->first();

        return $stateMachineState;
    }

    /**
     * @param string $stateMachineId
     * @param Context $context
     *
     * @return StateMachineStateEntity|null
     */
    public function getStateMachineById(string $stateMachineId, Context $context): ?StateMachineStateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id',  $stateMachineId));
        /** @var StateMachineStateEntity $stateMachineState */
        $stateMachineState = $this->stateMachineStateRepository->search($criteria, $context)->first();

        return $stateMachineState;
    }
}
