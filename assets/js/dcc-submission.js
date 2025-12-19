jQuery(document).ready(function($) {
    
    // =======================================================
    // 1. REGION DRILLDOWN LOGIC
    // =======================================================
    
    // Init (Pre-load if editing)
    var r_l1 = $('#dcc-fe-region-l1').val();
    if( r_l1 ) {
        var r_l2_saved = $('#dcc-fe-row-l2').data('selected');
        fetchRegion(r_l1, 'l2', 'radio', function() {
            if(r_l2_saved) {
                $('input[name="dcc_fe_radio_l2"][value="'+r_l2_saved+'"]').prop('checked', true);
                fetchRegion(r_l2_saved, 'l3', 'checkbox', function() {
                    $('.dcc-saved-region').each(function() {
                        $('input.dcc-fe-ward-check[value="'+$(this).val()+'"]').prop('checked', true);
                    });
                });
            }
        });
    }

    $('#dcc-fe-region-l1').on('change', function() {
        var val = $(this).val();
        resetRegion('l2'); resetRegion('l3'); syncRegion();
        if(val) fetchRegion(val, 'l2', 'radio');
    });

    $(document).on('change', 'input[name="dcc_fe_radio_l2"]', function() {
        var val = $(this).val();
        resetRegion('l3'); syncRegion();
        fetchRegion(val, 'l3', 'checkbox', function(cnt){ if(cnt===0) $('#dcc-fe-row-l3').hide(); });
    });

    $(document).on('change', 'input.dcc-fe-ward-check', function() { syncRegion(); });

    function fetchRegion(pid, level, type, cb) {
        var $row = $('#dcc-fe-row-'+level);
        var $list = $('#dcc-fe-list-'+level);
        $row.show();
        $list.html('<span class="dcc-loader">Loading...</span>');

        $.ajax({
            url: dcc_submission_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'dcc_get_term_children', security: dcc_submission_vars.nonce, parent_id: pid, taxonomy: 'job_listing_region' },
            success: function(res) {
                $list.empty();
                if(res.success && res.data.length > 0) {
                    $.each(res.data, function(i, item) {
                        var input = (type==='radio') 
                            ? '<input type="radio" name="dcc_fe_radio_l2" value="'+item.id+'"> ' 
                            : '<input type="checkbox" class="dcc-fe-ward-check" value="'+item.id+'"> ';
                        $list.append('<label>'+input+item.name+'</label>');
                    });
                    if(cb) cb(res.data.length);
                } else {
                    $list.html('<small>No sub-regions.</small>');
                    if(cb) cb(0);
                }
            }
        });
    }

    function resetRegion(level) {
        $('#dcc-fe-row-'+level).hide();
        $('#dcc-fe-list-'+level).empty();
    }

    function syncRegion() {
        var $col = $('#dcc-fe-region-collector').empty();
        $col.append('<input type="hidden" name="job_region[]" value="">');
        
        var l3 = $('input.dcc-fe-ward-check:checked');
        var l2 = $('input[name="dcc_fe_radio_l2"]:checked');
        var l1 = $('#dcc-fe-region-l1').val();

        if(l3.length > 0) l3.each(function(){ $col.append('<input type="hidden" name="job_region[]" value="'+$(this).val()+'">'); });
        else if(l2.length > 0) $col.append('<input type="hidden" name="job_region[]" value="'+l2.val()+'">');
        else if(l1) $col.append('<input type="hidden" name="job_region[]" value="'+l1+'">');
    }


    // =======================================================
    // 2. TRAIN PICKER LOGIC
    // =======================================================
    
    $('#dcc-fe-train-l1').on('change', function() {
        var val = $(this).val();
        resetTrain('l2'); resetTrain('l3'); resetTrain('l4');
        if(val) fetchTrain(val, 'l2', 'select');
    });

    $('#dcc-fe-train-l2').on('change', function() {
        var val = $(this).val();
        resetTrain('l3'); resetTrain('l4');
        if(val) fetchTrain(val, 'l3', 'select');
    });

    $('#dcc-fe-train-l3').on('change', function() {
        var val = $(this).val();
        resetTrain('l4');
        if(val) fetchTrain(val, 'l4', 'checkbox');
    });

    $(document).on('change', '.dcc-fe-train-check', function() {
        var id = $(this).val();
        var name = $(this).parent().text().trim();
        if($(this).is(':checked')) addTrainBucket(id, name);
        else removeTrainBucket(id);
    });

    $(document).on('click', '.dcc-fe-remove', function() {
        var id = $(this).parent().data('id');
        $(this).parent().remove();
        $('.dcc-fe-train-check[value="'+id+'"]').prop('checked', false);
        if($('#dcc-fe-train-bucket .dcc-fe-bucket-item').length === 0) 
            $('#dcc-fe-train-bucket').html('<span class="dcc-fe-placeholder">No stations selected.</span>');
    });

    // Search Filter
    $('.dcc-fe-search').on('keyup', function() {
        var term = $(this).val().toLowerCase();
        var target = $(this).data('target');
        $('#'+target+' label').each(function() {
            $(this).toggle( $(this).text().toLowerCase().indexOf(term) > -1 );
        });
    });

    function fetchTrain(pid, level, type) {
        var $row = $('#dcc-fet-row-'+level);
        var $target = (type==='select') ? $('#dcc-fe-train-'+level) : $('#dcc-fe-train-list');
        $row.show();

        $.ajax({
            url: dcc_submission_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'dcc_get_term_children', security: dcc_submission_vars.nonce, parent_id: pid, taxonomy: 'job_listing_train' },
            success: function(res) {
                if(res.success && res.data.length > 0) {
                    if(type==='select') {
                        var opts = '<option value="">Select...</option>';
                        $.each(res.data, function(i, item){ opts += '<option value="'+item.id+'">'+item.name+'</option>'; });
                        $target.html(opts);
                    } else {
                        $target.empty();
                        var used = [];
                        $('.dcc-fe-bucket-item').each(function(){ used.push($(this).data('id')); });
                        $.each(res.data, function(i, item){
                            var checked = used.includes(item.id) ? 'checked' : '';
                            $target.append('<label><input type="checkbox" class="dcc-fe-train-check" value="'+item.id+'" '+checked+'> '+item.name+'</label>');
                        });
                    }
                }
            }
        });
    }

    function resetTrain(level) {
        var $row = $('#dcc-fet-row-'+level);
        $row.hide();
        if(level === 'l4') $('#dcc-fe-train-list').empty();
        else $('#dcc-fe-train-'+level).html('<option value="">Select...</option>');
    }

    function addTrainBucket(id, name) {
        var $b = $('#dcc-fe-train-bucket');
        $b.find('.dcc-fe-placeholder').remove();
        if($b.find('.dcc-fe-bucket-item[data-id="'+id+'"]').length === 0) {
            $b.append('<div class="dcc-fe-bucket-item" data-id="'+id+'">'+name+'<span class="dcc-fe-remove">×</span><input type="hidden" name="job_train[]" value="'+id+'"></div>');
        }
    }
    
    function removeTrainBucket(id) {
        var $b = $('#dcc-fe-train-bucket');
        $b.find('.dcc-fe-bucket-item[data-id="'+id+'"]').remove();
        if($b.find('.dcc-fe-bucket-item').length === 0) 
            $b.html('<span class="dcc-fe-placeholder">No stations selected.</span>');
    }


    // =======================================================
    // 3. CATEGORY MODAL LOGIC (New!)
    // =======================================================
    
    // Open Modal
    $('.dcc-open-cat-modal').on('click', function(e) {
        e.preventDefault();
        $('body').css('overflow', 'hidden'); // Lock scroll
        $('#dcc-fe-cat-modal').fadeIn(200).css('display','flex');
    });

    // Close Modal
    $('.dcc-fe-modal-close, .dcc-fe-modal-close-icon').on('click', function(e) {
        e.preventDefault();
        $('body').css('overflow', 'auto'); // Unlock scroll
        $('#dcc-fe-cat-modal').fadeOut(200);
    });

    // Checkbox Change -> Update Bucket
    $(document).on('change', '.dcc-fe-cat-check', function() {
        var id = $(this).val();
        var name = $(this).data('name');
        
        if($(this).is(':checked')) {
            addCatBucket(id, name);
        } else {
            removeCatBucket(id);
        }
    });

    // Bucket Remove
    $(document).on('click', '.dcc-fe-remove-cat', function() {
        var id = $(this).parent().data('id');
        $(this).parent().remove();
        $('.dcc-fe-cat-check[value="'+id+'"]').prop('checked', false);
        if($('#dcc-fe-cat-bucket .dcc-fe-bucket-item').length === 0) 
            $('#dcc-fe-cat-bucket').html('<span class="dcc-fe-placeholder">No categories selected.</span>');
    });

    function addCatBucket(id, name) {
        var $b = $('#dcc-fe-cat-bucket');
        $b.find('.dcc-fe-placeholder').remove();
        if($b.find('.dcc-fe-bucket-item[data-id="'+id+'"]').length === 0) {
            $b.append('<div class="dcc-fe-bucket-item" data-id="'+id+'">'+name+'<span class="dcc-fe-remove-cat">×</span><input type="hidden" name="job_category[]" value="'+id+'"></div>');
        }
    }

    function removeCatBucket(id) {
        var $b = $('#dcc-fe-cat-bucket');
        $b.find('.dcc-fe-bucket-item[data-id="'+id+'"]').remove();
        if($b.find('.dcc-fe-bucket-item').length === 0) 
            $b.html('<span class="dcc-fe-placeholder">No categories selected.</span>');
    }

});