<?php

namespace Sendcloud\Shipping\Service\Utility;

use Sendcloud\Shipping\Entity\StateMachine\StateMachineRepository;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

/**
 * Class DeliveryStatusMapper
 *
 * @package Sendcloud\Shipping\Service\Utility
 */
class DeliveryStateMapper
{
    public const SENDCLOUD_STATUS_READY_TO_SEND = 1000;
    public const SENDCLOUD_STATUS_ANNOUNCED = 1;
    public const SENDCLOUD_STATUS_ANNOUNCED_NOT_COLLECTED = 13;
    public const SENDCLOUD_STATUS_DELIVERED = 11;
    public const SENDCLOUD_STATUS_COLLECTED_BY_CUSTOMER = 93;
    public const SENDCLOUD_STATUS_CANCELLED_UPSTREAM = 1998;
    public const SENDCLOUD_STATUS_CANCELLING = 1999;
    public const SENDCLOUD_STATUS_CANCELLED = 2000;
    public const SENDCLOUD_STATUS_CANCELLING_UPSTREAM = 2001;
    public const SENDCLOUD_STATUS_TO_SORTING = 3;
    public const SENDCLOUD_STATUS_SORTED = 5;
    public const SENDCLOUD_STATUS_UNSORTED = 6;
    public const SENDCLOUD_STATUS_DELIVERY_FAILED = 8;
    public const SENDCLOUD_STATUS_COLLECT_ERROR = 15;
    public const SENDCLOUD_STATUS_UNSORTED2 = 18;
    public const SENDCLOUD_STATUS_UNDELIVERABLE = 80;
    public const SENDCLOUD_STATUS_SHIPMENT_ON_ROUTE = 91;
    public const SENDCLOUD_STATUS_PICKED_UP_BY_DRIVER = 22;
    public const SENDCLOUD_STATUS_DRIVER_ON_ROUTE = 92;
    public const SENDCLOUD_STATUS_AWAITING_CUSTOMER_PICKUP = 12;
    public const SENDCLOUD_STATUS_DELAYED = 4;
    public const SENDCLOUD_STATUS_DELIVERY_FORCED = 10;
    public const SENDCLOUD_STATUS_NO_LABEL = 999;
    public const SENDCLOUD_STATUS_ANNOUNCEMENT_FAILED = 1002;
    public const SENDCLOUD_STATUS_AT_CUSTOMS = 62989;
    public const SENDCLOUD_STATUS_DATE_CHANGED = 62994;
    public const SENDCLOUD_STATUS_ADDRESS_INVALID = 62997;
    public const SENDCLOUD_STATUS_DELIVERY_METHOD_CHANGED = 62993;
    public const SENDCLOUD_STATUS_AT_STORING_CENTRE = 62990;
    public const SENDCLOUD_STATUS_REFUSED_BY_RECIPIENT = 62991;
    public const SENDCLOUD_STATUS_RETURNED_TO_SENDER = 62992;
    public const SENDCLOUD_STATUS_DELIVERY_ADDRESS_CHANGED = 62995;
    private const ORDER_DELIVERY_STATE_FIELD_NAME = 'stateId';

    /**
     * @var StateMachineRepository
     */
    private $stateMachineRepository;
    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    private static $statusMap = [
        self::SENDCLOUD_STATUS_READY_TO_SEND => OrderDeliveryStates::STATE_OPEN,
        self::SENDCLOUD_STATUS_ANNOUNCED => OrderDeliveryStates::STATE_OPEN,
        self::SENDCLOUD_STATUS_ANNOUNCED_NOT_COLLECTED => OrderDeliveryStates::STATE_OPEN,
        self::SENDCLOUD_STATUS_DELIVERED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_COLLECTED_BY_CUSTOMER => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_CANCELLED_UPSTREAM => OrderDeliveryStates::STATE_CANCELLED,
        self::SENDCLOUD_STATUS_CANCELLING => OrderDeliveryStates::STATE_CANCELLED,
        self::SENDCLOUD_STATUS_CANCELLED => OrderDeliveryStates::STATE_CANCELLED,
        self::SENDCLOUD_STATUS_CANCELLING_UPSTREAM => OrderDeliveryStates::STATE_CANCELLED,
        self::SENDCLOUD_STATUS_TO_SORTING => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_SORTED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_UNSORTED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DELIVERY_FAILED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_COLLECT_ERROR => OrderDeliveryStates::STATE_OPEN,
        self::SENDCLOUD_STATUS_UNSORTED2 => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_UNDELIVERABLE => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_SHIPMENT_ON_ROUTE => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_PICKED_UP_BY_DRIVER => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DRIVER_ON_ROUTE => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_AWAITING_CUSTOMER_PICKUP => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DELAYED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DELIVERY_FORCED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_AT_CUSTOMS => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DATE_CHANGED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_ADDRESS_INVALID => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DELIVERY_METHOD_CHANGED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_AT_STORING_CENTRE => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_REFUSED_BY_RECIPIENT => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_RETURNED_TO_SENDER => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_DELIVERY_ADDRESS_CHANGED => OrderDeliveryStates::STATE_SHIPPED,
        self::SENDCLOUD_STATUS_NO_LABEL => OrderDeliveryStates::STATE_OPEN,
        self::SENDCLOUD_STATUS_ANNOUNCEMENT_FAILED => OrderDeliveryStates::STATE_OPEN
    ];

    /**
     * DeliveryStatusMapper constructor.
     *
     * @param StateMachineRepository $stateMachineRepository
     * @param StateMachineRegistry $stateMachineRegistry
     */
    public function __construct(StateMachineRepository $stateMachineRepository, StateMachineRegistry $stateMachineRegistry)
    {
        $this->stateMachineRepository = $stateMachineRepository;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * Updates order delivery status.
     *
     * @param string $orderDeliveryId
     * @param string $sendcloudStatusId
     */
    public function updateStatus(string $orderDeliveryId, string $sendcloudStatusId): void
    {
        $context = Context::createDefaultContext();
        $newState = $this->stateMachineRepository->getOrderDeliveryState(
            $this->getDeliveryState($sendcloudStatusId),
            $context
        );
        if (!$newState) {
            return;
        }

        $transitionActionName = $this->getTransitionActionName($orderDeliveryId, $newState->getTechnicalName());
        if (!$transitionActionName) {
            return;
        }

        $this->stateMachineRegistry->transition(
            new Transition(
                OrderDeliveryDefinition::ENTITY_NAME,
                $orderDeliveryId,
                $transitionActionName,
                self::ORDER_DELIVERY_STATE_FIELD_NAME
            ),
            $context
        );
    }

    /**
     * Gets state machine transition action name for a given new state
     *
     * @param string $orderDeliveryId
     * @param string $newStateName
     * @return string|null
     */
    private function getTransitionActionName(string $orderDeliveryId, string $newStateName): ?string
    {
        /** @var StateMachineTransitionEntity[] $availableTransitions */
        $availableTransitions = $this->stateMachineRegistry->getAvailableTransitions(
            OrderDeliveryDefinition::ENTITY_NAME,
            $orderDeliveryId,
            self::ORDER_DELIVERY_STATE_FIELD_NAME,
            Context::createDefaultContext()
        );

        foreach ($availableTransitions as $availableTransition) {
            $toStateMachineState = $availableTransition->getToStateMachineState();
            if ($toStateMachineState && $toStateMachineState->getTechnicalName() === $newStateName) {
                return $availableTransition->getActionName();
            }
        }

        return null;
    }

    /**
     * Return order delivery state
     *
     * @param int $sendcloudStatusId
     *
     * @return string
     */
    private function getDeliveryState(int $sendcloudStatusId): string
    {
        return array_key_exists($sendcloudStatusId, self::$statusMap) ? self::$statusMap[$sendcloudStatusId] : OrderDeliveryStates::STATE_OPEN;
    }
}
