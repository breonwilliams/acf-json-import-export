jQuery(document).ready(function($) {
    // Preview functionality
    $('#acf-preview-import').on('click', function(e) {
        e.preventDefault();
        
        var postId = $('#post_id').val();
        var jsonData = $('#acf_json_paste').val();
        var fileInput = $('#acf_json_file')[0];
        
        if (!postId) {
            alert(acfJsonImportExport.selectItem);
            return;
        }
        
        if (!jsonData && (!fileInput.files || !fileInput.files[0])) {
            alert(acfJsonImportExport.noInput);
            return;
        }
        
        // Show loading
        $('.acf-preview-container').html('<div class="notice notice-info"><p>' + acfJsonImportExport.loading + '</p></div>');
        
        var formData = new FormData();
        formData.append('action', 'acf_preview_import');
        formData.append('nonce', acfJsonImportExport.nonce);
        formData.append('post_id', postId);
        
        if (jsonData) {
            formData.append('json_data', jsonData);
        } else if (fileInput.files[0]) {
            formData.append('json_file', fileInput.files[0]);
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('.acf-preview-container').html(response.data.html);
                } else {
                    $('.acf-preview-container').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('.acf-preview-container').html('<div class="notice notice-error"><p>' + acfJsonImportExport.error + '</p></div>');
            }
        });
    });
    
    // Toggle backup option visibility
    $('#enable-backup').on('change', function() {
        if ($(this).is(':checked')) {
            $('.backup-options').show();
        } else {
            $('.backup-options').hide();
        }
    });
});