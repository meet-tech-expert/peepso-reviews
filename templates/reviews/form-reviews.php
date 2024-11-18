<form class="ps-form ps-reviews-form" role="form" id="ps-reviews-form">
    <div class="ps-input-wrapper ps-reviews-title"><input type="text" name="reviews_title" id="reviews_title" placeholder="<?php echo __('Title', 'peepsoreviews') ?>" class="ps-input" /><span class="ps-info"><?php echo sprintf(__('Max %d characters allowed.', 'peepsoreviews'), $max_char_title); ?></span></div>

    <div class="ps-input-wrapper ps-reviews-rating">
        <span class="label"><?php echo __('Choose a rating', 'peepsoreviews') ?> (<span>0</span>)</span><span class="star-rating"><input type="radio" name="rating" value="1"><i></i><input type="radio" name="rating" value="2"><i></i><input type="radio" name="rating" value="3"><i></i><input type="radio" name="rating" value="4"><i></i><input type="radio" name="rating" value="5"><i></i></span>
    </div>

    <div class="ps-input-wrapper ps-reviews-image dropzone" id="ps-reviews-image">
        <div class="dz-message" data-dz-message><?php echo sprintf(__('Drag & Drop images or %sBrowse%s', 'peepsoreviews'), '<span>', '</span>'); ?></div>
        <span class="ps-info"><?php echo sprintf(__('Max %d images are allowed.Size should not exceed than %d MB.', 'peepsoreviews'), $max_files, $max_size); ?></span>
    </div>

    <div class="ps-input-wrapper ps-reviews-desc">
        <textarea name="reviews_description" id="reviews_description" placeholder="<?php echo __('Share your experience', 'peepsoreviews') ?>" class="ps-input ps-textarea ps-postbox-textarea ps-tagging-textarea"></textarea><span class="ps-info"><?php echo sprintf(__('Max %d characters allowed.', 'peepsoreviews'), $max_char_desc); ?></span>
    </div>
</form>