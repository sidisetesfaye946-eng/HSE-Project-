<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Employees Report' }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #0b1e2d;
        }
        p.subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 0;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #888;
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Employees Report' }}</h1>
    <p class="subtitle">Generated on {{ date('M d, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee Name</th>
                <th>Title</th>
                <th>Projects (Assigned)</th>
                <th>Material</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->fullName() }}</td>
                    <td>{{ $employee->job_title ?: '-' }}</td>
                    <td>{{ $employee->project_name_display ?: '-' }}</td>
                    <td>{{ $employee->material_type_display ?: '-' }}</td>
                    <td>{{ $employee->date ? \Carbon\Carbon::parse($employee->date)->format('M d, Y') : $employee->created_at->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No employees found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Ethiopian Engineering Corporation - HSE Management System
    </div>
</body>
</html>
