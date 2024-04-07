<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Authorize;

use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class AuthorizeStateHelper implements AuthorizeStateHelperInterface
{
    /** @var OrderTransactionStateHandler */
    private $transactionStateHandler;

    /** @var StateDataHelperInterface */
    private $stateDataHelper;

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler, StateDataHelperInterface $stateDataHelper, StateMachineRegistry $stateMachineRegistry, LoggerInterface $logger)
    {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateDataHelper         = $stateDataHelper;
        $this->stateMachineRegistry    = $stateMachineRegistry;
        $this->logger                  = $logger;
    }

    public function processOrderAuthorize(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->authorizeTransaction($transaction, $context);
        }
    }

    private function authorizeTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        if (!defined('Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions::ACTION_AUTHORIZE')) {
            // authorize state was introduced in shopware v6.3
            return;
        }

        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                if (method_exists($this->transactionStateHandler, 'authorize')) {
                    $this->transactionStateHandler->authorize($transaction->getId(), $context);
                } else {
                    $this->stateMachineRegistry->transition(
                        new Transition(
                            OrderTransactionDefinition::ENTITY_NAME,
                            $transaction->getId(),
                            /** @phpstan-ignore-next-line */
                            StateMachineTransitionActions::ACTION_AUTHORIZE,
                            'stateId'
                        ),
                        $context
                    );
                }
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
