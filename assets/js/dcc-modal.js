jQuery(document).ready(function($) {
    
    // CONFIG
    var STATION_LIMIT = 10; // Max stations allowed for Employers

    // --- HELPER: Update Sidebar Text ---
    function updateSidebarUI(modal) {
        var modalID = modal.attr('id');
        var btn = $('button[data-target="' + modalID + '"]');
        var clearBtn = $('.dcc-clear-trigger[data-target="' + modalID + '"]');
        var count = modal.find('input[type="checkbox"]:checked').length;
        
        var labelSpan = btn.find('span');
        if (labelSpan.length === 0) {
            btn.html('<span></span>');
            labelSpan = btn.find('span');
        }

        if(count > 0) {
            labelSpan.text(count + ' Selected');
            btn.addClass('has-selection');
            clearBtn.addClass('active');
        } else {
            var defaultText = modal.data('taxonomy').indexOf('category') > -1 ? 'Select Category' : 
                              (modal.data('taxonomy').indexOf('train') > -1 ? 'Select Line/Station' : 'Select Location');
            labelSpan.text(defaultText);
            btn.removeClass('has-selection');
            clearBtn.removeClass('active');
        }
    }

    // --- HELPER: Update Line Count Badge ---
    function updateLineCount( $lineWrapper ) {
        var $stations = $lineWrapper.find('.dcc-level-4-checkbox');
        var checked = $stations.filter(':checked').length;
        var $badge = $lineWrapper.find('.dcc-station-count');
        
        if ( checked > 0 ) {
            $badge.text(checked).show();
        } else {
            $badge.hide();
        }
    }

    // =======================================================
    // 1. BASIC INTERACTIONS
    // =======================================================
    $(document).on('click', '.dcc-modal-trigger', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var $modal = $('#' + target);
        $modal.fadeIn(200).css('display', 'flex');
        $('body').css('overflow', 'hidden');

        // Visuals: Open if checked
        $modal.find('.dcc-level-2-checkbox:checked, .dcc-level-3-checkbox:checked').each(function() {
            var level = $(this).hasClass('dcc-level-2-checkbox') ? 2 : 3;
            var next = level + 1;
            $(this).closest('.dcc-level-' + level + '-wrapper').find('.dcc-level-' + next + '-container').show();
        });
        
        $modal.find('.dcc-level-3-wrapper').each(function(){ updateLineCount($(this)); });
    });

    $(document).on('click', '.dcc-modal-close, .dcc-modal-cancel', function(e) {
        e.preventDefault();
        $('.dcc-modal-overlay').fadeOut(200);
        $('body').css('overflow', 'auto');
    });

    $(document).on('click', '.js-dcc-accordion-trigger', function() {
        $(this).toggleClass('active');
        $(this).next('.dcc-accordion-content').slideToggle(200);
    });

    $(document).on('change', '.dcc-modal-item-label input', function() {
        var modal = $(this).closest('.dcc-modal-overlay');
        if ( modal.data('mode') === 'search' ) updateSidebarUI(modal);
    });

    // =======================================================
    // 2. LEVEL 1 (ALL Checkbox)
    // =======================================================
    $(document).on('change', '.dcc-checkbox-all', function() {
        $(this).prop('indeterminate', false);
    });


    // =======================================================
    // 3. DECOUPLED HIERARCHY LOGIC
    // =======================================================

    // A. PARENT CLICKED
    $(document).on('change', '.dcc-level-2-checkbox, .dcc-level-3-checkbox', function() {
        var $parent = $(this);
        var level   = $parent.hasClass('dcc-level-2-checkbox') ? 2 : 3;
        var nextLevel = level + 1;
        var isChecked = $parent.is(':checked');
        var $wrapper  = $parent.closest('.dcc-level-' + level + '-wrapper');
        var $childContainer = $wrapper.find('.dcc-level-' + nextLevel + '-container');

        $parent.prop('indeterminate', false);

        if ( isChecked && $childContainer.length > 0 ) {
            $childContainer.slideDown(200);
            // Accordion: Close others
            if ( level === 3 ) {
                var $modal = $parent.closest('.dcc-modal-overlay');
                $modal.find('.dcc-level-4-container').not($childContainer).slideUp(200);
            }
        }
    });

    // B. CHILD CLICKED (LIMIT CHECK + SYNC)
    $(document).on('change', '.dcc-level-3-checkbox, .dcc-level-4-checkbox', function() {
        var $child = $(this);
        var modal = $child.closest('.dcc-modal-overlay');
        
        // --- LIMIT CHECK (Only for Stations / Level 4 in Submission Mode) ---
        if ( modal.data('mode') !== 'search' && $child.hasClass('dcc-level-4-checkbox') && $child.is(':checked') ) {
            // Count total Checked Stations (Level 4) across the WHOLE modal
            var totalStations = modal.find('.dcc-level-4-checkbox:checked').length;
            if ( totalStations > STATION_LIMIT ) {
                $child.prop('checked', false); // Undo check
                alert('You can only select up to ' + STATION_LIMIT + ' stations.');
                return; // Stop processing
            }
        }
        // --------------------------------------------------------------------

        var level  = $child.hasClass('dcc-level-3-checkbox') ? 3 : 4;
        var prevLevel = level - 1;

        var $wrapper  = $child.closest('.dcc-level-' + prevLevel + '-wrapper');
        var $parent = $wrapper.find('.dcc-level-' + prevLevel + '-checkbox').first();
        var $container = $child.closest('.dcc-level-' + level + '-container');
        var $siblings = $container.find('.dcc-level-' + level + '-checkbox');

        var total = $siblings.length;
        var checkedCount = $siblings.filter(':checked').length;

        // Update Parent State
        if ( checkedCount > 0 && checkedCount < total ) {
            $parent.prop('checked', false);
            $parent.prop('indeterminate', true);
        } else if ( checkedCount === total ) {
            $parent.prop('checked', true);
            $parent.prop('indeterminate', false);
        } else if ( checkedCount === 0 ) {
            $parent.prop('indeterminate', false);
        }
        
        if ( level === 4 ) updateLineCount($wrapper);

        // Bubble Up L4 -> L2
        if ( prevLevel > 2 ) {
            var $l3 = $parent;
            var $l2Wrapper = $l3.closest('.dcc-level-2-wrapper');
            var $l2Parent = $l2Wrapper.find('.dcc-level-2-checkbox').first();
            var $l3Siblings = $l2Wrapper.find('.dcc-level-3-checkbox');
            
            var l3Checked = $l3Siblings.filter(':checked').length;
            var l3Indeter = $l3Siblings.filter(function() { return this.indeterminate; }).length;

            if ( l3Checked === 0 && l3Indeter === 0 ) {
                 $l2Parent.prop('indeterminate', false);
            } else if ( l3Checked === $l3Siblings.length ) {
                 $l2Parent.prop('checked', true);
                 $l2Parent.prop('indeterminate', false);
            } else {
                 $l2Parent.prop('checked', false);
                 $l2Parent.prop('indeterminate', true);
            }
        }
    });

    // =======================================================
    // 4. SELECT ALL HELPER (Updated for Limit)
    // =======================================================
    $(document).on('click', '.dcc-select-all', function(e) {
        e.preventDefault();
        e.stopPropagation(); 

        var $wrapper = $(this).closest('.dcc-level-3-wrapper');
        var $children = $wrapper.find('.dcc-level-4-checkbox');
        var $parent = $wrapper.find('.dcc-level-3-checkbox');
        
        // Check mode
        var modal = $(this).closest('.dcc-modal-overlay');
        
        // If "Select All" would exceed global limit, block it in Submission Mode
        if ( modal.data('mode') !== 'search' ) {
            var currentTotal = modal.find('.dcc-level-4-checkbox:checked').length;
            var itemsInLine = $children.length;
            var alreadyCheckedInLine = $children.filter(':checked').length;
            var newItems = itemsInLine - alreadyCheckedInLine;
            
            if ( (currentTotal + newItems) > STATION_LIMIT ) {
                alert('You cannot select all. Limit is ' + STATION_LIMIT + ' stations.');
                return;
            }
        }

        var allChecked = ($children.length === $children.filter(':checked').length);
        $children.prop('checked', !allChecked);
        $parent.prop('checked', !allChecked);
        $parent.prop('indeterminate', false);
        
        updateLineCount($wrapper);
        $children.first().trigger('change'); 
    });


    // =======================================================
    // 5. ACTION BUTTONS
    // =======================================================
    $(document).on('click', '.dcc-modal-add', function(e) {
        e.preventDefault();
        var modal = $(this).closest('.dcc-modal-overlay');
        var mode = modal.data('mode'); 
        
        if ( mode === 'search' ) {
             updateSidebarUI(modal);
        } else {
            var tax = modal.data('taxonomy');
            var fieldName = modal.data('field-name');
            var container = $('#dcc-hidden-inputs-' + tax);
            var btn = $('button[data-target="dcc-modal-' + tax + '"]');
            var names = [];
            
            container.empty();
            modal.find('input[type="checkbox"]:checked').each(function() {
                names.push($(this).next('span').text()); 
                $('<input>').attr({type: 'hidden', name: fieldName, value: $(this).val()}).appendTo(container);
            });

            if (names.length === 0) {
                $('<input>').attr({type: 'hidden', name: fieldName, value: ''}).appendTo(container);
                btn.find('span').text('Select ' + tax.replace('job_listing_', ''));
                btn.removeClass('has-selection');
            } else {
                btn.find('span').text(names.join(', '));
                btn.addClass('has-selection');
            }
        }
        modal.fadeOut(200);
        $('body').css('overflow', 'auto');
    });

    $(document).on('click', '.dcc-clear-trigger', function(e) {
        e.preventDefault();
        var targetID = $(this).data('target');
        var modal = $('#' + targetID);

        modal.find('input[type="checkbox"]').prop('checked', false).prop('indeterminate', false);
        modal.find('.dcc-level-3-container, .dcc-level-4-container').hide();
        modal.find('.dcc-station-count').hide(); 
        updateSidebarUI(modal);
        modal.find('input.dcc-filter-input').first().trigger('change');
    });
});