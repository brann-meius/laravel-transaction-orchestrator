<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Support\Database;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Support\Database\TransactionManager;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionObject;

class TransactionManagerTest extends TestCase
{
    #[Test, DataProvider('transactionalProvider')]
    public function initializeConnectionsFromResolvesConnectionsAndIsFluent(array $connectionNames): void
    {
        $manager = resolve(TransactionManager::class);

        $this->assertSame(
            $manager,
            $manager->initializeConnectionsFrom(new Transactional($connectionNames))
        );

        $reflection = new ReflectionObject($manager);
        $connections = $reflection->getProperty('connections')->getValue($manager);

        $this->assertCount(count($connectionNames), $connections);
        foreach ($connections as $connection) {
            $this->assertInstanceOf(Connection::class, $connection);
        }
    }

    #[Test]
    public function initializeConnectionsPropagatesExceptionsFromConnections(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database connection [wrong] not configured.');

        resolve(TransactionManager::class)->initializeConnectionsFrom(new Transactional('wrong'));
    }

    public static function transactionalProvider(): array
    {
        return [
            'single' => [['mysql']],
            'two' => [['mysql', 'pgsql']],
            'three' => [['mysql', 'sqlite', 'pgsql']],
        ];
    }
}
