<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Employee Assignment History Report' }}</title>
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
        </style>
    </head>
    <body>
        <h1>{{ $title ?? 'Employee Assignment History Report' }}</h1>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Project Code') }}</th>
                    <th>{{ __('Project Name') }}</th>
                    <th>{{ __('Material') }}</th>
                    <th class="text-right">{{ __('Quantity') }}</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th>{{ __('Employee Job Title') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->assigned_date->format('Y-m-d') }}</td>
                        <td>{{ $assignment->project->project_code }}</td>
                        <td>{{ $assignment->project->project_name }}</td>
                        <td>{{ $assignment->material->material_name }}</td>
                        <td class="text-right">{{ $assignment->quantity }}</td>
                        <td>{{ $assignment->employee ? ($assignment->employee->first_name . ' ' . $assignment->employee->last_name) : __('General Site Usage') }}</td>
                        <td>{{ $assignment->employee?->job_title ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
