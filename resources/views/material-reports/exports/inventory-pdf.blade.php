<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Head Office Material Inventory Report' }}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #111827;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }

            th, td {
                border: 1px solid #d1d5db;
                padding: 0.5rem;
                text-align: left;
                font-size: 11px;
            }

            th {
                background-color: #f3f4f6;
                font-weight: 700;
            }

            .text-right {
                text-align: right;
            }

            h1 {
                font-size: 18px;
                margin-bottom: 0.5rem;
            }

            .summary-box {
                background-color: #f9fafb;
                border: 1px solid #e5e7eb;
                padding: 1rem;
                margin-bottom: 1rem;
                font-size: 12px;
            }

            .summary-box table {
                margin-top: 0;
            }

            .summary-box td {
                border: none;
                padding: 0.25rem 0.5rem;
            }
        </style>
    </head>
    <body>
        <h1>{{ $title ?? 'Head Office Material Inventory Report' }}</h1>
        
        <div class="summary-box">
            <h3>{{ __('Inventory Summary') }}</h3>
            <table>
                <tr>
                    <td><strong>{{ __('Total Materials:') }}</strong> {{ $summary['total_materials'] }}</td>
                    <td><strong>{{ __('Total Stocked Quantity:') }}</strong> {{ number_format($summary['total_stocked_quantity']) }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('Total HO Available:') }}</strong> {{ number_format($summary['total_head_office_available']) }}</td>
                    <td><strong>{{ __('Total Assigned to Projects:') }}</strong> {{ number_format($summary['total_assigned_to_projects']) }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('Total Assigned to Employees:') }}</strong> {{ number_format($summary['total_assigned_to_employees']) }}</td>
                    <td><strong>{{ __('Total Site Remaining:') }}</strong> {{ number_format($summary['total_site_remaining']) }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('Total in System:') }}</strong> {{ number_format($summary['total_in_system']) }}</td>
                    <td></td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('Material') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Unit of Measure') }}</th>
                    <th class="text-right">{{ __('Opening Stock') }}</th>
                    <th class="text-right">{{ __('Physical Balance') }}</th>
                    <th class="text-right">{{ __('Assigned to Sites') }}</th>
                    <th class="text-right">{{ __('Available Balance') }}</th>
                    <th class="text-right">{{ __('Total in System') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materials as $material)
                    <tr>
                        <td>{{ $material['name'] }}</td>
                        <td>{{ $material['description'] }}</td>
                        <td>{{ $material['unit_of_measure'] ?? '—' }}</td>
                        <td class="text-right">{{ number_format($material['opening_stock']) }}</td>
                        <td class="text-right">{{ number_format($material['physical_balance'] ?? 0) }}</td>
                        <td class="text-right">{{ number_format($material['assigned_to_sites']) }}</td>
                        <td class="text-right">{{ number_format($material['site_remaining']) }}</td>
                        <td class="text-right">{{ number_format($material['total_in_system']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
