<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Material Balance Report' }}</title>
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
                font-size: 12px;
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
        </style>
    </head>
    <body>
        <h1>{{ $title ?? 'Material Balance Report' }}</h1>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Material Name') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Unit of Measurement') }}</th>
                    <th class="text-right">{{ __('Head Office Balance') }}</th>
                    <th class="text-right">{{ __('On Sites Balance') }}</th>
                    <th class="text-right">{{ __('Total Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materials as $material)
                    <tr>
                        <td>{{ $material['name'] }}</td>
                        <td>{{ $material['description'] }}</td>
                        <td>{{ $material['unit_of_measure'] ?? '—' }}</td>
                        <td class="text-right">{{ $material['head_office_balance'] }}</td>
                        <td class="text-right">{{ $material['site_balance'] }}</td>
                        <td class="text-right">{{ $material['total_balance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
