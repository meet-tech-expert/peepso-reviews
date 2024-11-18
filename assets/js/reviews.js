var data = peepsowindowdata || {};
function openReviewLoginModal(e){
    // console.log('openReviewLoginModal', e);
    let actions = [
        '<button type="button" class="ps-btn ps-btn--sm" onclick="pswindow.hide(); return false;">'+data.label_cancel+'</button>',
    ].join(' ');
    pswindow.show('<img src="' + jQuery(e).data("avatar") + '" width="50" />', peepsoreviewsdata.login_texts).set_actions(actions);
}
function openReviewFormModal(e){
    console.log('openReviewFormModal');
    let actions = [
        '<button type="button" class="ps-btn ps-btn--sm" onclick="pswindow.hide(); return false;">'+data.label_cancel+'</button>',
        '<button type="button" class="ps-btn ps-btn--sm ps-btn--action" id="submitReview">'+peepsoreviewsdata.submit_label+' <img src="'+peepsoreviewsdata.loading_gif+'" class="ps-js-loading" alt="loading" style="padding-left:5px;display:none;"></button>',
    ].join(' ');
    let _html = peepsoreviewsdata.form_template;
    pswindow.show('<img src="' + jQuery(e).data("avatar") + '" width="50" />', _html).set_actions(actions);
    formScript();
}
function submitReview(title,rating,description){
    console.log('submitReview clicked!');
    jQuery.ajax({
        url     : peepsoreviewsdata.ajaxurl_legacy + 'reviewsajax.submitreview',
        method  : 'POST',
        dataType: "json",
        data: {
            title: title,
            rating: rating,
            description: description,
            view_user_id: peepsodata.userid
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-PeepSo-Nonce', peepsoreviewsdata.peepso_nonce);
        },
        success: function(response) {
            console.log(response);
            if(response.success){
                pswindow.hide()
                window.location.href = response.data.redirect_url;
            }else{
                jQuery("#submitReview").removeAttr('disabled');
                jQuery('#submitReview').find('.ps-js-loading').hide();
                alert(response.errors.join('\n'));
            }
            
        },
        error: function(){

        }
    });
}
function formScript(){
    jQuery('input[name="rating"]').on('change',function(){
          jQuery('.ps-reviews-rating .label span').text( this.value);
          jQuery("#submitReview").removeAttr('disabled');
          jQuery(".ps-reviews-rating").find('.ps-error').remove();
        } 
    );
    Dropzone.options.psReviewsImage = { // camelized version of the `id`
        paramName: "filedata", // The name that will be used to transfer the file
        maxFilesize: peepsoreviewsdata.image_max_size, // MB  
        url: peepsoreviewsdata.ajaxurl_legacy + 'reviewsajax.submitreview', 
        headers: { "X-PeepSo-Nonce": peepsoreviewsdata.peepso_nonce },
        uploadMultiple: false,
        maxFiles: peepsoreviewsdata.image_max_files,
        parallelUploads:peepsoreviewsdata.image_max_files,
        acceptedFiles: 'image/*', 
        autoProcessQueue: false,
        uploadMultiple: true,
        addRemoveLinks: true,
        dictFileTooBig: peepsoreviewsdata.file_size_error_txt,
        init: function() {
            var submitButton = document.querySelector("#submitReview"),myDropzone = this;
            submitButton.addEventListener("click", function() {
                console.log("submitReview");
                let title = jQuery.trim(jQuery("#reviews_title").val());
                let rating = jQuery("input[name='rating']:checked").val()
                let description = jQuery.trim(jQuery("#reviews_description").val());
                // console.log(title,rating,description);
                if(title !='' && title.length > peepsoreviewsdata.max_char_title ){
                    jQuery(".ps-reviews-title").find('.ps-info').hide();
                    jQuery(".ps-reviews-title").find('.ps-input').addClass('input-error');
                    if(jQuery(".ps-reviews-title").find('.ps-error').length == 0){
                        jQuery(".ps-reviews-title").append("<span class='ps-error'>"+peepsoreviewsdata.title_error_txt+"</span>");
                    }
                    return false;
                }else{
                    jQuery(".ps-reviews-title").find('.ps-info').show();
                    jQuery(".ps-reviews-title").find('.ps-input').removeClass('input-error');
                    jQuery(".ps-reviews-title").find('.ps-error').remove();
                }
                if(typeof rating == 'undefined'){
                    if(jQuery(".ps-reviews-rating").find('.ps-error').length == 0){
                        jQuery(".ps-reviews-rating").append("<span class='ps-error'>"+peepsoreviewsdata.rating_error_txt+"</span>");
                    }
                    return false;
                }else{
                    jQuery(".ps-reviews-rating").find('.ps-error').remove();
                }
                if(description !='' && description.length > peepsoreviewsdata.max_char_desc ){
                    jQuery(".ps-reviews-desc").find('.ps-info').hide();
                    jQuery(".ps-reviews-desc").find('.ps-input').addClass('input-error');
                    if(jQuery(".ps-reviews-desc").find('.ps-error').length == 0){
                        jQuery(".ps-reviews-desc").append("<span class='ps-error'>"+peepsoreviewsdata.desc_error_txt+"</span>");
                    }
                    return false;
                }else{
                    jQuery(".ps-reviews-desc").find('.ps-info').show();
                    jQuery(".ps-reviews-desc").find('.ps-input').removeClass('input-error');
                    jQuery(".ps-reviews-desc").find('.ps-error').remove();
                }
                
                jQuery(this).find('.ps-js-loading').show();
                jQuery("#submitReview").attr('disabled','disabled');
                console.log(myDropzone.getQueuedFiles().length);
                if(myDropzone.getQueuedFiles().length == 0){
                    submitReview(title,rating, description)
                }
                myDropzone.processQueue(); 
            });
        },
        sendingmultiple: function(file, xhr, formData){
            console.log('sending file');
            jQuery('.dz-remove').hide();
            let title = jQuery.trim(jQuery("#reviews_title").val());
            let rating = jQuery("input[name='rating']:checked").val()
            let description = jQuery.trim(jQuery("#reviews_description").val());
            formData.append('title', title);
            formData.append('rating', rating);
            formData.append('description', description);
            formData.append('view_user_id', peepsodata.userid);
        },
        successmultiple: function(file){
            console.log('success',file);
            let response = JSON.parse(file[0].xhr.responseText)
            if(response.success == 1){
                pswindow.hide();
                window.location.href = response.data.redirect_url
            }
        },
        error: function(file, message) {
            console.log(file,message)
            alert(message)
            this.removeFile(file);
        },
        accept: function(file, done) {
            // console.log(file)
            done();
        },
      };
      Dropzone.discover();
}

jQuery(document).ready(function() {
    operateReadMore(56);
});
function operateReadMore(max_height){
    jQuery(".reviews-content").each(function(i,k){ 
        let contentHeight = jQuery(this).find('p').innerHeight(); 
        // console.log(contentHeight); 
        if(contentHeight > max_height ){ 
            jQuery(this).addClass('truncated'); 
            if( jQuery(this).find('span.read_more').length == 0 && jQuery(this).find('span.read_less').length == 0 ){
                jQuery(this).append('<span class="read_more">Read more</span>'); 
                jQuery(this).append('<span class="read_less hide">Read less</span>'); 
            }else{
                jQuery(this).find('span.read_more').removeClass('hide');
                jQuery(this).find('span.read_less').addClass('hide');
            }
            let that = this;
            jQuery(this).find('span.read_more').on('click',function(){
                console.log('read_more clicked');
                jQuery(this).addClass('hide');
                jQuery(that).find('span.read_less').removeClass('hide'); 
                jQuery(that).removeClass('truncated'); 
            });
            jQuery(this).find('span.read_less').on('click',function(){
                console.log('read_less clicked');
                jQuery(this).addClass('hide');
                jQuery(that).find('span.read_more').removeClass('hide'); 
                jQuery(that).addClass('truncated'); 
            }); 
        }
    });
}
window.addEventListener('resize', function(event) {
    //console.log('calles')
    if(window.innerWidth >= '768'){
        operateReadMore(56);
    }else{
        operateReadMore(45);
    }
}, true);