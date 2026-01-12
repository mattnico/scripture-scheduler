<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reading Plan - {{ $plan->start_date->format('M j, Y') }} to {{ $plan->end_date->format('M j, Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
        }
        
        h1 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 15px;
            font-size: 11px;
        }
        
        .plan-name {
            font-size: 12px;
            color: #4f46e5;
            margin-bottom: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
        }
        
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            color: #374151;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #f3f4f6;
            border-radius: 4px;
            font-size: 9px;
            color: #6b7280;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            padding: 10px;
        }
    </style>
</head>
<body>
    @if($plan->name)
        <div class="plan-name">{{ $plan->name }}</div>
    @endif
    <h1>Scripture Reading Plan</h1>
    <p class="subtitle">{{ $plan->start_date->format('F j, Y') }} - {{ $plan->end_date->format('F j, Y') }}</p>
    
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Date</th>
                <th style="width: 55%;">{{ $plan->scheduling_method === 'chapter' ? 'Read Through Chapter' : 'Read Through Verse' }}</th>
                <th style="width: 12%;" class="text-right">{{ $plan->scheduling_method === 'chapter' ? 'Chapters' : 'Verses' }}</th>
                <th style="width: 13%;" class="text-right">Words</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedule as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('D, M j, Y') }}</td>
                    <td>{{ $entry['reading'] }}</td>
                    <td class="text-right">{{ $entry['chapter_count'] ?? $entry['verse_count'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format($entry['word_count']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary">
        <strong>Summary:</strong> {{ number_format($plan->total_words) }} total words over {{ count($schedule) }} days 
        (average {{ number_format($plan->words_per_day) }} words/day)
    </div>
</body>
</html>
