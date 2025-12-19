jQuery(document).ready(function($) {
    
    // 1. Prefecture Change -> Load Operators
    $('#dcc-train-l1').on('change', function() {
        var val = $(this).val();
        resetTrainLevel('l2'); resetTrainLevel('l3'); resetTrainLevel('l4');
        if (val) fetchTrainChildren(val, 'l2', 'select');
    });

    // 2. Operator Change -> Load Lines
    $('#dcc-train-l2').on('change', function() {
        var val = $(this).val();
        resetTrainLevel('l3'); resetTrainLevel('l4');
        if (val) fetchTrainChildren(val, 'l3', 'select');
    });

    // 3. Line Change -> Load Stations
    $('#dcc-train-l3').on('change', function() {
        var val = $(this).val();
        resetTrainLevel('l4');
        if (val) fetchTrainChildren(val, 'l4', 'checkbox');
    });

    // 4. Station Checkbox -> Add/Remove from Bucket
    $(document).on('change', '.dcc-train-check', function() {
        var id = $(this).val();
        var name = $(this).parent().text().trim();
        
        if ( $(this).is(':checked') ) {
            addToBucket(id, name);
        } else {
            removeFromBucket(id);
        }
    });

    // 5. Bucket Remove Click
    $(document).on('click', '.dcc-bucket-remove', function() {
        var $item = $(this).closest('.dcc-bucket-item');
        var id = $item.data('id');
        
        $item.remove();
        // Uncheck if visible
        $('.dcc-train-check[value="'+id+'"]').prop('checked', false);
        
        if ( $('#dcc-train-bucket').children('.dcc-bucket-item').length === 0 ) {
            $('#dcc-train-bucket').html('<span class="dcc-bucket-placeholder">No stations selected. Use the filters below to add.</span>');
        }
    });

    // 6. Search Filter
    $('.dcc-search-box').on('keyup', function() {
        var term = $(this).val().toLowerCase();
        var targetID = $(this).data('target');
        $('#' + targetID + ' label').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle( text.indexOf(term) > -1 );
        });
    });

    // --- HELPERS ---

    function addToBucket(id, name) {
        var $bucket = $('#dcc-train-bucket');
        $bucket.find('.dcc-bucket-placeholder').remove();
        if ( $bucket.find('.dcc-bucket-item[data-id="'+id+'"]').length > 0 ) return;

        var html = `
            <div class="dcc-bucket-item" data-id="${id}">
                ${name}
                <span class="dcc-bucket-remove">×</span>
                <input type="hidden" name="tax_input[job_listing_train][]" value="${id}">
            </div>
        `;
        $bucket.append(html);
    }

    function removeFromBucket(id) {
        var $bucket = $('#dcc-train-bucket');
        $bucket.find('.dcc-bucket-item[data-id="'+id+'"]').remove();
        if ( $bucket.children('.dcc-bucket-item').length === 0 ) {
            $bucket.html('<span class="dcc-bucket-placeholder">No stations selected. Use the filters below to add.</span>');
        }
    }

    function resetTrainLevel(key) {
        var $row = $('#dcc-t-row-' + key);
        if ( key === 'l4' ) {
            $('#dcc-train-list').empty();
            $row.find('input.dcc-search-box').val('');
        } else {
            $('#dcc-train-' + key).html('<option value="">Select...</option>');
        }
        $row.hide();
    }

    function fetchTrainChildren(parentID, levelKey, type) {
        var $row = $('#dcc-t-row-' + levelKey);
        var $target = (type === 'select') ? $('#dcc-train-' + levelKey) : $('#dcc-train-list');
        var $spinner = $row.find('.spinner');

        $row.show();
        $spinner.addClass('is-active');

        $.ajax({
            url: dcc_train_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dcc_get_term_children',
                security: dcc_train_vars.nonce,
                parent_id: parentID,
                taxonomy: 'job_listing_train' // <--- CRITICAL FIX: Tell PHP it's trains
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                
                if ( response.success && response.data.length > 0 ) {
                    if ( type === 'select' ) {
                        var opts = '<option value="">Select...</option>';
                        $.each(response.data, function(i, item){
                            opts += '<option value="'+item.id+'">'+item.name+'</option>';
                        });
                        $target.html(opts);
                    } else {
                        // Checkbox List (L4)
                        $target.empty();
                        var selectedIDs = [];
                        $('.dcc-bucket-item').each(function(){ selectedIDs.push( $(this).data('id') ); });

                        $.each(response.data, function(i, item){
                            var isChecked = selectedIDs.includes(item.id) ? 'checked' : '';
                            var html = `<label class="dcc-item-label">
                                <input type="checkbox" class="dcc-train-check" value="${item.id}" ${isChecked}> ${item.name}
                            </label>`;
                            $target.append(html);
                        });
                    }
                } else {
                    if(type === 'checkbox') $target.html('<div style="padding:10px; color:#999;">No items found.</div>');
                }
            }
        });
    }
});