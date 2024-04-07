<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\OrderValidator\OrderValidatorInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PostWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderChangeEventListener implements EventSubscriberInterface
{
    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var TranslatorInterface */
    private $translator;

    /** @var OrderValidatorInterface */
    private $orderValidator;

    /** @var string */
    private $currentLocale;

    public function __construct(
        OrderFetcherInterface $orderFetcher,
        TranslatorInterface $translator,
        OrderValidatorInterface $orderValidator
    ) {
        $this->orderFetcher   = $orderFetcher;
        $this->translator     = $translator;
        $this->orderValidator = $orderValidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostWriteValidationEvent::class => 'validateKlarnaOrder',
        ];
    }

    /**
     * @see \KlarnaPayment\Components\Controller\Administration\OrderUpdateController::update Change accordingly to keep functionality synchronized
     */
    public function validateKlarnaOrder(PostWriteValidationEvent $event): void
    {
        if ($event->getContext()->getScope() === ContextScope::INTERNAL_SCOPE) {
            // only check user generated changes
            return;
        }

        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            // No live data change, just draft versions
            return;
        }

        $order = $this->getOrderFromWriteCommands($event);

        if ($order === null || !$this->orderValidator->isKlarnaOrder($order)) {
            return;
        }

        $this->setCurrentLocale($event);

        $this->validateOrderAddress($order, $event);
        $this->validateLineItems($order, $event);
    }

    private function setCurrentLocale(PostWriteValidationEvent $event): void
    {
        $languages           = $event->getWriteContext()->getLanguages();
        $this->currentLocale = $languages[$event->getContext()->getLanguageId()]['code'] ?? 'en-GB';
    }

    private function validateLineItems(OrderEntity $orderEntity, PostWriteValidationEvent $event): void
    {
        if ($this->orderValidator->validateAndInitLineItemsHash($orderEntity, $event->getContext())) {
            return;
        }

        $violation = new ConstraintViolation(
            $this->translator->trans('KlarnaPayment.errorMessages.lineItemChangeDeclined', [], null, $this->currentLocale),
            '',
            [],
            '',
            '',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);

        $event->getExceptions()->add(new WriteConstraintViolationException($violations));
    }

    private function validateOrderAddress(OrderEntity $orderEntity, PostWriteValidationEvent $event): void
    {
        if ($this->orderValidator->validateAndInitOrderAddressHash($orderEntity, $event->getContext())) {
            return;
        }

        $violation = new ConstraintViolation(
            $this->translator->trans('KlarnaPayment.errorMessages.addressChangeDeclined', [], null, $this->currentLocale),
            '',
            [],
            '',
            '',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);

        $event->getExceptions()->add(new WriteConstraintViolationException($violations));
    }

    private function getOrderFromWriteCommands(PostWriteValidationEvent $event): ?OrderEntity
    {
        foreach ($event->getCommands() as $command) {
            if (!($command instanceof UpdateCommand)) {
                continue;
            }

            $primaryKeys = $command->getPrimaryKey();

            if (!array_key_exists('id', $primaryKeys) || empty($primaryKeys['id'])) {
                continue;
            }

            if ($command->getDefinition()->getClass() === OrderAddressDefinition::class) {
                return $this->orderFetcher->getOrderFromOrderAddress($primaryKeys['id'], $event->getContext());
            }

            if ($command->getDefinition()->getClass() === OrderLineItemDefinition::class) {
                return $this->orderFetcher->getOrderFromOrderLineItem($primaryKeys['id'], $event->getContext());
            }

            if ($command->getDefinition()->getClass() === OrderDefinition::class) {
                return $this->orderFetcher->getOrderFromOrder($primaryKeys['id'], $event->getContext());
            }
        }

        return null;
    }
}
