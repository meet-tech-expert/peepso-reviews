<div class="peepso ps-page-profile">
    <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

    <?php PeepSoTemplate::exec_template('profile', 'focus', array('current' => 'reviews')); ?>

    <section id="mainbody" class="ps-page-unstyled ps-reviews-profile">
        <section id="component" role="article" class="ps-clearfix">
            <?php $PeepSoUser = PeepSoUser::get_instance($view_user_id);
            $PeepSoUrlSegments = PeepSoUrlSegments::get_instance();
            // print_r($PeepSoUrlSegments->get_segments());
            $page = (get_query_var('paged')) ? get_query_var('paged') : 1;
            if ($PeepSoUrlSegments->get(3) == 'page') {
                $page = $PeepSoUrlSegments->get(4);
            }
            $limit = PeepSo::get_option('reviews_per_page', REVIEWS_PER_PAGE, TRUE);
            // var_dump($reviews_per_page);
            // exit;
            $args = array(
                'posts_per_page' => $limit,
                'paged'         => $page,
                'post_type'     => 'review',
                'meta_key'      => 'profile_user',
                'meta_value'    => $view_user_id
            );
            // query
            $the_query = new WP_Query($args);
            $totalReviews = $the_query->found_posts;
            $num_pages = ceil($totalReviews / $limit);
            ?>
            <div class="ps-review-btn">
                <div class="review-headline"><?php echo ($totalReviews == 0) ? sprintf(__('%s has yet to receive a review. Be the first to review %s.', 'peepsoreviews'), $PeepSoUser->get_fullname(), $PeepSoUser->get_fullname()) : '';
                                                ?></div>
                <?php if (is_user_logged_in() &&  get_current_user_id() != $PeepSoUser->get_id()) { ?>
                    <a href="#" class="ps-btn ps-btn--sm ps-btn--action review_btn" <?php if (is_user_logged_in()) { ?> onclick="openReviewFormModal(this);return false;" <?php } else { ?> onclick="openReviewLoginModal(this);return false;" <?php } ?> data-avatar="<?php echo $PeepSoUser->get_avatar('full'); ?>" data-name="<?php echo $PeepSoUser->get_fullname(); ?>"><?php echo __('Submit Review', 'peepsoreviews') ?></a>
                <?php } ?>
            </div>
            <?php
            $PeepSoReviews = PeepSoReviews::get_instance();
            ?>
            <div class="reviews-lists">
                <?php if ($the_query->have_posts()):
                    while ($the_query->have_posts()) : $the_query->the_post();
                        $post = $the_query->post;
                        $author_id = $post->post_author;
                        $PeepSoAuthor = PeepSoUser::get_instance($author_id);
                ?>
                        <div class="ps-post" id="post-review-<?php echo $post->ID; ?>">
                            <div class="ps-post__header">
                                <h4><?php the_title(); ?></h4>
                            </div>
                            <div class="ps-post__body">
                                <div class="review-meta">
                                    <div class="rating">
                                        <?php echo $PeepSoReviews->show_rating(get_field('review_rating')); ?>
                                    </div>
                                    <div class="time-ago">
                                        <?php echo $PeepSoReviews->relative_time(); ?>
                                    </div>
                                </div> <!-- review-meta -->
                                <?php $images = get_field('review_images');
                                if ($images) { ?>
                                    <div class="review-images">
                                        <?php // print_r($images);
                                        foreach ($images as $image_id) {
                                            echo '<a href="' . wp_get_attachment_image_url($image_id, 'full') . '" data-lightbox="review-images-' . $post->ID . '" data-title="' . get_the_title() . '"><img decoding="async" width="150" height="105" src="' . wp_get_attachment_image_url($image_id, 'adverts-upload-thumbnail') . '" class="attachment-adverts-upload-thumbnail size-adverts-upload-thumbnail ls-is-cached lazyloaded" alt="" data-src="' . wp_get_attachment_image_url($image_id, 'adverts-upload-thumbnail') . '" data-eio-rwidth="150" data-eio-rheight="105" data-src-webp="' . wp_get_attachment_image_url($image_id, 'adverts-upload-thumbnail') . '"></a>';
                                        }
                                        ?>
                                    </div>

                                <?php } ?>
                                <div class="reviews-content">
                                    <p><?php echo get_the_content(); ?></p>
                                </div>
                            </div>
                            <div class="ps-post__footer">
                                <div class="ps-post__actions-inner">
                                    <a class="ps-avatar ps-avatar--post" href="<?php echo $PeepSoAuthor->get_profileurl(); ?>" target="_blank">
                                        <img src="<?php echo $PeepSoAuthor->get_avatar('full'); ?>" alt="<?php echo $PeepSoAuthor->get_fullname(); ?> avatar">
                                    </a>
                                    <a class="ps-tag__link ps-csr" href="<?php echo $PeepSoAuthor->get_profileurl(); ?>" data-hover-card="<?php echo $author_id; ?>" target="_blank"><?php echo $PeepSoAuthor->get_fullname(); ?></a>
                                </div>
                            </div>
                        </div>
                <?php endwhile;
                endif;
                wp_reset_query(); ?>
            </div>
            <div class="pagination-wrapper">
                <?php
                // Pagination links
                $big = 999999999; // need an unlikely integer
                echo paginate_links(array(
                    'base'    => str_replace('%2F', '/', str_replace($big, '%#%', get_pagenum_link($big, false))),
                    'format'  => '?paged=%#%',
                    'current' => max(1, $page),
                    'total'   => $num_pages,
                    'type'      => 'list',
                    'prev_text' => '<i class="fa fa-angle-left"></i>',
                    'next_text'    => '<i class="fa fa-angle-right"></i>',
                    'end_size' => 1,
                    'mid_size' => 2
                ));

                $from = ($limit * $page) - ($limit - 1);
                if (($limit * $page) <= ($totalReviews)) {
                    $to = ($limit * $page);
                } else {
                    $to = $totalReviews;
                }
                if ($from == $to) {
                    $from_to = $from;
                } else {
                    $from_to = $from . ' - ' . $to;
                }
                ?>
                <div class="paginate-info"><?php echo $from_to . ' of ' . $totalReviews; ?> reviews</div>
            </div>
</div>

</section><!--end component-->
</section><!--end mainbody-->
</div><!--end row-->
<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>