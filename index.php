<html>
	<head>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
		<link href="theme.css?v=<?= time() ?>" rel="stylesheet">
		<title>Word-Based Scripture Scheduler - SmallSimple.org</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<button class="theme-toggle" id="theme-toggle" title="Toggle theme">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<circle cx="12" cy="12" r="5"/>
				<path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
			</svg>
		</button>
		
		<!-- Title Header -->
		<div class="container header-container mt-5">
			<div class="row justify-content-center">
				<div class="col-lg-8 col-md-10" style="padding-left: 2rem; padding-right: 2rem;">
					<div class="mb-5">
						<h1 class="display-3 mb-3 lh-1">
							<span class="text-accent fw-bold">Word-Based</span><br>
							<span class="text-primary">Scripture Scheduler</span><span class="text-muted" style="font-size: 0.8rem; vertical-align: top; font-weight: 800;">BETA</span>
						</h1>
						<p class="text-muted mb-0 fs-6">by SmallSimple.org</p>
					</div>
					
					<p class="mb-0 lead text-primary fs-5">
						Create a personalized reading schedule for the scriptures! Unlike other reading plans that use uneven chapters, 
						this tool balances your daily reading <strong>by actual word count</strong> to ensure consistent daily portions.
					</p>
				</div>
			</div>
		</div>

		<!-- Main Content -->
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-8 col-md-10">
					
					<div class="mb-4 feature-list">
						<ul id="feature-list-items" class="list-unstyled text-muted">
							<li class="mb-2">&#128214; <strong>Choose your volumes:</strong> Mix and match any combination of scripture volumes</li>
							<li class="mb-2">&#128197; <strong>Set your timeline:</strong> Pick your start and end dates for flexible scheduling</li>
							<li class="mb-2">&#9878; <strong>Balanced reading:</strong> Get verse-level precision or chapter-level simplicity</li>
							<li class="mb-2">&#128202; <strong>Multiple formats:</strong> Generate interactive plans, calendars, tables, or CSV files</li>
							<li class="mb-2">&#127919; <strong>Stay on track:</strong> Consistent, manageable daily reading goals</li>
						</ul>
					</div>
					
					<form action="calc.php" method="post">
				        <div class="card mb-4">
				        	<div class="card-body">
					        	<h5 class="card-title">Reading Schedule</h5>
					        	<div class="row">
					        		<div class="col-md-6">
						            	<label for="start_date" class="form-label">When will you start reading?</label>
						            	<input id="start_date" name="start_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>">
					        		</div>
					        		<div class="col-md-6">
						            	<label for="end_date" class="form-label">When will you finish reading?</label>
						            	<input id="end_date" name="end_date" type="date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year -1 day')) ?>">
					        		</div>
					        	</div>
				        	</div>
				        </div>

				        <div class="card mb-4">
				        	<div class="card-body">
					        	<h5 class="card-title">Scripture Selection</h5>
					        	<div class="row">
					        		<div class="col-md-6">
						        		<label class="form-label">Which volumes do you want to read in that time?</label>
						        		<div>
							            	<div class="form-check">
							                	<input id="ot" name="ot" type="checkbox" class="form-check-input">
							                	<label for="ot" class="form-check-label">Old Testament</label>
							            	</div>
							            	<div class="form-check">
							                	<input id="nt" name="nt" type="checkbox" class="form-check-input">
							                	<label for="nt" class="form-check-label">New Testament</label>
							            	</div>
							            	<div class="form-check">
							                	<input id="bom" name="bom" type="checkbox" class="form-check-input" checked="checked">
							                	<label for="bom" class="form-check-label">Book of Mormon</label>
							            	</div>
							            	<div class="form-check">
							                	<input id="dc" name="dc" type="checkbox" class="form-check-input">
							                	<label for="dc" class="form-check-label">Doctrine &amp; Covenants</label>
							            	</div>
							            	<div class="form-check">
							                	<input id="pgp" name="pgp" type="checkbox" class="form-check-input">
							                	<label for="pgp" class="form-check-label">Pearl of Great Price</label>
							            	</div>
						        		</div>
					        		</div>
					        		<div class="col-md-6">
						            	<label for="beginning_verse" class="form-label">Starting Verse</label>
						            	<div class="autocomplete-container">
						                	<input id="beginning_verse" name="beginning_verse" type="text" placeholder="1 Nephi 3:7" class="form-control verse-input" autocomplete="off">
						                	<div id="autocomplete-suggestions" class="autocomplete-suggestions"></div>
						            	</div>
						            	<div id="validation-message" class="validation-message"></div>
						        		
						        		<div class="alert alert-info mb-3 mt-3">
						        			<strong>Already started reading?</strong> Tell us what verse you'll read next and we'll base your schedule on that.
						        		</div>
					        		</div>
					        	</div>
				        	</div>
				        </div>

				        <div class="card mb-4">
				        	<div class="card-body">
					        	<h5 class="card-title">Choose Your Output</h5>
					        	
					        	<!-- Hidden input to track which tab is active -->
					        	<input type="hidden" id="create_plan" name="create_plan" value="on">
					        	
					        	<!-- Bootstrap Tabs -->
					        	<ul class="nav nav-tabs" id="output-tabs" role="tablist">
					        		<li class="nav-item" role="presentation">
					        			<button class="nav-link active" id="interactive-tab" data-bs-toggle="tab" data-bs-target="#interactive-pane" type="button" role="tab">
					        				<strong>Interactive Plan</strong> <span class="badge bg-primary ms-1">Recommended</span>
					        			</button>
					        		</li>
					        		<li class="nav-item" role="presentation">
					        			<button class="nav-link" id="static-tab" data-bs-toggle="tab" data-bs-target="#static-pane" type="button" role="tab">
					        				<strong>Static File</strong>
					        			</button>
					        		</li>
					        	</ul>
					        	
					        	<!-- Tab Content -->
					        	<div class="tab-content mt-3" id="output-tab-content">
					        		<!-- Interactive Plan Tab -->
					        		<div class="tab-pane fade show active" id="interactive-pane" role="tabpanel">
					        			<div class="alert alert-info">
					        				<strong>Interactive Plan:</strong> You'll get a personal bookmark-able link to track your progress, see today's reading, and stay motivated!
					        			</div>
					        			
					        			<div class="row mb-3">
					        				<div class="col-md-6">
						        				<label for="scheduling_method_interactive" class="form-label">Scheduling Method</label>
						        				<select id="scheduling_method_interactive" name="scheduling_method" class="form-select">
						        					<option value="verse">Verse-level (precise word count)</option>
						        					<option value="chapter">Chapter-level (balanced by chapter)</option>
						        				</select>
					        				</div>
					        				<div class="col-md-6">
					        					<label class="form-label">Features</label>
						        				<ul id="interactive-features-list" class="list-unstyled mb-0">
						        					<li><small>&#10003; Daily progress tracking</small></li>
						        					<li><small>&#10003; Bookmark-able personal link</small></li>
						        					<li><small>&#10003; See today's reading assignment</small></li>
						        					<li><small>&#10003; Track days ahead/behind schedule</small></li>
						        				</ul>
					        				</div>
					        			</div>
					        		</div>
					        		
					        		<!-- Static File Tab -->
					        		<div class="tab-pane fade" id="static-pane" role="tabpanel">
					        			<div class="alert alert-warning">
					        				<strong>Static Output:</strong> You'll get a one-time PDF or file download. No progress tracking or daily updates.
					        			</div>
					        			
					        			<div class="row">
					        				<div class="col-md-6">
						        				<label for="scheduling_method" class="form-label">Scheduling Method</label>
						        				<select id="scheduling_method" name="scheduling_method" class="form-select">
						        					<option value="verse">Verse-level (precise word count)</option>
						        					<option value="chapter">Chapter-level (balanced by chapter)</option>
						        				</select>
					        				</div>
					        				<div class="col-md-6">
						        				<label for="format" class="form-label">Format</label>
						        				<select id="format" name="format" class="form-select mb-3">
						        					<option value="calendar">Calendar w/Quotes</option>
						        					<option value="calendar_plain">Plain Calendar</option>
						        					<option value="table">Table</option>
						        					<option value="csv">CSV File</option>
						        				</select>
						        				
						            			<div class="form-check">
													<input id="show_word_count" name="show_word_count" type="checkbox" class="form-check-input">
													<label for="show_word_count" class="form-check-label">Show word count on calendar</label>
						            			</div>
					        				</div>
					        			</div>
					        		</div>
					        	</div>
				        	</div>
				        </div>

				        <div class="mb-3">
				            <button type="submit" id="submit-button" class="btn btn-primary submit-button">Create Reading Plan</button>
				        </div>
					</form>
				</div>
			</div>
		</div>

		<script>
		// Theme toggle functionality
		const themeToggle = document.getElementById('theme-toggle');
		const body = document.body;
		
		// Load saved theme or default to dark
		const savedTheme = localStorage.getItem('theme') || 'dark';
		if (savedTheme === 'light') {
			body.classList.add('light-mode');
		}
		
		// Toggle theme
		themeToggle.addEventListener('click', () => {
			body.classList.toggle('light-mode');
			const newTheme = body.classList.contains('light-mode') ? 'light' : 'dark';
			localStorage.setItem('theme', newTheme);
		});
		
		document.addEventListener('DOMContentLoaded', function() {
			let allVerses = [];
			let allChapters = [];
			let filteredData = [];
			let selectedIndex = -1;
			let currentMode = 'verse'; // 'verse' or 'chapter'
			
			const input = document.getElementById('beginning_verse');
			const suggestionsContainer = document.getElementById('autocomplete-suggestions');
			const validationMessage = document.getElementById('validation-message');
			const submitButton = document.getElementById('submit-button');
			const volumeCheckboxes = document.querySelectorAll('input[type="checkbox"][name$="t"], input[type="checkbox"][name="bom"], input[type="checkbox"][name="dc"], input[type="checkbox"][name="pgp"]');
			const schedulingMethodInteractive = document.getElementById('scheduling_method_interactive');
			const schedulingMethodStatic = document.getElementById('scheduling_method');
			
			// Load both verse and chapter data
			Promise.all([
				fetch('get_verses.php').then(response => {
					if (!response.ok) throw new Error('Failed to load verse data');
					return response.json();
				}),
				fetch('get_chapters.php').then(response => {
					if (!response.ok) throw new Error('Failed to load chapter data');
					return response.json();
				})
			]).then(([verses, chapters]) => {
				console.log('Loaded verse data:', verses.length, 'verses');
				console.log('Loaded chapter data:', chapters.length, 'chapters');
				allVerses = verses;
				allChapters = chapters;
				updateFilteredData();
			}).catch(error => {
				console.error('Error loading scripture data:', error);
				validationMessage.className = 'validation-message invalid text-danger small';
				validationMessage.innerHTML = '&#10005; Failed to load scripture data. Please refresh the page.';
			});

			// Update current mode based on selected scheduling method
			function updateCurrentMode() {
				const activeSchedulingMethod = schedulingMethodInteractive.closest('.tab-pane').classList.contains('active') 
					? schedulingMethodInteractive.value 
					: schedulingMethodStatic.value;
				
				currentMode = activeSchedulingMethod;
				console.log('Current mode updated to:', currentMode);
				
				// Update placeholder text
				if (currentMode === 'chapter') {
					input.placeholder = '1 Nephi 3';
				} else {
					input.placeholder = '1 Nephi 3:7';
				}
				
				updateFilteredData();
			}

			// Update filtered data based on selected volumes and current mode
			function updateFilteredData() {
				const selectedVolumes = getSelectedVolumes();
				console.log('Selected volumes:', selectedVolumes, 'Current mode:', currentMode);
				
				if (selectedVolumes.length === 0) {
					filteredData = [];
				} else {
					const sourceData = currentMode === 'chapter' ? allChapters : allVerses;
					filteredData = sourceData.filter(item => selectedVolumes.includes(item.volume_id));
				}
				
				console.log('Filtered data count:', filteredData.length, 'for mode:', currentMode);
				
				// Re-validate input with new filtered data
				validateInput();
				
				// Update submit button state
				updateSubmitButton();
			}

			// Get selected volume IDs
			function getSelectedVolumes() {
				const volumes = [];
				volumeCheckboxes.forEach(checkbox => {
					if (checkbox.checked) {
						switch(checkbox.name) {
							case 'ot': volumes.push(1); break;
							case 'nt': volumes.push(2); break;
							case 'bom': volumes.push(3); break;
							case 'dc': volumes.push(4); break;
							case 'pgp': volumes.push(5); break;
						}
					}
				});
				return volumes;
			}

			// Update submit button state
			function updateSubmitButton() {
				const selectedVolumes = getSelectedVolumes();
				if (selectedVolumes.length === 0) {
					submitButton.disabled = true;
				} else {
					submitButton.disabled = false;
				}
			}

			// Listen for volume checkbox changes
			volumeCheckboxes.forEach(checkbox => {
				checkbox.addEventListener('change', updateFilteredData);
			});

			// Listen for scheduling method changes
			if (schedulingMethodInteractive) {
				schedulingMethodInteractive.addEventListener('change', updateCurrentMode);
			}
			if (schedulingMethodStatic) {
				schedulingMethodStatic.addEventListener('change', updateCurrentMode);
			}

			// Initialize submit button state and current mode
			updateCurrentMode();
			updateSubmitButton();

			// Handle tab switching for output options
			const createPlanInput = document.getElementById('create_plan');
			const interactiveTab = document.getElementById('interactive-tab');
			const staticTab = document.getElementById('static-tab');
			
			// Sync the scheduling method dropdowns
			function syncSchedulingMethods() {
				if (schedulingMethodInteractive && schedulingMethodStatic) {
					// When interactive changes, update static and mode
					schedulingMethodInteractive.addEventListener('change', function() {
						schedulingMethodStatic.value = this.value;
						updateCurrentMode();
					});
					
					// When static changes, update interactive and mode
					schedulingMethodStatic.addEventListener('change', function() {
						schedulingMethodInteractive.value = this.value;
						updateCurrentMode();
					});
				}
			}
			
			function updateOutputMode() {
				if (interactiveTab && staticTab && createPlanInput && submitButton) {
					if (interactiveTab.classList.contains('active')) {
						// Interactive tab is active
						createPlanInput.value = 'on';
						submitButton.textContent = 'Create Reading Plan';
						console.log('Switched to Interactive Plan mode, create_plan value:', createPlanInput.value);
					} else {
						// Static tab is active
						createPlanInput.value = '';
						submitButton.textContent = 'Generate PDF/File';
						console.log('Switched to Static File mode, create_plan value:', createPlanInput.value);
					}
				}
			}
			
			// Listen for Bootstrap tab events
			if (interactiveTab && staticTab) {
				// Use Bootstrap's tab events instead of click events
				interactiveTab.addEventListener('shown.bs.tab', function() {
					updateOutputMode();
				});
				
				staticTab.addEventListener('shown.bs.tab', function() {
					updateOutputMode();
				});
				
				// Also listen for clicks as backup
				interactiveTab.addEventListener('click', function() {
					setTimeout(updateOutputMode, 100);
				});
				
				staticTab.addEventListener('click', function() {
					setTimeout(updateOutputMode, 100);
				});
				
				updateOutputMode(); // Initialize on page load
				syncSchedulingMethods(); // Initialize scheduling method sync
			}

			// Handle format dropdown changes to show/hide word count checkbox
			const formatSelect = document.getElementById('format');
			const wordCountCheckboxElement = document.querySelector('input[name="show_word_count"]');
			const wordCountCheckbox = wordCountCheckboxElement ? wordCountCheckboxElement.closest('.form-check') : null;
			
			function updateWordCountVisibility() {
				const selectedFormat = formatSelect.value;
				if (wordCountCheckbox) {
					if (selectedFormat === 'table' || selectedFormat === 'csv') {
						wordCountCheckbox.style.visibility = 'hidden';
					} else {
						wordCountCheckbox.style.visibility = 'visible';
					}
				}
			}
			
			if (formatSelect) {
				formatSelect.addEventListener('change', updateWordCountVisibility);
				updateWordCountVisibility(); // Initialize on page load
			}
			
			function showSuggestions(suggestions) {
				console.log('Showing suggestions:', suggestions.length);
				suggestionsContainer.innerHTML = '';
				selectedIndex = -1;
				
				if (suggestions.length === 0) {
					suggestionsContainer.style.display = 'none';
					return;
				}
				
				suggestions.slice(0, 10).forEach((suggestion, index) => {
					const div = document.createElement('div');
					div.className = 'autocomplete-suggestion';
					div.textContent = suggestion;
					div.addEventListener('click', function() {
						input.value = suggestion;
						suggestionsContainer.style.display = 'none';
						validateInput();
					});
					suggestionsContainer.appendChild(div);
				});
				
				suggestionsContainer.style.display = 'block';
				console.log('Suggestions container display:', suggestionsContainer.style.display);
			}
			
			function validateInput() {
				const value = input.value.trim();
				
				if (value === '' || value.length < 2) {
					input.classList.remove('is-valid', 'is-invalid');
					validationMessage.className = 'validation-message';
					validationMessage.innerHTML = '&nbsp;';
					return;
				}
				
				// Check for exact match first
				const isExactMatch = filteredData.some(item => item.verse_title === value);
				
				if (isExactMatch) {
					input.classList.remove('is-invalid');
					input.classList.add('is-valid');
					const referenceType = currentMode === 'chapter' ? 'chapter' : 'verse';
					validationMessage.className = 'validation-message valid text-success small';
					validationMessage.innerHTML = `&#10003; Valid ${referenceType} reference`;
					return;
				}
				
				// If not exact match, check if there are suggestions (partial matches)
				const suggestions = filterSuggestions(value);
				
				if (suggestions.length > 0) {
					// There are suggestions, so this could be a valid partial input
					input.classList.remove('is-valid', 'is-invalid');
					validationMessage.className = 'validation-message';
					validationMessage.innerHTML = '&nbsp;';
				} else {
					// No suggestions and no exact match
					input.classList.remove('is-valid');
					input.classList.add('is-invalid');
					validationMessage.className = 'validation-message invalid text-danger small';
					if (filteredData.length === 0) {
						validationMessage.innerHTML = '&#10005; Please select at least one volume';
					} else {
						const referenceType = currentMode === 'chapter' ? 'chapter' : 'verse';
						validationMessage.innerHTML = `&#10005; ${referenceType.charAt(0).toUpperCase() + referenceType.slice(1)} reference not found in selected volumes`;
					}
				}
			}
			
			function filterSuggestions(query) {
				if (query.length < 2 || filteredData.length === 0) return [];
				
				const queryLower = query.toLowerCase();
				return filteredData
					.filter(item => item.verse_title.toLowerCase().includes(queryLower))
					.map(item => item.verse_title)
					.sort((a, b) => {
						const aIndex = a.toLowerCase().indexOf(queryLower);
						const bIndex = b.toLowerCase().indexOf(queryLower);
						if (aIndex !== bIndex) return aIndex - bIndex;
						// Use natural/alphanumeric sorting for proper number ordering
						return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
					});
			}
			
			// Input event listener
			input.addEventListener('input', function() {
				const query = this.value.trim();
				console.log('Input event triggered, query:', query, 'Mode:', currentMode);
				console.log('Filtered data available:', filteredData.length);
				
				if (query.length >= 2) {
					const suggestions = filterSuggestions(query);
					console.log('Generated suggestions:', suggestions.length, 'items');
					showSuggestions(suggestions);
				} else {
					suggestionsContainer.style.display = 'none';
				}
				
				validateInput();
			});
			
			// Keyboard navigation
			input.addEventListener('keydown', function(e) {
				const suggestions = suggestionsContainer.children;
				
				if (suggestions.length === 0) return;
				
				switch(e.key) {
					case 'ArrowDown':
						e.preventDefault();
						selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
						updateSelection();
						break;
					case 'ArrowUp':
						e.preventDefault();
						selectedIndex = Math.max(selectedIndex - 1, -1);
						updateSelection();
						break;
					case 'Enter':
						e.preventDefault();
						if (selectedIndex >= 0) {
							input.value = suggestions[selectedIndex].textContent;
							suggestionsContainer.style.display = 'none';
							validateInput();
						}
						break;
					case 'Escape':
						suggestionsContainer.style.display = 'none';
						break;
				}
			});
			
			function updateSelection() {
				const suggestions = suggestionsContainer.children;
				
				for (let i = 0; i < suggestions.length; i++) {
					suggestions[i].classList.remove('selected');
				}
				
				if (selectedIndex >= 0) {
					suggestions[selectedIndex].classList.add('selected');
				}
			}
			
			// Hide suggestions when clicking outside
			document.addEventListener('click', function(e) {
				if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
					suggestionsContainer.style.display = 'none';
				}
			});
			
			// Validate on blur
			input.addEventListener('blur', function() {
				setTimeout(() => {
					validateInput();
				}, 100);
			});
		});
		</script>
		
		<!-- Bootstrap JS for tab functionality -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
	</body>
</html>