<?php

namespace Sendcloud\Shipping\Entity\Currency;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * Class CurrencyRepository
 *
 * @package Sendcloud\Shipping\Entity\Currency
 */
class CurrencyRepository
{
    public const EURO = 'EUR';

    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * CurrencyRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns euro currency entity
     *
     * @return CurrencyEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getEuroCurrency(): ?CurrencyEntity
    {
        return $this->getCurrencyByIsoCode(self::EURO);
    }

    /**
     * Returns currency by iso code
     *
     * @param string $isoCode
     *
     * @return CurrencyEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCurrencyByIsoCode(string $isoCode): ?CurrencyEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
