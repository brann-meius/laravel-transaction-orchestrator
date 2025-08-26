<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Enums;

enum HttpRollbackPolicy: string
{
    case ROLLBACK_NONE = 'never';
    case ROLLBACK_ON_4XX = '4xx';
    case ROLLBACK_ON_5XX = '5xx';
    case ROLLBACK_ON_4XX_5XX = '4xx_5xx';
}
