---
id: task-12
title: "BUG: Chapter-level scheduling sorts books alphabetically"
status: Done
assignee: []
created_date: '2026-01-11 21:15'
labels: [bug]
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
When calculating a reading plan at the chapter level, books are being sorted alphabetically instead of in canonical scripture order.

**Expected Behavior:**
Books should appear in scripture order (e.g., Genesis, Exodus, Leviticus... or 1 Nephi, 2 Nephi, Jacob...)

**Actual Behavior:**
Books appear alphabetically (e.g., 1 Chronicles, 1 Corinthians, 1 John, 1 Kings...)

**Location:**
Likely in `app/Services/ScheduleCalculator.php` - chapter query may be missing ORDER BY clause for volume_id/book_id.
<!-- SECTION:DESCRIPTION:END -->
