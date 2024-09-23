<?php

namespace App\Http\Controllers;

use App\Models\CRM;
use App\Models\packagePrice;
use Illuminate\Http\Request;
use App\Models\FrontlineRetention;
use App\Models\movement;
use App\Models\TeamPerformance;
use App\Models\AgentPerformance;
use Illuminate\Support\Carbon;
use App\Jobs\ProcessRetentionData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FrontlineRetentionController extends Controller
{
    public function index()
    {
        try {
           // Dispatch the job to run in the background
           ProcessRetentionData::dispatch();

           return response()->json(['message' => 'Historical data processing started'], 200);
        } catch (\Exception $e) {
           \Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to start data processing'], 500);
        }
    }

    public function team()
    {
        // Fetch all unique months from FrontlineRetention
        $months = FrontlineRetention::select('month_of_contact')
                    ->distinct()
                    ->pluck('month_of_contact');

        foreach ($months as $month) {
            // Fetch the channel for the current month
            $channels = FrontlineRetention::where('month_of_contact', $month)
                            ->distinct()
                            ->pluck('channel');

            foreach ($channels as $team) {
                // Fetching data for the team
                $inboundTeamBilled = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->count();

                $inboundTeamRetained = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->where('contact_month_status', 'Active')
                    ->where('current_month_status', 'Active')
                    ->count();

                $inboundTeamPercentage = $inboundTeamBilled > 0 ? $inboundTeamRetained / $inboundTeamBilled * 100 : 0;

                TeamPerformance::create([
                    'team' => $team,
                    'kpi' => 'Paying Base Retention',
                    'billed' => $inboundTeamBilled,
                    'retained' => $inboundTeamRetained,
                    'billed_retained_percentage' => $inboundTeamPercentage,
                    'month_performance' => $month,
                ]);

                $inboundTeamTotalNpd = FrontlineRetention::where('channel', $team)
                    ->where('contact_month_status', '!=', 'Active')
                    ->where('month_of_contact', $month)
                    ->count();

                $inboundTeamTotalNpdConversation = FrontlineRetention::where('channel', $team)
                    ->where('contact_month_status', '!=', 'Active')
                    ->where('month_of_contact', $month)
                    ->where('current_month_status', 'Active')
                    ->count();

                $inboundConversation = $inboundTeamTotalNpd > 0 ? $inboundTeamTotalNpdConversation / $inboundTeamTotalNpd * 100 : 0;

                TeamPerformance::create([
                    'kpi' => 'NPD Conversation',
                    'npd_contacts' => $inboundTeamTotalNpd,
                    'npd_conversations' => $inboundTeamTotalNpdConversation,
                    'npd_contacts_npd_conversations_percentage' => $inboundConversation,
                    'team' => $team,
                    'month_performance' => $month,
                ]);

                $inboundTeamUpgrades = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->where('contact_month_status', 'Active')
                    ->where('current_month_status', 'Active')
                    ->where('upgrade_downgrade', 'Upgrade')
                    ->count();

                $inboundTeamDowngrades = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->where('contact_month_status', 'Active')
                    ->where('current_month_status', 'Active')
                    ->where('upgrade_downgrade', 'Downgrade')
                    ->count();

                $inboundTeamPackageMovement = ($inboundTeamUpgrades + $inboundTeamDowngrades) > 0
                    ? $inboundTeamUpgrades / ($inboundTeamUpgrades + $inboundTeamDowngrades) * 100
                    : 0;

                TeamPerformance::create([
                    'team' => $team,
                    'month_performance' => $month,
                    'kpi' => 'Package Movement',
                    'upgrades' => $inboundTeamUpgrades,
                    'downgrades' => $inboundTeamDowngrades,
                    'upgrades_downgrade_percentage' => $inboundTeamPackageMovement,
                ]);

                $inboundTeamM1Sales = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->where('contact_month_status', 'Active')
                    ->where('current_month_status', 'Active')
                    ->where('sales_retention', 'M0 Account')
                    ->count();

                $inboundTeamM1Active = FrontlineRetention::where('channel', $team)
                    ->where('month_of_contact', $month)
                    ->where('contact_month_status', 'Active')
                    ->where('current_month_status', 'Active')
                    ->where('sales_retention', 'M1 Retained')
                    ->count();

                TeamPerformance::create([
                    'team' => $team,
                    'month_performance' => $month,
                    'kpi' => 'Sales Retention',
                    'm1_sales' => $inboundTeamM1Sales,
                    'M1_active' => $inboundTeamM1Active,
                    'M1_active_m1_sales_percentage' => $inboundTeamM1Sales > 0 ? $inboundTeamM1Active / $inboundTeamM1Sales * 100 : 0,
                ]);
            }
        }


    }
    public function agent()
{
    // Fetch all relevant data in one query
    $data = FrontlineRetention::select('channel', 'month_of_contact', 'agent_name', 'contact_month_status', 'current_month_status', 'upgrade_downgrade', 'sales_retention')
        ->get()
        ->groupBy('month_of_contact');

    foreach ($data as $month => $monthData) {
        $agents = $monthData->groupBy('agent_name');

        foreach ($agents as $agent => $agentData) {
            // Extract channel for the agent
            $channel = $agentData->first()->channel;

            // Calculate KPIs for the agent
            $billed = $agentData->count();
            $retained = $agentData->filter(function ($item) {
                return $item->contact_month_status == 'Active' && $item->current_month_status == 'Active';
            })->count();
            $billedRetainedPercentage = $billed > 0 ? ($retained / $billed) * 100 : 0;

            // Create or update Paying Base Retention
            AgentPerformance::updateOrCreate([
                'agent_name' => $agent,
                'month_performance' => $month,
                'kpi' => 'Paying Base Retention',
                'team' => $channel,
            ], [
                'billed' => $billed,
                'retained' => $retained,
                'billed_retained_percentage' => $billedRetainedPercentage,
            ]);

            $npdContacts = $agentData->filter(function ($item) {
                return $item->contact_month_status != 'Active';
            })->count();
            $npdConversions = $agentData->filter(function ($item) {
                return $item->contact_month_status != 'Active' && $item->current_month_status == 'Active';
            })->count();
            $npdConversionsPercentage = $npdContacts > 0 ? ($npdConversions / $npdContacts) * 100 : 0;

            // Create or update NPD Conversation
            AgentPerformance::updateOrCreate([
                'agent_name' => $agent,
                'month_performance' => $month,
                'kpi' => 'NPD Conversation',
                'team' => $channel,
            ], [
                'npd_contacts' => $npdContacts,
                'npd_conversations' => $npdConversions,
                'npd_contacts_npd_conversations_percentage' => $npdConversionsPercentage,
            ]);

            $upgrades = $agentData->filter(function ($item) {
                return $item->upgrade_downgrade == 'Upgrade';
            })->count();
            $downgrades = $agentData->filter(function ($item) {
                return $item->upgrade_downgrade == 'Downgrade';
            })->count();
            $packageMovementPercentage = ($upgrades + $downgrades) > 0 ? ($upgrades / ($upgrades + $downgrades)) * 100 : 0;

            // Create or update Package Movement
            AgentPerformance::updateOrCreate([
                'agent_name' => $agent,
                'month_performance' => $month,
                'kpi' => 'Package Movement',
                'team' => $channel,
            ], [
                'upgrades' => $upgrades,
                'downgrades' => $downgrades,
                'upgrades_downgrade_percentage' => $packageMovementPercentage,
            ]);

            $m1Sales = $agentData->filter(function ($item) {
                return $item->sales_retention == 'M0 Account';
            })->count();
            $m1Active = $agentData->filter(function ($item) {
                return $item->sales_retention == 'M1 Retained';
            })->count();
            $m1ActiveSalesPercentage = $m1Sales > 0 ? ($m1Active / $m1Sales) * 100 : 0;

            // Create or update Sales Retention
            AgentPerformance::updateOrCreate([
                'agent_name' => $agent,
                'month_performance' => $month,
                'kpi' => 'Sales Retention',
                'team' => $channel,
            ], [
                'm1_sales' => $m1Sales,
                'M1_active' => $m1Active,
                'M1_active_m1_sales_percentage' => $m1ActiveSalesPercentage,
            ]);
        }
    }

    return view('dashboard');
}

    public function TeamPerformance(Request $request){

                // Default values
                $currentMonth = date('m');
                $currentYear = date('Y');

                $input = $request->all();

                //get Team performance
                $month = $request->input('month',$currentMonth);
                $year = $request->input('year', $currentYear);
                $monthName = date('F', mktime(0, 0, 0, $month, 10));

        //to correct based on Channels from CRM
        $teams = ['Inbound', 'Digital', 'Shops', 'Billing Team', 'Service Support'];

        // Fetch team performance data
        $performances = TeamPerformance::where('month_performance', $monthName)
                                       ->whereYear('created_at', $year)
                                       ->get()
                                       ->groupBy('team');

        // Log the retrieved performances
        \Log::info('Performances:', $performances->mapWithKeys(function ($items, $team) {
            return [$team => $items->toArray()];
        })->toArray());

        // Prepare data for each team and KPI
        $teamData = [];
        foreach ($teams as $team) {
            $performanceItems = $performances->get($team, collect());

            $teamData[$team] = [
                'Paying Base Retention' => $performanceItems->where('kpi', 'Paying Base Retention')->first(),
                'NPD Conversation' => $performanceItems->where('kpi', 'NPD Conversation')->first(),
                'Package Movement' => $performanceItems->where('kpi', 'Package Movement')->first(),
                'Sales Retention' => $performanceItems->where('kpi', 'Sales Retention')->first(),
            ];
        }


        // Log the team data
        \Log::info('Team Data:', $teamData);

         return view('dashboard', [
           'months' => [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
           ],
          'currentMonth' => $month,
          'currentYear' => $year,
           'teamData' => $teamData,

        ]);


    }


    public function summaryDashboard(Request $request)
    {

        $input = $request->all();

        // Default values
        $currentMonth = date('m');
        $currentYear = date('Y');

        //get agent performance
        $agentTeam = $request->input('agent_team');
        $agentMonth = $request->input('agent_month');
        $agentYear = $request->input('agent_year', $currentYear);
        $AgentMonthName = date('F', mktime(0, 0, 0, $agentMonth, 10));

        // Fetch distinct agent names by team and paginate
        $perPage = 15; // Adjust as necessary
        $page = request()->input('page', 1); // Current page

        // Fetch distinct agents by team
        $agentsQuery = AgentPerformance::select('agent_name')
                ->where('team', $agentTeam) // Filter by team
                ->distinct();
        $paginatedAgents = $agentsQuery->paginate($perPage, ['*'], 'page', $page);

         // Fetch agent performance data without pagination for now
        $performancesKpi = AgentPerformance::where('month_performance', $AgentMonthName)
                                   ->whereYear('created_at', $agentYear)
                                   ->where('team', $agentTeam) // Filter by team
                                   ->get()
                                   ->groupBy('agent_name'); // Group by agent_name only

        $agentData = [];

        // Loop through each paginated agent and get their performance KPIs
        foreach ($paginatedAgents as $agent) {
            $performanceAgent = $performancesKpi->get($agent->agent_name, collect());

            $agentData[$agent->agent_name] = [
                'Paying Base Retention' => $performanceAgent->where('kpi', 'Paying Base Retention')->first(),
                'NPD Conversation' => $performanceAgent->where('kpi', 'NPD Conversation')->first(),
                'Package Movement' => $performanceAgent->where('kpi', 'Package Movement')->first(),
                'Sales Retention' => $performanceAgent->where('kpi', 'Sales Retention')->first(),
            ];
        }

        // Convert agentData to a collection so we can paginate it manually
        $agentDataCollection = collect($agentData);

        // Manually paginate the agent data collection
        $agentDataPaginated = new LengthAwarePaginator(
              $agentDataCollection->forPage($page, $perPage), // Items for current page
              $agentDataCollection->count(), // Total number of items
              $perPage, // Items per page
              $page, // Current page
              ['path' => request()->url(), 'query' => request()->query()] // Keeps URL query parameters
        );

           // Log agent data for debugging
        \Log::info('Agent Data:', $agentData);


        return view('agent_dashboard', [
            'months' => [
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            ],
            'AgentCurrentMonth' => $agentMonth,
            'currentYear' => $agentYear,
            'agentData' => $agentData,
            'agentDataPaginated'=>$agentDataPaginated,
            'paginatedAgents'=>$paginatedAgents
        ]);
    }


}

