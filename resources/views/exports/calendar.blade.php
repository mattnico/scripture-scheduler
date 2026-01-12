<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Calendar - {{ $plan->start_date->format('M Y') }}</title>
    <style>
        @media print {
            @page { 
                size: landscape;
                margin: 0.25in; 
            }
            .no-print { display: none !important; }
            .month-container { page-break-after: always; }
            .month-container:last-child { page-break-after: avoid; }
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
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
        
        .month-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .month-title {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e5e7eb;
            border: 1px solid #e5e7eb;
        }
        
        .day-header {
            background: #f3f4f6;
            padding: 8px;
            text-align: center;
            font-weight: 600;
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .day-cell {
            background: white;
            min-height: 80px;
            padding: 4px;
            position: relative;
        }
        
        .day-cell.empty {
            background: #fafafa;
        }
        
        .day-cell.has-reading {
            background: #eff6ff;
        }
        
        .day-number {
            font-weight: 600;
            font-size: 12px;
            color: #374151;
            position: absolute;
            top: 4px;
            right: 6px;
        }
        
        .reading-text {
            font-size: 9px;
            color: #1e40af;
            margin-top: 20px;
            text-align: center;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .word-count {
            font-size: 8px;
            color: #6b7280;
            text-align: center;
            margin-top: 2px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .month-container {
                box-shadow: none;
                border-radius: 0;
                padding: 10px;
                margin-bottom: 0;
            }
            
            .day-cell {
                min-height: 70px;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Calendar</button>
    
    @foreach ($months as $monthKey => $month)
        <div class="month-container">
            <h2 class="month-title">{{ $month['name'] }}</h2>
            
            <div class="calendar-grid">
                <div class="day-header">Mon</div>
                <div class="day-header">Tue</div>
                <div class="day-header">Wed</div>
                <div class="day-header">Thu</div>
                <div class="day-header">Fri</div>
                <div class="day-header">Sat</div>
                <div class="day-header">Sun</div>
                
                @php
                    $firstDayOfWeek = $month['first_day_of_week'];
                    $daysInMonth = $month['days_in_month'];
                @endphp
                
                @for ($i = 1; $i < $firstDayOfWeek; $i++)
                    <div class="day-cell empty"></div>
                @endfor
                
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $hasReading = isset($month['days'][$day]);
                        $reading = $hasReading ? $month['days'][$day] : null;
                    @endphp
                    <div class="day-cell {{ $hasReading ? 'has-reading' : '' }}">
                        <span class="day-number">{{ $day }}</span>
                        @if ($hasReading)
                            <div class="reading-text">{{ $reading['reading'] }}</div>
                            <div class="word-count">{{ number_format($reading['word_count']) }} words</div>
                        @endif
                    </div>
                @endfor
                
                @php
                    $lastDayOfWeek = \Carbon\Carbon::create($month['year'], $month['month'], $daysInMonth)->dayOfWeekIso;
                @endphp
                
                @for ($i = $lastDayOfWeek + 1; $i <= 7; $i++)
                    <div class="day-cell empty"></div>
                @endfor
            </div>
        </div>
    @endforeach
</body>
</html>
