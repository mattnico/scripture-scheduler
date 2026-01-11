<?php
// Teacher Curriculum Management System
// Extends the existing schedule system for seminary use

require_once 'calc.php'; // Use existing functions

// Simple authentication (you can enhance this later)
session_start();
$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 'demo_teacher';
$teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : 'Demo Teacher';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_curriculum':
                handle_create_curriculum();
                break;
            case 'save_assignments':
                handle_save_assignments();
                break;
        }
    }
}

function handle_create_curriculum() {
    global $teacher_id;
    
    $curriculum_data = array(
        'id' => generate_plan_id(),
        'type' => 'seminary_curriculum',
        'teacher_id' => $teacher_id,
        'class_name' => $_POST['class_name'],
        'seminary_year' => $_POST['seminary_year'],
        'school_year' => $_POST['school_year'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'meeting_days' => isset($_POST['meeting_days']) ? $_POST['meeting_days'] : array(),
        'assignments' => array(),
        'created' => date('Y-m-d H:i:s'),
        'enrollment_code' => strtoupper(generate_plan_id(6))
    );
    
    // Generate basic schedule based on meeting days
    if (!empty($_POST['meeting_days'])) {
        $curriculum_data['assignments'] = generate_basic_schedule(
            $_POST['start_date'], 
            $_POST['end_date'], 
            $_POST['meeting_days'],
            $_POST['seminary_year']
        );
    }
    
    $curriculum_id = save_curriculum($curriculum_data);
    
    if ($curriculum_id) {
        header('Location: teacher_curriculum.php?id=' . $curriculum_id . '&mode=edit');
        exit;
    }
}

function handle_save_assignments() {
    global $teacher_id;
    
    $curriculum_id = $_POST['curriculum_id'];
    $curriculum_data = load_curriculum($curriculum_id);
    
    if ($curriculum_data['teacher_id'] !== $teacher_id) {
        die('Access denied');
    }
    
    // Process assignment updates
    $assignments = array();
    if (isset($_POST['assignments'])) {
        foreach ($_POST['assignments'] as $index => $assignment) {
            if (!empty($assignment['scripture']) || !empty($assignment['lesson_topic'])) {
                $assignments[] = array(
                    'date' => $assignment['date'],
                    'scripture' => trim($assignment['scripture']),
                    'lesson_topic' => trim($assignment['lesson_topic']),
                    'is_major_deadline' => isset($assignment['is_major_deadline']),
                    'notes' => trim($assignment['notes'] ?? '')
                );
            }
        }
    }
    
    $curriculum_data['assignments'] = $assignments;
    $curriculum_data['updated'] = date('Y-m-d H:i:s');
    
    save_curriculum($curriculum_data);
    
    header('Location: teacher_curriculum.php?id=' . $curriculum_id . '&saved=1');
    exit;
}

function generate_basic_schedule($start_date, $end_date, $meeting_days, $seminary_year) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    $assignments = array();
    
    // Get curriculum template based on seminary year
    $curriculum_template = get_curriculum_template($seminary_year);
    $template_index = 0;
    
    $current_date = $start;
    while ($current_date <= $end && $template_index < count($curriculum_template)) {
        $day_of_week = date('N', $current_date); // 1 = Monday, 7 = Sunday
        
        if (in_array($day_of_week, $meeting_days)) {
            $template_item = $curriculum_template[$template_index];
            
            $assignments[] = array(
                'date' => date('Y-m-d', $current_date),
                'scripture' => $template_item['scripture'],
                'lesson_topic' => $template_item['topic'],
                'is_major_deadline' => $template_item['major_deadline'] ?? false,
                'notes' => ''
            );
            
            $template_index++;
        }
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    return $assignments;
}

function get_curriculum_template($seminary_year) {
    // Pre-built curriculum templates
    $templates = array(
        'old_testament' => array(
            array('scripture' => 'Genesis 1-3', 'topic' => 'The Creation', 'major_deadline' => false),
            array('scripture' => 'Genesis 4-11', 'topic' => 'The Fall and Early Prophets', 'major_deadline' => false),
            array('scripture' => 'Genesis 12-17', 'topic' => 'Abraham\'s Covenant', 'major_deadline' => true),
            array('scripture' => 'Genesis 18-23', 'topic' => 'Faith and Sacrifice', 'major_deadline' => false),
            // Add more as needed...
        ),
        'new_testament' => array(
            array('scripture' => 'Matthew 1-2', 'topic' => 'The Birth of Christ', 'major_deadline' => false),
            array('scripture' => 'Matthew 3-4', 'topic' => 'Baptism and Temptation', 'major_deadline' => false),
            array('scripture' => 'Matthew 5-7', 'topic' => 'Sermon on the Mount', 'major_deadline' => true),
            // Add more as needed...
        ),
        'book_of_mormon' => array(
            array('scripture' => '1 Nephi 1-5', 'topic' => 'Lehi\'s Family Leaves Jerusalem', 'major_deadline' => false),
            array('scripture' => '1 Nephi 6-10', 'topic' => 'The Brass Plates', 'major_deadline' => false),
            array('scripture' => '1 Nephi 11-15', 'topic' => 'Lehi\'s Dream and Nephi\'s Vision', 'major_deadline' => true),
            array('scripture' => '1 Nephi 16-22', 'topic' => 'Journey in the Wilderness', 'major_deadline' => false),
            array('scripture' => '2 Nephi 1-5', 'topic' => 'Lehi\'s Final Counsel', 'major_deadline' => false),
            array('scripture' => '2 Nephi 6-10', 'topic' => 'Jacob\'s Teachings', 'major_deadline' => false),
            array('scripture' => '2 Nephi 11-25', 'topic' => 'Isaiah\'s Prophecies', 'major_deadline' => true),
            // Add more as needed...
        ),
        'doctrine_covenants' => array(
            array('scripture' => 'D&C 1-3', 'topic' => 'Introduction to the Restoration', 'major_deadline' => false),
            array('scripture' => 'D&C 4-6', 'topic' => 'The Work of the Lord', 'major_deadline' => false),
            array('scripture' => 'D&C 7-9', 'topic' => 'Translation and Testimony', 'major_deadline' => true),
            // Add more as needed...
        )
    );
    
    return isset($templates[$seminary_year]) ? $templates[$seminary_year] : array();
}

function save_curriculum($curriculum_data) {
    $plans_dir = create_plan_directory();
    $curriculum_file = $plans_dir . '/curriculum_' . $curriculum_data['id'] . '.json';
    
    $json_data = json_encode($curriculum_data, JSON_PRETTY_PRINT);
    if (file_put_contents($curriculum_file, $json_data)) {
        return $curriculum_data['id'];
    }
    
    return false;
}

function load_curriculum($curriculum_id) {
    $plans_dir = create_plan_directory();
    $curriculum_file = $plans_dir . '/curriculum_' . $curriculum_id . '.json';
    
    if (file_exists($curriculum_file)) {
        $json_data = file_get_contents($curriculum_file);
        return json_decode($json_data, true);
    }
    
    return false;
}

function get_teacher_curricula($teacher_id) {
    $plans_dir = create_plan_directory();
    $curricula = array();
    
    if (is_dir($plans_dir)) {
        $files = glob($plans_dir . '/curriculum_*.json');
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['teacher_id'] === $teacher_id) {
                $curricula[] = $data;
            }
        }
    }
    
    return $curricula;
}

// Get current curriculum if editing
$current_curriculum = null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
if (isset($_GET['id'])) {
    $current_curriculum = load_curriculum($_GET['id']);
    if (!$current_curriculum || $current_curriculum['teacher_id'] !== $teacher_id) {
        die('Curriculum not found or access denied');
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Seminary Curriculum Manager</title>
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .curriculum-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .assignment-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .assignment-row.major-deadline { border-left: 4px solid #e74c3c; background-color: #fdf2f2; }
        .form-grid { display: grid; grid-template-columns: 100px 1fr 200px 80px 40px; gap: 10px; align-items: center; }
        .btn-add { background: #27ae60; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-remove { background: #e74c3c; color: white; padding: 2px 6px; border: none; border-radius: 3px; cursor: pointer; }
        .enrollment-code { font-size: 24px; font-weight: bold; background: #f8f9fa; padding: 10px; text-align: center; border-radius: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; background: #f8f9fa; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>

<div class="curriculum-container">
    <h1>Seminary Curriculum Manager</h1>
    <p>Welcome, <?php echo htmlspecialchars($teacher_name); ?>!</p>

    <?php if ($mode === 'list'): ?>
        <!-- List of Teacher's Curricula -->
        <div class="pure-g">
            <div class="pure-u-2-3">
                <h2>Your Curricula</h2>
                <?php 
                $curricula = get_teacher_curricula($teacher_id);
                if (empty($curricula)):
                ?>
                    <p>You haven't created any curricula yet. Create your first one!</p>
                <?php else: ?>
                    <table class="pure-table pure-table-striped" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Seminary Year</th>
                                <th>School Year</th>
                                <th>Assignments</th>
                                <th>Enrollment Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($curricula as $curriculum): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($curriculum['class_name']); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $curriculum['seminary_year'])); ?></td>
                                <td><?php echo htmlspecialchars($curriculum['school_year']); ?></td>
                                <td><?php echo count($curriculum['assignments']); ?> assignments</td>
                                <td><strong><?php echo $curriculum['enrollment_code']; ?></strong></td>
                                <td>
                                    <a href="?id=<?php echo $curriculum['id']; ?>&mode=edit">Edit</a> |
                                    <a href="teacher_dashboard.php?id=<?php echo $curriculum['id']; ?>">Dashboard</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="pure-u-1-3" style="padding-left: 20px;">
                <h3>Create New Curriculum</h3>
                
                <!-- Import Option -->
                <div style="background: #e8f4f8; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <h4 style="margin-top: 0;">✨ NEW: Auto-Import from Official Manuals</h4>
                    <p style="margin-bottom: 10px;">Import lesson titles and scripture references directly from Church seminary manuals</p>
                    <a href="curriculum_import.php" class="pure-button pure-button-primary">
                        📚 Import from Seminary Manual
                    </a>
                </div>
                
                <div style="text-align: center; margin: 15px 0; color: #666;">
                    <strong>— OR —</strong>
                </div>
                
                <h4>Create Manual Curriculum</h4>
                <form method="post" class="pure-form pure-form-stacked">
                    <input type="hidden" name="action" value="create_curriculum">
                    
                    <label for="class_name">Class Name:</label>
                    <input type="text" name="class_name" id="class_name" placeholder="e.g., Period 1 Seminary" required>
                    
                    <label for="seminary_year">Seminary Year:</label>
                    <select name="seminary_year" id="seminary_year" required>
                        <option value="">Select...</option>
                        <option value="old_testament">Old Testament</option>
                        <option value="new_testament">New Testament</option>
                        <option value="book_of_mormon">Book of Mormon</option>
                        <option value="doctrine_covenants">Doctrine & Covenants</option>
                    </select>
                    
                    <label for="school_year">School Year:</label>
                    <input type="text" name="school_year" id="school_year" placeholder="2024-2025" required>
                    
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" required>
                    
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" required>
                    
                    <label>Meeting Days:</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="1"> Monday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="2"> Tuesday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="3"> Wednesday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="4"> Thursday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="5"> Friday</label>
                    
                    <button type="submit" class="pure-button pure-button-primary">Create Basic Curriculum</button>
                </form>
            </div>
        </div>

    <?php elseif ($mode === 'edit' && $current_curriculum): ?>
        <!-- Edit Curriculum Assignments -->
        <div class="pure-g">
            <div class="pure-u-1">
                <h2>Edit: <?php echo htmlspecialchars($current_curriculum['class_name']); ?></h2>
                
                <?php if (isset($current_curriculum['source_manual'])): ?>
                    <div style="background: #e8f4f8; padding: 10px; border-radius: 4px; margin: 10px 0;">
                        📚 <strong>Imported from Official Manual:</strong> 
                        <a href="<?php echo htmlspecialchars($current_curriculum['source_manual']); ?>" target="_blank">
                            View Source Manual
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($current_curriculum['assignments']); ?></h3>
                        <p>Total Assignments</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($current_curriculum['assignments'], function($a) { return $a['is_major_deadline']; })); ?></h3>
                        <p>Major Deadlines</p>
                    </div>
                    <div class="stat-card">
                        <div class="enrollment-code"><?php echo $current_curriculum['enrollment_code']; ?></div>
                        <p>Enrollment Code</p>
                    </div>
                </div>

                <?php if (isset($_GET['saved'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;">
                        ✅ Curriculum saved successfully!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['imported'])): ?>
                    <div style="background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0;">
                        ✅ Curriculum imported successfully from official seminary manual! You can now edit assignments and dates below.
                    </div>
                <?php endif; ?>

                <form method="post" class="pure-form">
                    <input type="hidden" name="action" value="save_assignments">
                    <input type="hidden" name="curriculum_id" value="<?php echo $current_curriculum['id']; ?>">
                    
                    <div style="margin-bottom: 15px;">
                        <button type="button" class="btn-add" onclick="addAssignment()">+ Add Assignment</button>
                        <button type="submit" class="pure-button pure-button-primary" style="margin-left: 10px;">Save All Changes</button>
                        <a href="teacher_curriculum.php" class="pure-button">Back to List</a>
                    </div>

                    <div id="assignments-container">
                        <div class="form-grid" style="font-weight: bold; margin-bottom: 10px;">
                            <div>Date</div>
                            <div>Scripture Reading</div>
                            <div>Lesson Topic</div>
                            <div>Major?</div>
                            <div></div>
                        </div>
                        
                        <?php foreach ($current_curriculum['assignments'] as $index => $assignment): ?>
                        <div class="assignment-row <?php echo $assignment['is_major_deadline'] ? 'major-deadline' : ''; ?>">
                            <div class="form-grid">
                                <input type="date" name="assignments[<?php echo $index; ?>][date]" value="<?php echo $assignment['date']; ?>" class="pure-input-1">
                                <input type="text" name="assignments[<?php echo $index; ?>][scripture]" value="<?php echo htmlspecialchars($assignment['scripture']); ?>" placeholder="e.g., 1 Nephi 1-5" class="pure-input-1">
                                <input type="text" name="assignments[<?php echo $index; ?>][lesson_topic]" value="<?php echo htmlspecialchars($assignment['lesson_topic']); ?>" placeholder="Lesson topic" class="pure-input-1">
                                <input type="checkbox" name="assignments[<?php echo $index; ?>][is_major_deadline]" <?php echo $assignment['is_major_deadline'] ? 'checked' : ''; ?>>
                                <button type="button" class="btn-remove" onclick="removeAssignment(this)">×</button>
                            </div>
                            <div style="margin-top: 5px;">
                                <input type="text" name="assignments[<?php echo $index; ?>][notes]" value="<?php echo htmlspecialchars($assignment['notes'] ?? ''); ?>" placeholder="Notes (optional)" class="pure-input-1" style="font-size: 12px;">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </div>

        <script>
        let assignmentIndex = <?php echo count($current_curriculum['assignments']); ?>;
        
        function addAssignment() {
            const container = document.getElementById('assignments-container');
            const newAssignment = document.createElement('div');
            newAssignment.className = 'assignment-row';
            newAssignment.innerHTML = `
                <div class="form-grid">
                    <input type="date" name="assignments[${assignmentIndex}][date]" class="pure-input-1">
                    <input type="text" name="assignments[${assignmentIndex}][scripture]" placeholder="e.g., 1 Nephi 1-5" class="pure-input-1">
                    <input type="text" name="assignments[${assignmentIndex}][lesson_topic]" placeholder="Lesson topic" class="pure-input-1">
                    <input type="checkbox" name="assignments[${assignmentIndex}][is_major_deadline]">
                    <button type="button" class="btn-remove" onclick="removeAssignment(this)">×</button>
                </div>
                <div style="margin-top: 5px;">
                    <input type="text" name="assignments[${assignmentIndex}][notes]" placeholder="Notes (optional)" class="pure-input-1" style="font-size: 12px;">
                </div>
            `;
            container.appendChild(newAssignment);
            assignmentIndex++;
        }
        
        function removeAssignment(button) {
            button.closest('.assignment-row').remove();
        }
        </script>

    <?php endif; ?>

</div>

</body>
</html> 