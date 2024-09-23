<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FrontlineRetention;
use App\Models\Movement;
use App\Models\PackagePrice;
use Carbon\Carbon;

class UpdateRetentionMonthly extends Command
{
    protected $signature = 'retention:update-monthly';
    protected $description = 'Update Retention Data Monthly';

    public function handle()
    {
        $this->info('Starting monthly retention update...');

        // Get all records from the movement table
        $movements = Movement::all();

        foreach ($movements as $movement) {
            // Fetch the corresponding record from RetentionAtFrontline based on account number
            $retention = FrontlineRetention::where('account', $movement->subs_account_no)->first();

            if ($retention) {
                // Update the relevant fields
                $retention->current_bill_cycle = $movement->subs_bill_cycle;
                $retention->current_month_status = $this->determineStatus($movement);
                $retention->current_month_package = $movement->subs_current_package;
                $retention->current_month_package_price = $this->getPackagePrice($movement->subs_current_package);

                // Save the updated retention data
                $retention->save();
            }
        }

        $this->info('Monthly retention update completed.');
    }

    private function determineStatus($movement)
    {
        // Determine current_month_status based on subs_current_status and subs_current_package
        if ($movement->subs_current_status === 'Active') {
            return $movement->subs_current_package ? 'Active' : 'NPD';
        }

        return $movement->subs_current_status;
    }

    private function getPackagePrice($packageName)
    {
        // Retrieve package price based on package name
        $package = PackagePrice::where('package_name', $packageName)->first();
        return $package ? $package->package_price : null;
    }
}

