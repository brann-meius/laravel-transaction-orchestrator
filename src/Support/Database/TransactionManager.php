<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Throwable;

class TransactionManager
{
    /**
     * List of currently active (open) database transactions.
     *
     * @var Connection[] $connections
     */
    private array $connections = [];

    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        //
    }

    public function initializeConnectionsFrom(Transactional $transactional): self
    {
        foreach ($transactional->connections as $connection) {
            $this->connections[] = $this->databaseManager->connection($connection);
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function beginTransactions(): self
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function commitTransactions(): self
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function rollBackTransactions(): self
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }

        return $this;
    }
}
