<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report - {{ ucfirst($type) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        h1 {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #64748b;
            font-size: 14px;
        }
        .meta-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 5px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #3b82f6;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .summary-card {
            padding: 15px;
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 5px;
        }
        .summary-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report</h1>
        <div class="subtitle">{{ ucfirst($type) }} Report</div>
    </div>

    <div class="meta-info">
        <div class="meta-row">
            <span class="label">Generated:</span>
            <span>{{ $generated_at }}</span>
        </div>
        @if(isset($data['period']))
        <div class="meta-row">
            <span class="label">Period:</span>
            <span>{{ $data['period']['start_date'] ?? '' }} to {{ $data['period']['end_date'] ?? '' }}</span>
        </div>
        @endif
    </div>

    @if($type === 'summary')
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Sales</div>
            <div class="summary-value">{{ $data['total_sales'] ?? 0 }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Revenue</div>
            <div class="summary-value">{{ number_format($data['total_revenue'] ?? 0, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Average Transaction</div>
            <div class="summary-value">{{ number_format($data['average_transaction_value'] ?? 0, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Items Sold</div>
            <div class="summary-value">{{ $data['total_items_sold'] ?? 0 }}</div>
        </div>
    </div>
    @endif

    @if($type === 'daily' && isset($data['hourly_breakdown']))
    <table>
        <thead>
            <tr>
                <th>Hour</th>
                <th>Sales Count</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['hourly_breakdown'] as $hour)
            <tr>
                <td>{{ $hour['hour'] ?? '' }}</td>
                <td>{{ $hour['sales_count'] ?? 0 }}</td>
                <td>{{ number_format($hour['revenue'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(($type === 'weekly' || $type === 'monthly') && isset($data['daily_breakdown']))
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sales Count</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['daily_breakdown'] as $day)
            <tr>
                <td>{{ $day['date'] ?? '' }}</td>
                <td>{{ $day['sales_count'] ?? 0 }}</td>
                <td>{{ number_format($day['revenue'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Generated with TiwineBiz Backend</p>
        <p>© {{ date('Y') }} - All rights reserved</p>
    </div>
</body>
</html>
