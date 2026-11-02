<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Material History Report' }}</title>
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

            h1 {
                font-size: 18px;
                margin-bottom: 0.5rem;
            }
        </style>
    </head>
    <body>
        <h1>{{ $title ?? 'Material History Report' }}</h1>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Material') }}</th>
                    <th>{{ __('Event') }}</th>
                    <th>{{ __('Balance Before') }}</th>
                    <th>{{ __('Quantity Change') }}</th>
                    <th>{{ __('Balance After') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Recorded By') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($histories as $history)
                    <tr>
                        <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $history->material?->material_name ?? __('Unknown material') }}</td>
                        <td>{{ Illuminate\Support\Str::headline($history->event_type) }}</td>
                        <td>{{ $history->balance_before }}</td>
                        <td>{{ $history->quantity_change }}</td>
                        <td>{{ $history->balance_after }}</td>
                        <td>{{ $history->description }}</td>
                        <td>{{ $history->createdBy?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
