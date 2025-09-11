<!DOCTYPE html>
<html>
<head>
    <title>Audit Logs</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 1rem; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
        code { font-size: 12px; }
    </style>
</head>
<body>
<h1>Audit Logs</h1>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Time</th>
        <th>Log</th>
        <th>Description</th>
        <th>Event</th>
        <th>Causer</th>
        <th>Subject</th>
        <th>Props</th>
    </tr>
    </thead>
    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{{ $log['id'] }}</td>
            <td>{{ $log['time'] }}</td>
            <td>{{ $log['log'] }}</td>
            <td>{{ $log['description'] }}</td>
            <td>{{ $log['event'] }}</td>
            <td>{{ $log['causer_type'] }}#{{ $log['causer_id'] }}</td>
            <td>{{ $log['subject_type'] }}#{{ $log['subject_id'] }}</td>
            <td><code>{{ json_encode($log['properties']) }}</code></td>
        </tr>
    @endforeach
    </tbody>
</table>

{{ $logs->links() }}
</body>
</html>