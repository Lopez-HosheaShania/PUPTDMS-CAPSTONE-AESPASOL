<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin: 0 0 6px; font-size: 22px; color: #7f1d1d; }
        p { margin: 0 0 4px; }
        .meta { margin-bottom: 16px; }
        .filters { margin: 12px 0 16px; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .filters strong { color: #111827; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 7px 8px; vertical-align: top; word-wrap: break-word; }
        th { background: #7f1d1d; color: #fff; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #fafafa; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>System Logs Report</h1>
    <div class="meta">
        <p><strong>Generated:</strong> {{ $generatedAt->format('F j, Y h:i A') }}</p>
        <p><strong>Total Entries:</strong> {{ $logs->count() }}</p>
    </div>

    <div class="filters">
        <p><strong>Status:</strong> {{ ucfirst($filters['status'] ?? 'active') }}</p>
        <p><strong>Role:</strong> {{ ucfirst($filters['role'] ?? 'all') }}</p>
        <p><strong>Search:</strong> {{ $filters['search'] ?: 'None' }}</p>
        <p><strong>Date Range:</strong> {{ $filters['date_from'] ?: 'Any' }} to {{ $filters['date_to'] ?: 'Any' }}</p>
        <p><strong>Action Type:</strong> {{ $filters['action_type'] ?: 'Any' }}</p>
        <p><strong>Module:</strong> {{ $filters['module'] ?: 'Any' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">ID</th>
                <th style="width: 14%;">Timestamp</th>
                <th style="width: 10%;">Role</th>
                <th style="width: 14%;">User</th>
                <th style="width: 12%;">Action</th>
                <th style="width: 12%;">Module</th>
                <th style="width: 20%;">Description</th>
                <th style="width: 12%;">Archive Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td>#{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ optional($log->created_at)->format('M j, Y h:i:s A') }}</td>
                    <td>{{ ucfirst(strtolower($log->actor_role ?? 'other')) }}</td>
                    <td>
                        {{ $log->actor_name ?? 'Unknown User' }}<br>
                        <span class="muted">{{ $log->actor_identifier ?? '—' }}</span>
                    </td>
                    <td>{{ ucwords(str_replace('_', ' ', $log->action ?? '')) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $log->module ?? '')) }}</td>
                    <td>{{ $log->full_description ?? 'No description provided.' }}</td>
                    <td>
                        @if ($log->is_archived)
                            Archived<br>
                            <span class="muted">{{ optional($log->archived_at)->format('M j, Y h:i A') }}</span>
                        @else
                            Active
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
