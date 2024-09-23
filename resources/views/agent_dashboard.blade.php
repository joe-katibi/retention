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
                <h4>Frontline Retention KPI Per Agent</h4>
                <form method="GET" action="{{ url('agent-dashboard') }}" class="d-flex align-items-center">
                    <!-- Team Select -->
                    <select name="agent_team" class="form-select me-2" style="min-width: 200px;">
                        <option value="">Select Team</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team }}" {{ $team == request('agent_team', 'Inbound') ? 'selected' : '' }}>
                                {{ $team }}
                            </option>
                        @endforeach
                    </select>


                    <!-- Month Select -->
                    <select name="agent_month" class="form-select me-2" style="min-width: 150px;">
                        @foreach ($months as $num => $name)
                            <option value="{{ $num }}" {{ $num == $AgentCurrentMonth ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Year Select -->
                    <select name="agent_year" class="form-select me-2">
                        @for ($year = $currentYear + 10; $year >= $currentYear - 10; $year--)
                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                    </select>

                    <!-- Submit Button -->
                    <button class="btn btn-primary me-2" type="submit">View</button>
                    <button class="btn btn-primary me-2" type="submit">Export</button>
                </form>
            </div>
            <div class="card-body">
                <table id="performanceTable" class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th rowspan="2">Agent</th>
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
                        @foreach($agentDataPaginated as $agentName => $performance)
                            <tr>
                                <td>{{ $agentName }}</td>
                                <td>{{ $performance['Paying Base Retention']->billed ?? '-' }}</td>
                                <td>{{ $performance['Paying Base Retention']->retained ?? '-' }}</td>
                                <td>{{ $performance['Paying Base Retention']->billed_retained_percentage ?? '-' }}%</td>
                                <td>{{ $performance['NPD Conversation']->npd_contacts ?? '-' }}</td>
                                <td>{{ $performance['NPD Conversation']->npd_conversations ?? '-' }}</td>
                                <td>{{ $performance['NPD Conversation']->npd_contacts_npd_conversations_percentage ?? '-' }}%</td>
                                <td>{{ $performance['Package Movement']->upgrades ?? '-' }}</td>
                                <td>{{ $performance['Package Movement']->downgrades ?? '-' }}</td>
                                <td>{{ $performance['Package Movement']->upgrades_downgrade_percentage ?? '-' }}%</td>
                                <td>{{ $performance['Sales Retention']->m1_sales ?? '-' }}</td>
                                <td>{{ $performance['Sales Retention']->M1_active ?? '-' }}</td>
                                <td>{{ $performance['Sales Retention']->M1_active_m1_sales_percentage ?? '-' }}%</td>
                            </tr>
                        @endforeach

                    </tbody>

                </table>
                <!-- Display pagination links -->
                {{ $paginatedAgents->links() }}
            </div>
        </div>
    </div>
</div>
</div>


</x-app-layout>
