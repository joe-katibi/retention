<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontlineRetention extends Model
{
    use HasFactory;

    protected $table = 'retention_at_frontline';

    /**
 * The attributes that are mass assignable.
 *
 * @var array<int, string>
 */
protected $fillable = [
    'account',
    'channel',
    'contact_bill_cycle',
    'agent_name',
    'date',
    'contact_day',
    'due_this_month',
    'month_of_contact',
    'week_of_contact',
    'contact_month_status',
    'contact_month_package',
    'contact_month_package_price',
    'contact_month_account_sales',
    'current_bill_cycle',
    'current_month_status',
    'current_month_package',
    'current_month_package_price',
    'sales_retention',
    'current_month_price_variance',
    'upgrade_downgrade',
    'payment_status',

];
}
