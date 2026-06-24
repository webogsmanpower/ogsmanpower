<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscriptionBase;

class CashierSubscription extends CashierSubscriptionBase
{
    protected $table = 'cashier_subscriptions';
}
