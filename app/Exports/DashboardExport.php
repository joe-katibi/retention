<?php


namespace App\Exports;

use App\Models\TeamPerformance;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DashboardExport implements FromView
{
    public function view(): View
    {
        // Fetch the data based on your requirements
        $performances = TeamPerformance::all(); // Adjust this query as needed

        return view('exports.dashboard', [
            'performances' => $performances,
        ]);
    }
}
