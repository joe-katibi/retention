<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CRM;
use App\Models\FrontlineRetention;
use App\Models\PackagePrice;
use App\Models\Movement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessRetentionData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $startDate = Carbon::createFromDate(2024, 1, 1); // Starting date
            $finalEndDate = Carbon::yesterday(); // The final end date (yesterday's date)
            $excludedAccounts = ['DTH-00', 'DROPPED-00', 'SALES-00', 'MAIL-00', 'TRAINING-00'];

            while ($startDate->lte($finalEndDate)) {
                $endDate = $startDate->copy()->addDay(); // Set end date to one day after start date

                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Process data one by one instead of in chunks
                    $dayData = CRM::whereDate('created_at', $date->format('Y-m-d'))
                        ->whereNotIn('account_no', $excludedAccounts)
                        ->cursor(); // Using cursor to iterate over results one by one

                    foreach ($dayData as $data) {
                        try {
                            // Validate data before processing
                            if (!$data->sub_status || !$data->bill_cycle) {
                                Log::warning('Missing required data for account: ' . $data->account_no);
                                continue;
                            }

                              // Fetch package details if $data->current_pkg is not null
                            if ($data->current_pkg !== null) {
                                 $packageDetails = PackagePrice::where('package_name', $data->current_pkg)->first();

                                if (!$packageDetails) {
                                        Log::warning('Package details not found for package name: ' . $data->current_pkg);
                                    }
                            } else {
                                  $packageDetails = null; // Assign null if $data->current_pkg is null
                            }

                            // Assign values to $contactMonthPackage and $contactMonthPackagePrice
                            $contactMonthPackage = $packageDetails ? $packageDetails->package_initials : null;
                            $contactMonthPackagePrice = $packageDetails ? $packageDetails->package_price : null;

                            // Continue processing even if $data->current_pkg is null


                            // Determine contact_month_status
                            if (
                                ($data->sub_status === 'Active' || $data->sub_status === 'ACT' || $data->sub_status === 'CMP' || $data->sub_status === 'OFC' || $data->sub_status === 'STF') &&
                                $data->current_pkg !== null
                            ) {
                                $contactMonthStatus = 'Active';
                            } elseif (
                                ($data->sub_status === 'Active' || $data->sub_status === 'ACT' || $data->sub_status === 'CMP' || $data->sub_status === 'OFC' || $data->sub_status === 'STF') &&
                                $data->current_pkg === null
                            ) {
                                $contactMonthStatus = 'NPD';
                            } else {
                                $contactMonthStatus = $data->sub_status;
                            }




                            // Determine due_this_month status
                            $dueThisMonth = $data->bill_cycle > $date->format('d') ? 'Due this Month' : 'Due next Month';

                            // Save or update the record
                            $retentionRecord = FrontlineRetention::updateOrCreate(
                                ['account' => $data->account_no, 'date' => $date->format('Y-m-d')],
                                [
                                    'account' => $data->account_no,
                                    'channel' => $data->channel_name,
                                    'contact_bill_cycle' => $data->bill_cycle,
                                    'agent_name' => $data->name,
                                    'date' => $date->format('Y-m-d'),
                                    'contact_day' => $date->format('d'),
                                    'due_this_month' => $dueThisMonth,
                                    'month_of_contact' => $date->format('F'),
                                    'week_of_contact' => $date->weekOfMonth,
                                    'contact_month_status' => $contactMonthStatus,
                                    'contact_month_package' => $contactMonthPackage,
                                    'contact_month_package_price' => $contactMonthPackagePrice,
                                ]
                            );

                            // Run additional update logic here
                            $this->runRetentionUpdate($retentionRecord);

                            // Calculate sales retention
                            $installedDate = Carbon::parse($data->installed_date);
                            $oneMonthAgo = Carbon::now()->subMonth();
                            $twoMonthsAgo = Carbon::now()->subMonths(2);

                            $salesRetention = null;
                            $contactMonthAccountSales = null;

                            if ($installedDate->greaterThanOrEqualTo($oneMonthAgo)) {
                                $salesRetention = 'M0 Account';
                                $contactMonthAccountSales = $data->account_no;
                            } elseif ($installedDate->greaterThanOrEqualTo($twoMonthsAgo)) {
                                if ($contactMonthStatus === 'Active' && $data->current_month_status === 'Active') {
                                    $salesRetention = 'M1 Retained';
                                } elseif ($contactMonthStatus === 'Active' && $data->current_month_status !== 'Active') {
                                    $salesRetention = 'M1 Decay';
                                } else {
                                    $salesRetention = 'Existing Base';
                                }
                                $contactMonthAccountSales = $data->account_no;
                            } else {
                                $salesRetention = 'Existing Base';
                            }

                            // Update sales retention data
                            $retentionRecord->update([
                                'sales_retention' => $salesRetention,
                                'contact_month_account_sales' => $contactMonthAccountSales,
                            ]);

                            // Calculate price variance and update fields
                            if ($retentionRecord) {
                                   // Fetch values from the retention record
                                     $currentMonthPackagePrice = $retentionRecord->current_month_package_price;
                                      $contactMonthPackagePrices = $retentionRecord->contact_month_package_price;
                                      $currentMonthPackage = $retentionRecord->current_month_package;

                                       // Check if the required fields are not null before proceeding
                             if ($currentMonthPackagePrice !== null && $contactMonthPackagePrices !== null && $currentMonthPackage !== null) {
                                    $currentMonthPriceVariance = $currentMonthPackagePrice - $contactMonthPackagePrices;

                                    $upgradeDowngrade = 'Retained';
                                    if ($currentMonthPriceVariance < 0) {
                                      $upgradeDowngrade = 'Downgrade';
                                    } elseif ($currentMonthPriceVariance > 0) {
                                       $upgradeDowngrade = 'Upgrade';
                                    }

                                   // Only update non-null fields
                                   $fieldsToUpdate = [];

                                    if ($currentMonthPriceVariance !== null) {
                                      $fieldsToUpdate['current_month_price_variance'] = $currentMonthPriceVariance;
                                    }

                                    if ($upgradeDowngrade !== null) {
                                       $fieldsToUpdate['upgrade_downgrade'] = $upgradeDowngrade;
                                    }

                                    // Update the retention record if there are fields to update
                                    if (!empty($fieldsToUpdate)) {
                                       $retentionRecord->update($fieldsToUpdate);
                                    }
                                }
                            }

                            if ($retentionRecord) {
                                // Extract necessary fields from the fetched record
                                $dueThisMonth = $retentionRecord->due_this_month;
                                $billCycle = $retentionRecord->contact_bill_cycle;
                                $currentMonthPackage = $retentionRecord->current_month_package;
                                $contactMonthStatus = $retentionRecord->contact_month_status;
                                $currentMonthStatus = $retentionRecord->current_month_status;
                                $currentBillCycle = $retentionRecord->current_bill_cycle;

                                // Determine payment_status using the retrieved data
                                $paymentStatus = null;

                                // Check if currentMonthPackage is null first
                                if ($currentMonthPackage === null) {
                                    $paymentStatus = "NPD";
                                } else {
                                    // Determine payment status based on other conditions
                                    if (
                                        $dueThisMonth === "Due next Month" &&
                                        $currentMonthStatus === "Active") {
                                         $paymentStatus = "Billed";
                                    } elseif ($dueThisMonth === "") {
                                        $paymentStatus = "";
                                    } elseif ($dueThisMonth === "Due this Month") {
                                        if (
                                            $billCycle === $currentBillCycle &&
                                            $currentMonthStatus === "Active" &&
                                            $contactMonthStatus === "Active"
                                        ) {
                                            $paymentStatus = "Prepay";
                                        } elseif (
                                            $currentMonthStatus === "Active" &&
                                            $contactMonthStatus === "Active"
                                        ) {
                                            $paymentStatus = "Disconnected";
                                        } elseif (
                                            ($contactMonthStatus !== "Active" || $contactMonthStatus !== "SEA") &&
                                            $currentMonthStatus === "Active"
                                        ) {
                                            $paymentStatus = "Conversion";
                                        } elseif (
                                            $currentMonthStatus === "Active" &&
                                             $contactMonthStatus === "SEA"
                                        ) {
                                            $paymentStatus = "Unsuspension";
                                        } else {
                                            $paymentStatus = "NPD";
                                        }
                                    }
                                }

                                // Update the payment_status field in the RetentionAtFrontline record
                                $retentionRecord->update(['payment_status' => $paymentStatus]);
                            }

                        } catch (\Exception $e) {
                            Log::error('Error processing data for account: ' . $data->account_no . '. Error: ' . $e->getMessage());
                            Log::error('Stack Trace: ' . $e->getTraceAsString());
                        }
                    }
                }

                // Move start date forward by one day (outside inner loops)
                $startDate->addDay();
            }
        } catch (\Exception $e) {
            Log::error('Error in job ProcessRetentionData: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Run the retention update logic for a specific record.
     *
     * @param  \App\Models\FrontlineRetention  $retentionRecord
     * @return void
     */
    private function runRetentionUpdate($retentionRecord)
    {
        Log::info('Starting runRetentionUpdate method for account: ' . $retentionRecord->account);

        // Start a database transaction
        \DB::beginTransaction();

        try {
            // Fetch the corresponding movement data
            $movement = Movement::where('subs_account_no', $retentionRecord->account)->first();

            if ($movement) {
                try {
                    Log::info('Processing movement for account: ' . $movement->subs_account_no);

                    // Update the relevant fields
                    $retentionRecord->current_bill_cycle = $movement->subs_bill_cycle;
                    $retentionRecord->current_month_status = $this->determineStatus($movement);
                    $retentionRecord->current_month_package = $movement->subs_current_package;
                    $retentionRecord->current_month_package_price = $this->getPackagePrice($movement->subs_current_package);

                    // Save the updated retention data
                    $retentionRecord->save();
                    Log::info('Successfully updated retention record for account: ' . $movement->subs_account_no);

                } catch (\Exception $e) {
                    Log::error('Error updating retention for account: ' . $movement->subs_account_no . '. Error: ' . $e->getMessage());
                    Log::error('Stack Trace: ' . $e->getTraceAsString());
                }
            } else {
                Log::warning('No movement record found for account: ' . $retentionRecord->account);
            }

            // Commit the transaction if all updates succeed
            \DB::commit();
            Log::info('Successfully completed runRetentionUpdate method for account: ' . $retentionRecord->account);

        } catch (\Exception $e) {
            // Rollback the transaction in case of any failure
            \DB::rollBack();
            Log::error('Error in runRetentionUpdate: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Determine the status of the current month.
     *
     * @param  \App\Models\Movement  $movement
     * @return string
     */
    private function determineStatus($movement)
    {
        // If the package is null, check for statuses that should return 'NPD'
        if (
            ($movement->subs_current_status === 'Active' || $movement->subs_current_status === 'COMPLIMENTARY' || $movement->subs_current_status === 'VIP')
            && $movement->subs_current_package === null
        ) {
            return 'NPD';
        }

        // If the package is not null, check for statuses that should return 'Active'
        if (
            ($movement->subs_current_status === 'Active' || $movement->subs_current_status === 'COMPLIMENTARY' || $movement->subs_current_status === 'Staff' || $movement->subs_current_status === 'VIP' || $movement->subs_current_status === 'Office')
            && $movement->subs_current_package !== null
        ) {
            return 'Active';
        }

        // Default to returning the status as-is if no conditions are met
        return $movement->subs_current_status;
    }



    /**
     * Get the package price based on the package name.
     *
     * @param  string  $packageName
     * @return float|null
     */
    private function getPackagePrice($packageName)
    {
        // Retrieve package price based on package name
        $package = PackagePrice::where('package_name', $packageName)->first();
        return $package ? $package->package_price : null;
    }
}
