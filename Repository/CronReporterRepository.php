<?php

namespace Tranchard\CronMonitorApiBundle\Repository;

use Doctrine\MongoDB\ArrayIterator;
use Doctrine\MongoDB\Iterator;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Math\Statistics;

class CronReporterRepository extends DocumentRepository
{

    /**
     * @return int
     */
    public function count()
    {
        return $this->createQueryBuilder()->count()->getQuery()->execute();
    }

    /**
     * @param array $pipeline
     *
     * @return Iterator
     */
    private function aggregate(array $pipeline)
    {
        $collection = $this->dm->getDocumentCollection(CronReporter::class);

        return $collection->aggregate($pipeline);
    }

    /**
     * @param array $filters
     *
     * @return Iterator
     */
    public function getStatistics(array $filters)
    {
        return $this->aggregate(
            [
                ['$match' => $filters,],
                [
                    '$group' => [
                        '_id'   => [
                            'project'     => '$project',
                            'environment' => '$environment',
                            'job'         => '$job',
                            'description' => '$description',
                        ],
                        'count' => ['$sum' => 1],
                    ],
                ],
            ]
        );
    }

    /**
     * @param string|null $environment
     *
     * @return Cursor
     */
    public function getProjects(string $environment = null)
    {
        if (is_null($environment)) {
            $query = $this->createQueryBuilder()->distinct('project')->getQuery();
        } else {
            $query = $this->createQueryBuilder()->distinct('project')->field('environment')->equals(
                $environment
            )->getQuery();
        }

        return $query->execute();
    }

    /**
     * @param array       $criteria
     * @param array       $sort
     * @param int|null    $limit
     * @param int|null    $skip
     * @param string|null $distinctField
     *
     * @return ArrayIterator
     */
    public function getBy(
        array $criteria,
        array $sort = [],
        int $limit = null,
        int $skip = null,
        string $distinctField = null
    ): ArrayIterator {
        $queryBuilder = $this->createQueryBuilder();
        foreach ($criteria as $field => $criterion) {
            if (is_array($criterion)) {
                if (isset($criterion['method']) && isset($criterion['value'])) {
                    $queryBuilder->field($field)->{$criterion['method']}($criterion['value']);
                } else {
                    foreach ($criterion as $subCriterion) {
                        $queryBuilder->field($field)->{$subCriterion['method']}($subCriterion['value']);
                    }
                }
            } else {
                $queryBuilder->field($field)->equals(new \MongoRegex(sprintf('/%s/i', $criterion)));
            }
        }
        if (!is_null($distinctField)) {
            $queryBuilder->distinct($distinctField);
        } elseif (is_array($sort)) {
            foreach ($sort as $field => $direction) {
                $queryBuilder->sort($field, $direction);
            }
        }
        if (!is_null($limit)) {
            $queryBuilder->limit($limit);
        }
        if (!is_null($skip)) {
            $queryBuilder->skip($skip);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param array $criteria
     *
     * @return int
     */
    public function countBy(array $criteria)
    {
        $queryBuilder = $this->createQueryBuilder()->count();
        foreach ($criteria as $field => $criterion) {
            if (is_array($criterion)) {
                if (isset($criterion['method']) && isset($criterion['value'])) {
                    $queryBuilder->field($field)->{$criterion['method']}($criterion['value']);
                } else {
                    foreach ($criterion as $subCriterion) {
                        $queryBuilder->field($field)->{$subCriterion['method']}($subCriterion['value']);
                    }
                }
            } else {
                $queryBuilder->field($field)->equals($criterion);
            }
        }

        return $queryBuilder->getQuery()->execute();
    }


    /**
     * @param CronReporter $cronReporter
     * @param bool         $useTokens
     *
     * @return float
     */
    public function computeAverageDuration(CronReporter $cronReporter, bool $useTokens): float
    {
        $durations = array_column(
            iterator_to_array($this->collectData($cronReporter, $useTokens, ['duration'])),
            'duration'
        );

        return Statistics::averageDuration($durations);
    }

    /**
     * @param CronReporter $cronReporter
     * @param bool         $useTokens
     * @param array        $fields
     *
     * @return Cursor
     */
    private function collectData(CronReporter $cronReporter, bool $useTokens, array $fields = []): Cursor
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select($fields);

        $queryBuilder->field('job')->equals($cronReporter->getjob());
        $queryBuilder->field('environment')->equals($cronReporter->getEnvironment());
        $queryBuilder->field('status')->equals(CronReporter::STATUS_SUCCESS);
        $queryBuilder->field('project')->equals($cronReporter->getProject());

        $extraPayload = $cronReporter->getExtraPayload();
        if ($useTokens && isset($extraPayload['tokens'])) {
            $queryBuilder->field('extraPayload.tokens')->equals(
                [
                    'arguments' => $extraPayload['tokens']['arguments'],
                    'options'   => $extraPayload['tokens']['options'],
                ]
            );
        }
        $queryBuilder->limit(10);
        $queryBuilder->hydrate(false);

        return $queryBuilder->getQuery()->execute();
    }
}
