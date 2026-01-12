<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reading Calendar - {{ $plan->start_date->format('M Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        
        .month-container {
            page-break-after: always;
            margin-bottom: 20px;
        }
        
        .month-container:last-child {
            page-break-after: avoid;
        }
        
        .month-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .plan-name {
            font-size: 10px;
            text-align: center;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        .calendar-table th {
            background-color: #f3f4f6;
            padding: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }
        
        .calendar-table td {
            border: 1px solid #e5e7eb;
            height: 60px;
            vertical-align: top;
            padding: 3px;
            width: 14.28%;
        }
        
        .calendar-table td.empty {
            background-color: #f9fafb;
        }
        
        .calendar-table td.has-reading {
            background-color: #eff6ff;
        }
        
        .day-number {
            font-weight: bold;
            font-size: 9px;
            color: #374151;
            text-align: right;
            margin-bottom: 2px;
        }
        
        .reading-text {
            font-size: 7px;
            color: #1e40af;
            text-align: center;
            word-wrap: break-word;
            line-height: 1.1;
        }
        
        .word-count {
            font-size: 6px;
            color: #6b7280;
            text-align: center;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    @foreach ($months as $monthKey => $month)
        <div class="month-container">
            @if($loop->first && $plan->name)
                <div class="plan-name">{{ $plan->name }}</div>
            @endif
            <h2 class="month-title">{{ $month['name'] }}</h2>
            
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                        <th>Sun</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $firstDayOfWeek = $month['first_day_of_week'];
                        $daysInMonth = $month['days_in_month'];
                        $day = 1;
                        $started = false;
                    @endphp
                    
                    @for ($week = 0; $week < 6; $week++)
                        @if ($day <= $daysInMonth)
                            <tr>
                                @for ($weekday = 1; $weekday <= 7; $weekday++)
                                    @if (!$started && $weekday < $firstDayOfWeek)
                                        <td class="empty"></td>
                                    @elseif ($day <= $daysInMonth)
                                        @php
                                            $started = true;
                                            $hasReading = isset($month['days'][$day]);
                                            $reading = $hasReading ? $month['days'][$day] : null;
                                            $currentDay = $day;
                                            $day++;
                                        @endphp
                                        <td class="{{ $hasReading ? 'has-reading' : '' }}">
                                            <div class="day-number">{{ $currentDay }}</div>
                                            @if ($hasReading)
                                                <div class="reading-text">{{ $reading['reading'] }}</div>
                                                <div class="word-count">{{ number_format($reading['word_count']) }}</div>
                                            @endif
                                        </td>
                                    @else
                                        <td class="empty"></td>
                                    @endif
                                @endfor
                            </tr>
                        @endif
                    @endfor
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
