<?php
    echo $args['before_widget'];
    $owner = PeepSoUser::get_instance($instance['user_id']);
?>

<div class="ps-widget__wrapper<?php echo $instance['class_suffix'];?> ps-widget<?php echo $instance['class_suffix'];?>">
    <div class="ps-widget__header<?php echo $instance['class_suffix'];?>">
        <a href="#"><?php
                if ( ! empty( $instance['title'] ) ) {
                    echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
                }
        ?></a>
    </div>
    <div class="ps-widget__body<?php echo $instance['class_suffix'];?>">
        <?php echo $instance['hello']; ?>
    </div>
</div>

<?php

echo $args['after_widget'];

// EOF
