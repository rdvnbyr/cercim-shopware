<?php

declare(strict_types=1);

namespace KlarnaPayment\Command;

use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ActivatePaymentMethods extends Command
{
    // TODO: Adjust this if compatibility is at least > 6.4.0.0
    /** @var EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator */
    private $paymentMethodRepository;

    // TODO: Adjust this if compatibility is at least > 6.4.0.0

    /**
     * @param EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator $paymentMethodRepository
     */
    public function __construct($paymentMethodRepository)
    {
        parent::__construct();

        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $context  = Context::createDefaultContext();
        $criteria = new Criteria(array_keys(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES));

        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($this->paymentMethodRepository->search($criteria, $context) as $paymentMethod) {
            $io->writeln(sprintf('<info>Activating %s</info>', $paymentMethod->getName()));

            $update = [
                'id'     => $paymentMethod->getId(),
                'active' => true,
            ];

            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
                $this->paymentMethodRepository->update([$update], $context);
            });
        }

        return 0;
    }
}
