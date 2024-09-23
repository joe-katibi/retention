<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
    $months = [
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
    ];

    $teams = ['Inbound', 'Digital', 'Shops', 'Billing Team', 'Service Support'];
    @endphp

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="card m-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Frontline Retention KPI</h4>
                        <form method="GET" action="{{ url('dashboard') }}" class="d-flex align-items-center">
                            <!-- Month Select -->
                            <select name="month" class="form-select me-2" style="min-width: 150px;">
                                @foreach ($months as $num => $name)
                                    <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Year Select -->
                            <select name="year" class="form-select me-2">
                                @for ($year = $currentYear + 10; $year >= $currentYear - 10; $year--)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                            <!-- Submit Button -->
                            <button class="btn btn-primary me-2" type="submit">View</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th rowspan="2">Team</th>
                                    <th colspan="3">Paying Base Retention</th>
                                    <th colspan="3">NPD CONVERSIONS</th>
                                    <th colspan="3">Package Movement</th>
                                    <th colspan="3">SALES RETENTION</th>
                                </tr>
                                <tr>
                                    <th>BILLED</th>
                                    <th>RETAINED</th>
                                    <th>%</th>
                                    <th>NPD CONTACTS</th>
                                    <th>NPD CONVERSIONS</th>
                                    <th>%</th>
                                    <th>UPGRADES</th>
                                    <th>DOWNGRADES</th>
                                    <th>%</th>
                                    <th>M1 SALES</th>
                                    <th>M1 ACTIVE</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teams as $team)
                                    @php
                                        $currentTeamData = $teamData[$team] ?? null;
                                    @endphp
                                    @if ($currentTeamData)
                                        <tr>
                                            <td>{{ $team }}</td>
                                            <td>{{ $currentTeamData['Paying Base Retention']->billed ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Paying Base Retention']->retained ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Paying Base Retention']->billed_retained_percentage ?? '-' }}%</td>
                                            <td>{{ $currentTeamData['NPD Conversation']->npd_contacts ?? '-' }}</td>
                                            <td>{{ $currentTeamData['NPD Conversation']->npd_conversations ?? '-' }}</td>
                                            <td>{{ $currentTeamData['NPD Conversation']->npd_contacts_npd_conversations_percentage ?? '-' }}%</td>
                                            <td>{{ $currentTeamData['Package Movement']->upgrades ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Package Movement']->downgrades ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Package Movement']->upgrades_downgrade_percentage ?? '-' }}%</td>
                                            <td>{{ $currentTeamData['Sales Retention']->m1_sales ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Sales Retention']->M1_active ?? '-' }}</td>
                                            <td>{{ $currentTeamData['Sales Retention']->M1_active_m1_sales_percentage ?? '-' }}%</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="13">{{ $team }} has no data available.</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- New Export Form -->
                <div class="m-4">
                    <form method="GET" action="{{ url('export-dashboard') }}" class="d-flex align-items-center">
                        <!-- Month Select -->
                        <select name="month" class="form-select me-2" style="min-width: 150px;">
                            @foreach ($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Year Select -->
                        <select name="year" class="form-select me-2">
                            @for ($year = $currentYear + 10; $year >= $currentYear - 10; $year--)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>

                        <!-- Export Button -->
                        <button class="btn btn-primary me-2" type="submit">Export</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
