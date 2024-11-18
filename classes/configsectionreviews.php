<?php

class PeepSoConfigSectionReviews extends PeepSoConfigSectionAbstract
{
    // Builds the groups array
    public function register_config_groups()
    {
        $this->context = 'left';
        $this->profiles();

        $this->context = 'right';
        $this->formsettings();
    }


    /**
     * General Settings Box
     */
    private function profiles()
    {
        // # Enable profile integration
        $this->args('descript', __('Enabled: Reviews tab will show in user profiles.', 'peepsoreviews'));
        $this->set_field(
            'reviews_profiles_enable',
            __('Enabled', 'peepsoreviews'),
            'yesno_switch'
        );

        // # Show to owner only
        // $this->args('descript', __('Enabled: users will see the Reviews tab only on their own profiles.', 'peepsoreviews'));
        // $this->set_field(
        //     'reviews_profiles_owner_only',
        //     __('Profile owner only', 'peepsoreviews'),
        //     'yesno_switch'
        // );

        // # Label
        $this->args('descript', __('Leave empty for default.', 'peepsoreviews'));
        $this->set_field(
            'reviews_profiles_label',
            __('Menu label', 'peepsoreviews'),
            'text'
        );

        // # Slug
        $this->args('descript', __('Leave empty for default. Be careful not to use a slug that is already taken, for example "photos", "videos" etc.', 'peepsoreviews'));
        $this->set_field(
            'reviews_profiles_slug',
            __('Menu slug', 'peepsoreviews'),
            'text'
        );

        // # Icon
        $this->args('descript', __('Icon CSS class override. Leave empty for default', 'peepsoreviews'));
        $this->set_field(
            'reviews_profiles_icon',
            __('Menu icon', 'peepsoreviews'),
            'text'
        );


        $this->set_group(
            'peepso_reviews_general',
            __('Profile integration', 'peepsoreviews')
        );
    }

    /**
     * Custom Greeting Box
     */
    private function formsettings()
    {
        $this->args('int', TRUE);
        // $this->args('validation', array('required', 'numeric'));

        // If we didn't specify a default during plugin activation, we can do it now
        $this->args('default', REVIEWS_MAX_UPLOAD_SIZE);
        $this->args('descript', __('Leave empty for default.Set max upload file size.', 'peepsoreviews'));
        $this->set_field(
            'reviews_image_max_size',
            __('Max image size', 'peepsoreviews'),
            'text'
        );

        $this->args('int', TRUE);
        $this->args('default', REVIEWS_MAX_FILES);
        $this->args('descript', __('Leave empty for default. Set max files allowed per review.', 'peepsoreviews'));
        $this->set_field(
            'reviews_image_max_files',
            __('Max image files', 'peepsoreviews'),
            'text'
        );

        $this->args('int', TRUE);
        $this->args('default', REVIEWS_MAX_CHAR_TITLE);
        $this->args('descript', __('Leave empty for default. Set max characters allowed for title.', 'peepsoreviews'));
        $this->set_field(
            'reviews_max_char_title',
            __('Max characters for title', 'peepsoreviews'),
            'text'
        );

        $this->args('int', TRUE);
        $this->args('default', REVIEWS_MAX_CHAR_DESC);
        $this->args('descript', __('Leave empty for default. Set max characters allowed for description.', 'peepsoreviews'));
        $this->set_field(
            'reviews_max_char_desc',
            __('Max characters for description', 'peepsoreviews'),
            'text'
        );

        $this->args('int', TRUE);
        $this->args('default', REVIEWS_PER_PAGE);
        $this->args('descript', __('Leave empty for default. Set limit for reviews listing.', 'peepsoreviews'));
        $this->set_field(
            'reviews_per_page',
            __('Reviews Per Page', 'peepsoreviews'),
            'text'
        );


        $this->set_group(
            'peepso_reviews_settings',
            __('Form fields settings', 'peepsoreviews')
        );
    }
}
