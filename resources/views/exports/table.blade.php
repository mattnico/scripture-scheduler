<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Plan - {{ $plan->start_date->format('M j, Y') }} to {{ $plan->end_date->format('M j, Y') }}</title>
    <style>
        @media print {
            @page { margin: 0.5in; }
            .no-print { display: none !important; }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .print-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .print-btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Schedule</button>
    
    <h1>Scripture Reading Plan</h1>
    <p class="subtitle">{{ $plan->start_date->format('F j, Y') }} - {{ $plan->end_date->format('F j, Y') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>{{ $plan->scheduling_method === 'chapter' ? 'Read Through Chapter' : 'Read Through Verse' }}</th>
                <th class="text-right">Words</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedule as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('D, M j') }}</td>
                    <td>{{ $entry['reading'] }}</td>
                    <td class="text-right">{{ number_format($entry['word_count']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <p class="no-print" style="color: #666; font-size: 11px;">
        Total: {{ number_format($plan->total_words) }} words over {{ count($schedule) }} days
        (avg {{ number_format($plan->words_per_day) }} words/day)
    </p>
</body>
</html>
