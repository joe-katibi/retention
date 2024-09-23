<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPerformance extends Model
{
    use HasFactory;
        /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'team',
        'kpi',
        'billed',
        'retained',
        'billed_retained_percentage',
        'npd_contacts',
        'npd_conversations',
        'npd_contacts_npd_conversations_percentage',
        'upgrades',
        'downgrades',
        'upgrades_downgrade_percentage',
        'm1_sales',
        'M1_active',
        'M1_active_m1_sales_percentage',
        'month_performance',
    ];
}
