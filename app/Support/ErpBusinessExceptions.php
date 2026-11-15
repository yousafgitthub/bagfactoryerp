<?php

namespace App\Support;

use App\Services\Accounting\AccountingException;
use App\Services\Bag\BagBatchException;
use App\Services\Dana\DanaBatchException;
use App\Services\Hr\HrException;
use App\Services\Hr\PayrollException;
use App\Services\Inventory\InventoryPostingException;
use App\Services\Purchases\PurchaseException;
use App\Services\Sales\SalesException;

class ErpBusinessExceptions
{
    /**
     * @return list<class-string<\Throwable>>
     */
    public static function classes(): array
    {
        return [
            SalesException::class,
            PurchaseException::class,
            InventoryPostingException::class,
            DanaBatchException::class,
            BagBatchException::class,
            AccountingException::class,
            HrException::class,
            PayrollException::class,
        ];
    }

    public static function handles(\Throwable $throwable): bool
    {
        foreach (self::classes() as $class) {
            if ($throwable instanceof $class) {
                return true;
            }
        }

        return false;
    }
}