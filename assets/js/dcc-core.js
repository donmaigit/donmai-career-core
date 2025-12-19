jQuery(document).ready(function($) {
    
    // ARRAYS
    var activeLocations  = [];
    var activeCategories = [];

    // Initial Load
    dcc_fetch_jobs();
    checkTrainDependency(); // Check initial state

    // --- DEPENDENCY LOGIC: LOCATION -> TRAIN ---
    function checkTrainDependency() {
        // 1. Find all SELECTED Locations (Any Level)
        // We look for ANY checked input that has a 'data-pref-slug' attribute
        
        var selectedRegions = [];
        
        // Modal Mode Check
        $('#dcc-modal-search-location input:checked').each(function() {
            var pref = $(this).data('pref-slug'); // Get parent pref slug
            if(pref && !selectedRegions.includes(pref)) {
                selectedRegions.push(pref);
            }
        });

        // Dropdown Mode Check (Fallback)
        if ( selectedRegions.length === 0 ) {
            if ( activeLocations.length > 0 ) selectedRegions.push('all');
        }

        // 2. Toggle Train Wrapper
        var $wrapper = $('.dcc-train-wrapper');
        var $btn = $('#dcc-train-btn');
        var $hint = $('#dcc-train-hint');
        var $modalBody = $('#dcc-modal-search-train .dcc-modal-body');

        if ( selectedRegions.length > 0 ) {
            // ENABLE
            $wrapper.css({ 'opacity': '1', 'pointer-events': 'auto' });
            $hint.hide();
            
            // FILTER MODAL CONTENT
            $modalBody.find('.dcc-train-pref-group').hide(); // Hide all first
            
            if ( selectedRegions.includes('all') ) {
                $modalBody.find('.dcc-train-pref-group').show();
            } else {
                selectedRegions.forEach(function(slug) {
                    $modalBody.find('.dcc-train-pref-group[data-pref-slug*="'+slug+'"]').show();
                });
            }

        } else {
            // DISABLE
            $wrapper.css({ 'opacity': '0.5', 'pointer-events': 'none' });
            $hint.show();
        }
    }

    // Listen for Location Changes (to trigger dependency check)
    $(document).on('change', '#dcc-modal-search-location input', function() {
        checkTrainDependency();
    });
    
    // Also listen for Tag removal (Dropdown mode)
    $(document).on('click', '.dcc-tag-remove', function() {
        setTimeout(checkTrainDependency, 100); 
    });


    // --- GENERIC ADD FUNCTION ---
    function dcc_add_tag( selectID, containerID, storageArray, callback ) {
        var $select = $(selectID);
        var val = $select.val();
        var label = $select.find('option:selected').text();

        if ( val !== '' && ! storageArray.includes(val) ) {
            storageArray.push(val);
            var type = (selectID === '#dcc-location-select') ? 'loc' : 'cat';
            var tagHtml = '<div class="dcc-tag" data-val="'+val+'" data-type="'+type+'">' + label + ' <span class="dcc-tag-remove">×</span></div>';
            $(containerID).append(tagHtml);
            $select.val('');
            callback();
        }
    }

    // BUTTON HANDLERS
    $('#dcc-add-location').on('click', function() {
        dcc_add_tag('#dcc-location-select', '#dcc-location-tags', activeLocations, dcc_fetch_jobs);
    });

    $('#dcc-add-category').on('click', function() {
        dcc_add_tag('#dcc-category-select', '#dcc-category-tags', activeCategories, dcc_fetch_jobs);
    });

    // REMOVE TAG
    $(document).on('click', '.dcc-tag-remove', function() {
        var $tag = $(this).closest('.dcc-tag');
        var val = $tag.data('val');
        var type = $tag.data('type');

        if( type === 'loc' ) {
            activeLocations = activeLocations.filter(item => item !== val);
        } else if ( type === 'cat' ) {
            activeCategories = activeCategories.filter(item => item !== val);
        }
        
        $tag.remove();
        dcc_fetch_jobs();
    });

    // CHECKBOX LISTENER (Generic for Loc, Cat, Type, Train)
    $(document).on('change', '.dcc-filter-input', function() {
        dcc_fetch_jobs();
    });

    var timer;
    $('#dcc-keywords').on('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            dcc_fetch_jobs();
        }, 500);
    });

    // GLOBAL CLEAR
    $('#dcc-global-clear').on('click', function(e) {
        e.preventDefault();
        
        $('#dcc-keywords').val('');
        $('.dcc-filter-input').prop('checked', false);
        $('.dcc-filter-input').prop('indeterminate', false);
        
        $('#dcc-location-tags').empty();
        $('#dcc-category-tags').empty();
        activeLocations = [];
        activeCategories = [];
        
        // Reset Text
        $('.dcc-modal-trigger').each(function() {
            var target = $(this).data('target');
            var txt = 'Select Item';
            if(target.indexOf('category') > -1) txt = 'Select Category';
            if(target.indexOf('location') > -1) txt = 'Select Location';
            if(target.indexOf('train') > -1) txt = 'Select Line/Station';
            
            $(this).find('span').text(txt);
            $(this).removeClass('has-selection');
        });
        $('.dcc-clear-trigger').removeClass('active');
        $('.dcc-level-3-container, .dcc-level-4-container').hide();

        checkTrainDependency(); // Reset dependency
        dcc_fetch_jobs();
    });

    // FETCH FUNCTION
    function dcc_fetch_jobs() {
        var filters = [];
        var keywords = $('#dcc-keywords').val();

        $('#dcc-job-list').css('opacity', '0.5');

        // 1. Collect Checkboxes (Loc, Cat, Type, Train)
        $('input.dcc-filter-input:checked').each(function() {
            filters.push({ taxonomy: $(this).data('tax'), term: $(this).val() });
        });

        // 2. Collect Dropdown Tags
        if ( activeLocations.length > 0 ) {
            activeLocations.forEach(function(slug) {
                filters.push({ taxonomy: 'job_listing_region', term: slug });
            });
        }
        if ( activeCategories.length > 0 ) {
            activeCategories.forEach(function(slug) {
                filters.push({ taxonomy: 'job_listing_category', term: slug });
            });
        }

        $.ajax({
            url: dcc_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dcc_filter_jobs',
                security: dcc_vars.nonce,
                filters: filters,
                search_keywords: keywords
            },
            success: function(response) {
                $('#dcc-job-list').html(response.html);
                $('#dcc-results-count').text(response.count);
                $('#dcc-job-list').css('opacity', '1');
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    }
});