<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Site Material Report' }}</title>
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
        <h1>{{ $title ?? 'Site Material Report' }}</h1>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Material Name') }}</th>
                    <th>{{ __('Unit of Measurement') }}</th>
                    <th>{{ __('Project Name') }}</th>
                    <th>{{ __('Project Code') }}</th>
                    <th class="text-right">{{ __('Assigned Count') }}</th>
                    <th class="text-right">{{ __('Distributed to Employee') }}</th>
                    <th class="text-right">{{ __('Physical Available') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materials as $material)
                    <tr>
                        <td>{{ $material['material_name'] }}</td>
                        <td>{{ $material['unit_of_measure'] }}</td>
                        <td>{{ $material['project_name'] }}</td>
                        <td>{{ $material['project_code'] }}</td>
                        <td class="text-right">{{ $material['assigned_count'] }}</td>
                        <td class="text-right">{{ $material['distributed_to_employee'] }}</td>
                        <td class="text-right">{{ $material['physical_available'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
