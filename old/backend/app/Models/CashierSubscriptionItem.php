<?php

namespace App\Models;

use Laravel\Cashier\SubscriptionItem as CashierSubscriptionItemBase;

class CashierSubscriptionItem extends CashierSubscriptionItemBase
{
    protected $table = 'cashier_subscription_items';
}
