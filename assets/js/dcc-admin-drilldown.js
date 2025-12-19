jQuery(document).ready(function($) {
    
    // --- INIT ---
    var l1_val = $('#dcc-region-l1').val();
    if ( l1_val ) {
        var l2_saved = $('#dcc-row-l2').data('selected');
        
        fetchChildren( l1_val, 'l2', 'radio', function() {
            if ( l2_saved ) {
                $('input[name="dcc_radio_l2"][value="'+l2_saved+'"]').prop('checked', true);
                
                fetchChildren( l2_saved, 'l3', 'checkbox', function() {
                    $('.dcc-saved-term').each(function() {
                        var id = $(this).val();
                        $('input.dcc-ward-check[value="'+id+'"]').prop('checked', true);
                    });
                });
            }
        });
    }

    // --- EVENTS ---
    $('#dcc-region-l1').on('change', function() {
        var val = $(this).val();
        resetLevel('l2'); resetLevel('l3'); syncSubmission();
        if ( val ) fetchChildren( val, 'l2', 'radio' );
    });

    $(document).on('change', 'input[name="dcc_radio_l2"]', function() {
        var val = $(this).val();
        resetLevel('l3'); syncSubmission();
        fetchChildren( val, 'l3', 'checkbox', function( count ) {
            if( count === 0 ) $('#dcc-row-l3').hide();
        });
    });

    $(document).on('change', 'input.dcc-ward-check', function() {
        syncSubmission();
    });

    $('.dcc-search-box').on('keyup', function() {
        var term = $(this).val().toLowerCase();
        var targetID = $(this).data('target');
        $('#' + targetID + ' label').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle( text.indexOf(term) > -1 );
        });
    });

    // --- HELPERS ---
    function fetchChildren( parentID, levelID, inputType, callback ) {
        var $row = $('#dcc-row-' + levelID);
        var $list = $('#dcc-list-' + levelID);
        
        $row.show();
        $list.html('<span class="spinner is-active" style="float:none; margin:10px;"></span>Loading...');

        $.ajax({
            url: dcc_admin_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dcc_get_term_children',
                security: dcc_admin_vars.nonce,
                parent_id: parentID,
                taxonomy: 'job_listing_region' // EXPLICIT
            },
            success: function(response) {
                $list.empty();
                
                if ( response.success && response.data.length > 0 ) {
                    $.each( response.data, function( i, item ) {
                        var inputHTML = '';
                        if ( inputType === 'radio' ) {
                            inputHTML = '<input type="radio" name="dcc_radio_'+levelID+'" value="'+item.id+'"> ' + item.name;
                        } else {
                            inputHTML = '<input type="checkbox" class="dcc-ward-check" value="'+item.id+'"> ' + item.name;
                        }
                        $list.append('<label class="dcc-item-label">' + inputHTML + '</label>');
                    });
                    if ( callback ) callback( response.data.length );
                } else {
                    $list.html('<div style="padding:10px; color:#999;">No sub-regions found.</div>');
                    if ( callback ) callback( 0 );
                }
            }
        });
    }

    function resetLevel( levelID ) {
        $('#dcc-row-' + levelID).hide();
        $('#dcc-list-' + levelID).empty();
        $('#dcc-row-' + levelID + ' input.dcc-search-box').val('');
    }

    function syncSubmission() {
        var $container = $('#dcc-submission-container');
        $container.empty();
        $container.append('<input type="hidden" name="tax_input[job_listing_region][]" value="">');

        var l3_checked = $('input.dcc-ward-check:checked');
        var l2_checked = $('input[name="dcc_radio_l2"]:checked');
        var l1_val     = $('#dcc-region-l1').val();

        if ( l3_checked.length > 0 ) {
            l3_checked.each(function() { addHidden( $(this).val() ); });
        } else if ( l2_checked.length > 0 ) {
            addHidden( l2_checked.val() );
        } else if ( l1_val ) {
            addHidden( l1_val );
        }
    }

    function addHidden( val ) {
        $('#dcc-submission-container').append('<input type="hidden" name="tax_input[job_listing_region][]" value="'+val+'">');
    }
});