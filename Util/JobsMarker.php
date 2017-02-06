<?php

namespace SerendipityHQ\Bundle\QueuesBundle\Util;

use Doctrine\ORM\EntityManager;
use SerendipityHQ\Bundle\QueuesBundle\Model\Job;

/**
 * Changes the status of Jobs during their execution attaching to them execution info.
 */
class JobsMarker
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Job $job
     * @param array $info
     */
    public function markJobAsAborted(Job $job, array $info)
    {
        $this->markJobAsClosed($job, Job::STATUS_ABORTED, $info);
    }

    /**
     * @param Job $job
     * @param array $info
     */
    public function markJobAsFailed(Job $job, array $info)
    {
        $this->markJobAsClosed($job, Job::STATUS_FAILED, $info);
    }

    /**
     * @param Job $job
     * @param array $info
     */
    public function markJobAsFinished(Job $job, array $info)
    {
        $this->markJobAsClosed($job, Job::STATUS_FINISHED, $info);
    }

    /**
     * @param Job $job
     * @param array $info
     */
    public function markJobAsPending(Job $job, array $info)
    {
        $this->markJob($job, Job::STATUS_PENDING, $info);
    }

    /**
     * @param Job $job
     */
    public function markJobAsRunning(Job $job)
    {
        $this->markJob($job, Job::STATUS_RUNNING);
    }

    /**
     * @param Job $job
     * @param string $status
     * @param array $info
     */
    private function markJobAsClosed(Job $job, string $status, array $info)
    {
        $info['closed_at'] = new \DateTime();
        $this->markJob($job, $status, $info);
    }

    /**
     * @param Job $job
     * @param string $status
     * @param array $info
     */
    private function markJob(Job $job, string $status, array $info = [])
    {
        $reflectedClass = new \ReflectionClass($job);

        // First of all set the current status
        $reflectedProperty = $reflectedClass->getProperty('status');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($job, $status);

        // Now set the other info
        foreach ($info as $property => $value) {
            switch ($property) {
                case 'closed_at':
                    $reflectedProperty = $reflectedClass->getProperty('closedAt');
                    break;
                case 'debug':
                    $reflectedProperty = $reflectedClass->getProperty('debug');
                    break;
                case 'output':
                    $reflectedProperty = $reflectedClass->getProperty('output');
                    break;
                case 'exit_code':
                    $reflectedProperty = $reflectedClass->getProperty('exitCode');
                    break;
                case 'started_at':
                    $reflectedProperty = $reflectedClass->getProperty('startedAt');
                    break;
                default:
                    throw new \RuntimeException(sprintf('The property %s is not managed. Manage it or verify its spelling is correct.', $property));
            }

            // Set the property as accessible
            $reflectedProperty->setAccessible(true);
            $reflectedProperty->setValue($job, $value);
        }

        // Persist the entity again (just to be sure it is managed)
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        // Now first set to null and then unset to save memory ASAP
        $reflectedClass =
        $reflectedProperty =
        $property =
        $value = null;
        unset($reflectedClass, $reflectedProperty, $property, $value);
    }
}