<?php

namespace Sendcloud\Shipping\Entity\Process;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProcessEntityRepository
 *
 * @package Sendcloud\Shipping\Entity\Process
 */
class ProcessEntityRepository
{

    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * ProcessEntityRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Creates/Updates process
     *
     * @param string $guid
     * @param string $serializedRunner
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function saveGuidAndRunner(string $guid, string $serializedRunner): void
    {
        $process = $this->getProcessByGuid($guid);
        $context = Context::createDefaultContext();
        if (!$process) {
            $this->baseRepository->create(
                [
                    ['guid' => $guid, 'runner' => $serializedRunner]
                ],
                $context
            );
        } else {
            $this->baseRepository->update([
                ['id' => $process->getId(), 'guid' => $guid, 'runner' => $serializedRunner],
            ],
                $context
            );
        }
    }

    /**
     * Deletes process by its guid
     *
     * @param string $guid
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function deleteByGuid(string $guid): void
    {
        $process = $this->getProcessByGuid($guid);
        $context = Context::createDefaultContext();
        if ($process) {
            $this->baseRepository->delete([['id' => $process->getId()]], $context);
        }
    }

    /**
     * Returns process by guid
     *
     * @param string $guid
     *
     * @return ProcessEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getProcessByGuid(string $guid): ?ProcessEntity
    {
        $results = $this->baseRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('guid', $guid)),
            Context::createDefaultContext()
        );

        return $results->getEntities()->first();
    }
}
