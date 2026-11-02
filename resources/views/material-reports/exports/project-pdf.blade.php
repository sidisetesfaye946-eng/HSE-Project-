<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Project Material Balance' }}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #111827;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
                margin-bottom: 2rem;
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
                margin-bottom: 0.25rem;
            }

            h2 {
                font-size: 14px;
                color: #4b5563;
                margin-top: 0;
                margin-bottom: 1.5rem;
            }

            h3 {
                font-size: 14px;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 0.25rem;
            }
        </style>
    </head>
    <body>
        <h1>{{ $project->project_name }} ({{ $project->project_code }})</h1>
        <h2>{{ __('Site Officer:') }} {{ $project->siteOfficer?->name ?? '—' }}</h2>

        @foreach ($balances as $balance)
            <div>
                <h3>{{ $balance['material']->material_name }} ({{ __('Total Assigned Balance: :qty', ['qty' => $balance['total_quantity']]) }})</h3>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th class="text-right">{{ __('Quantity') }}</th>
                            <th>{{ __('Receiver') }}</th>
                            <th>{{ __('Assigned By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balance['assignments'] as $assignment)
                            <tr>
                                <td>{{ $assignment->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-right">{{ $assignment->quantity }}</td>
                                <td>{{ $assignment->receiver->name }}</td>
                                <td>{{ $assignment->assignedBy->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </body>
</html>
