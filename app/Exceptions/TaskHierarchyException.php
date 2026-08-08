<?php

namespace App\Exceptions;

use RuntimeException;

class TaskHierarchyException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'TASK_HIERARCHY_INCOMPLETE',
    ) {
        parent::__construct($message);
    }
}