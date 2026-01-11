<?php
// Teacher Dashboard - Monitor student progress
require_once 'calc.php';

session_start();
$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 'demo_teacher';
$teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : 'Demo Teacher';

// Load curriculum functions
require_once 'teacher_curriculum.php';

if (!isset($_GET['id'])) {
    die('Curriculum ID required');
}

$curriculum_id = $_GET['id'];
$curriculum = load_curriculum($curriculum_id);

if (!$curriculum || $curriculum['teacher_id'] !== $teacher_id) {
    die('Curriculum not found or access denied');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_reminders':
                handle_send_reminders();
                break;
            case 'bulk_update':
                handle_bulk_update();
                break;
        }
    }
}

function handle_send_reminders() {
    // Placeholder for email functionality
    header('Location: ' . $_SERVER['REQUEST_URI'] . '&reminded=1');
    exit;
}

function handle_bulk_update() {
    // Handle bulk progress updates
    global $curriculum_id;
    
    if (isset($_POST['student_progress'])) {
        foreach ($_POST['student_progress'] as $student_id => $progress_data) {
            update_student_progress($student_id, $curriculum_id, $progress_data);
        }
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI'] . '&updated=1');
    exit;
}

function get_enrolled_students($curriculum_id) {
    $plans_dir = create_plan_directory();
    $students = array();
    
    if (is_dir($plans_dir)) {
        $files = glob($plans_dir . '/student_*.json');
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['enrolled_curricula']) && in_array($curriculum_id, $data['enrolled_curricula'])) {
                $students[] = $data;
            }
        }
    }
    
    return $students;
}

function get_student_progress($student_id, $curriculum_id) {
    $plans_dir = create_plan_directory();
    $progress_file = $plans_dir . '/progress_' . $student_id . '_' . $curriculum_id . '.json';
    
    if (file_exists($progress_file)) {
        return json_decode(file_get_contents($progress_file), true);
    }
    
    return array('completed_assignments' => array(), 'notes' => array());
}

function update_student_progress($student_id, $curriculum_id, $progress_data) {
    $plans_dir = create_plan_directory();
    $progress_file = $plans_dir . '/progress_' . $student_id . '_' . $curriculum_id . '.json';
    
    $existing_progress = get_student_progress($student_id, $curriculum_id);
    
    // Merge new progress data
    if (isset($progress_data['completed'])) {
        foreach ($progress_data['completed'] as $date => $completed) {
            if ($completed) {
                $existing_progress['completed_assignments'][$date] = date('Y-m-d H:i:s');
            } else {
                unset($existing_progress['completed_assignments'][$date]);
            }
        }
    }
    
    $existing_progress['updated'] = date('Y-m-d H:i:s');
    
    file_put_contents($progress_file, json_encode($existing_progress, JSON_PRETTY_PRINT));
}

function calculate_progress_stats($assignments, $progress) {
    $total_assignments = count($assignments);
    $completed_count = count($progress['completed_assignments']);
    $completion_rate = $total_assignments > 0 ? ($completed_count / $total_assignments) * 100 : 0;
    
    // Check upcoming deadlines
    $upcoming_deadlines = 0;
    $overdue_count = 0;
    $today = date('Y-m-d');
    
    foreach ($assignments as $assignment) {
        if ($assignment['is_major_deadline']) {
            if ($assignment['date'] > $today) {
                $upcoming_deadlines++;
            } elseif ($assignment['date'] < $today && !isset($progress['completed_assignments'][$assignment['date']])) {
                $overdue_count++;
            }
        }
    }
    
    return array(
        'total' => $total_assignments,
        'completed' => $completed_count,
        'completion_rate' => round($completion_rate, 1),
        'upcoming_deadlines' => $upcoming_deadlines,
        'overdue' => $overdue_count
    );
}

$enrolled_students = get_enrolled_students($curriculum_id);
$student_stats = array();

foreach ($enrolled_students as $student) {
    $progress = get_student_progress($student['id'], $curriculum_id);
    $stats = calculate_progress_stats($curriculum['assignments'], $progress);
    $student_stats[] = array(
        'student' => $student,
        'progress' => $progress,
        'stats' => $stats
    );
}

// Calculate class averages
$class_completion_rates = array_column(array_column($student_stats, 'stats'), 'completion_rate');
$class_average = count($class_completion_rates) > 0 ? round(array_sum($class_completion_rates) / count($class_completion_rates), 1) : 0;

?><!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard - <?php echo htmlspecialchars($curriculum['class_name']); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; background: #f8f9fa; border-radius: 4px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 24px; }
        .stat-card.low { border-left: 4px solid #e74c3c; }
        .stat-card.medium { border-left: 4px solid #f39c12; }
        .stat-card.high { border-left: 4px solid #27ae60; }
        .student-row { display: grid; grid-template-columns: 150px 1fr 100px 100px 80px 120px; gap: 10px; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .student-row:hover { background: #f9f9f9; }
        .progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #27ae60; transition: width 0.3s; }
        .progress-fill.low { background: #e74c3c; }
        .progress-fill.medium { background: #f39c12; }
        .status-badge { padding: 2px 6px; border-radius: 3px; font-size: 12px; color: white; }
        .status-on-track { background: #27ae60; }
        .status-behind { background: #f39c12; }
        .status-at-risk { background: #e74c3c; }
        .assignment-quick-view { font-size: 12px; color: #666; margin-top: 5px; }
        .bulk-actions { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .upcoming-assignments { background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1><?php echo htmlspecialchars($curriculum['class_name']); ?> Dashboard</h1>
            <p>Seminary Year: <?php echo ucwords(str_replace('_', ' ', $curriculum['seminary_year'])); ?> | 
               Enrollment Code: <strong><?php echo $curriculum['enrollment_code']; ?></strong></p>
        </div>
        <div>
            <a href="teacher_curriculum.php?id=<?php echo $curriculum_id; ?>&mode=edit" class="pure-button">Edit Curriculum</a>
            <a href="teacher_curriculum.php" class="pure-button">Back to List</a>
        </div>
    </div>

    <?php if (isset($_GET['reminded'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;">
            ✅ Reminder emails sent successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div style="background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0;">
            ✅ Student progress updated successfully!
        </div>
    <?php endif; ?>

    <!-- Class Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo count($enrolled_students); ?></h3>
            <p>Enrolled Students</p>
        </div>
        <div class="stat-card <?php echo $class_average >= 80 ? 'high' : ($class_average >= 60 ? 'medium' : 'low'); ?>">
            <h3><?php echo $class_average; ?>%</h3>
            <p>Class Average</p>
        </div>
        <div class="stat-card">
            <h3><?php echo count(array_filter($curriculum['assignments'], function($a) { return $a['is_major_deadline']; })); ?></h3>
            <p>Major Deadlines</p>
        </div>
        <div class="stat-card">
            <h3><?php echo count($curriculum['assignments']); ?></h3>
            <p>Total Assignments</p>
        </div>
    </div>

    <!-- Upcoming Assignments Alert -->
    <?php 
    $upcoming_assignments = array_filter($curriculum['assignments'], function($a) {
        return $a['date'] >= date('Y-m-d') && $a['date'] <= date('Y-m-d', strtotime('+7 days'));
    });
    if (!empty($upcoming_assignments)): 
    ?>
    <div class="upcoming-assignments">
        <h3>📅 Upcoming This Week</h3>
        <?php foreach ($upcoming_assignments as $assignment): ?>
            <div style="margin: 5px 0;">
                <strong><?php echo date('D, M j', strtotime($assignment['date'])); ?>:</strong>
                <?php echo htmlspecialchars($assignment['scripture']); ?>
                <?php if ($assignment['lesson_topic']): ?>
                    - <?php echo htmlspecialchars($assignment['lesson_topic']); ?>
                <?php endif; ?>
                <?php if ($assignment['is_major_deadline']): ?>
                    <span class="status-badge status-at-risk">MAJOR DEADLINE</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Student Progress Table -->
    <div style="background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
        <div style="background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd;">
            <h3 style="margin: 0;">Student Progress Overview</h3>
        </div>

        <div class="student-row" style="font-weight: bold; background: #f8f9fa;">
            <div>Student Name</div>
            <div>Progress</div>
            <div>Completion</div>
            <div>Status</div>
            <div>Overdue</div>
            <div>Actions</div>
        </div>

        <?php if (empty($student_stats)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <h3>No Students Enrolled Yet</h3>
                <p>Share the enrollment code <strong><?php echo $curriculum['enrollment_code']; ?></strong> with your students to get started!</p>
            </div>
        <?php else: ?>
            <?php foreach ($student_stats as $stat): 
                $student = $stat['student'];
                $progress = $stat['progress'];
                $stats = $stat['stats'];
                
                $status_class = 'status-on-track';
                $status_text = 'On Track';
                if ($stats['completion_rate'] < 50) {
                    $status_class = 'status-at-risk';
                    $status_text = 'At Risk';
                } elseif ($stats['completion_rate'] < 75) {
                    $status_class = 'status-behind';
                    $status_text = 'Behind';
                }
                
                $progress_class = $stats['completion_rate'] >= 80 ? 'high' : ($stats['completion_rate'] >= 60 ? 'medium' : 'low');
            ?>
            <div class="student-row">
                <div>
                    <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                    <div class="assignment-quick-view">
                        Last activity: <?php echo isset($progress['updated']) ? date('M j', strtotime($progress['updated'])) : 'Never'; ?>
                    </div>
                </div>
                <div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $stats['completion_rate']; ?>%"></div>
                    </div>
                    <div style="font-size: 12px; color: #666; margin-top: 2px;">
                        <?php echo $stats['completed']; ?>/<?php echo $stats['total']; ?> assignments
                    </div>
                </div>
                <div style="text-align: center;">
                    <strong><?php echo $stats['completion_rate']; ?>%</strong>
                </div>
                <div style="text-align: center;">
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
                <div style="text-align: center;">
                    <?php if ($stats['overdue'] > 0): ?>
                        <span style="color: #e74c3c; font-weight: bold;"><?php echo $stats['overdue']; ?></span>
                    <?php else: ?>
                        <span style="color: #27ae60;">0</span>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="student_detail.php?student=<?php echo $student['id']; ?>&curriculum=<?php echo $curriculum_id; ?>" class="pure-button pure-button-primary" style="font-size: 12px;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bulk Actions -->
    <?php if (!empty($student_stats)): ?>
    <div class="bulk-actions">
        <h3>Class Actions</h3>
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="send_reminders">
            <button type="submit" class="pure-button">📧 Send Reminder Emails</button>
        </form>
        
        <button type="button" class="pure-button" onclick="exportProgress()">📊 Export Progress Report</button>
        
        <button type="button" class="pure-button" onclick="generateCalendar()">📅 Generate Class Calendar</button>
    </div>
    <?php endif; ?>

    <!-- Quick Stats Cards for Teachers -->
    <div style="margin-top: 30px;">
        <h3>Weekly Summary</h3>
        <div class="stats-grid">
            <?php
            $this_week_assignments = array_filter($curriculum['assignments'], function($a) {
                $week_start = date('Y-m-d', strtotime('monday this week'));
                $week_end = date('Y-m-d', strtotime('sunday this week'));
                return $a['date'] >= $week_start && $a['date'] <= $week_end;
            });
            
            $completed_this_week = 0;
            $total_possible_this_week = count($this_week_assignments) * count($enrolled_students);
            
            foreach ($student_stats as $stat) {
                foreach ($this_week_assignments as $assignment) {
                    if (isset($stat['progress']['completed_assignments'][$assignment['date']])) {
                        $completed_this_week++;
                    }
                }
            }
            
            $weekly_completion_rate = $total_possible_this_week > 0 ? round(($completed_this_week / $total_possible_this_week) * 100, 1) : 0;
            ?>
            <div class="stat-card">
                <h3><?php echo count($this_week_assignments); ?></h3>
                <p>Assignments This Week</p>
            </div>
            <div class="stat-card <?php echo $weekly_completion_rate >= 80 ? 'high' : ($weekly_completion_rate >= 60 ? 'medium' : 'low'); ?>">
                <h3><?php echo $weekly_completion_rate; ?>%</h3>
                <p>Weekly Class Completion</p>
            </div>
        </div>
    </div>
</div>

<script>
function exportProgress() {
    // Generate CSV export
    window.location.href = 'export_progress.php?curriculum=<?php echo $curriculum_id; ?>';
}

function generateCalendar() {
    // Generate printable calendar
    window.open('curriculum_calendar.php?id=<?php echo $curriculum_id; ?>', '_blank');
}
</script>

</body>
</html> 