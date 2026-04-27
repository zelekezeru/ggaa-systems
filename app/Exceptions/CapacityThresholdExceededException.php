<?php

namespace App\Exceptions;

use Exception;

class CapacityThresholdExceededException extends Exception
{
    public readonly int $currentLoad;
    public readonly int $requiredPoints;
    public readonly int $maxCapacity;

    public function __construct(int $currentLoad, int $requiredPoints, int $maxCapacity = 30)
    {
        $this->currentLoad    = $currentLoad;
        $this->requiredPoints = $requiredPoints;
        $this->maxCapacity    = $maxCapacity;

        $projected = $currentLoad + $requiredPoints;

        parent::__construct(
            "Capacity Threshold Exceeded: assigning this task would bring the employee to " .
            "{$projected}/{$maxCapacity} points (current: {$currentLoad}, required: +{$requiredPoints})."
        );
    }
}
