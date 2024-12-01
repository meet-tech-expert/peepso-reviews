<?php
require_once(PeepSo::get_plugin_dir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'install.php');

class PeepSoReviewsInstall extends PeepSoInstall
{

	// optional default settings
	protected $default_config = array(
		#'HELLO_WORLD' => '100',
	);

	public function plugin_activation($is_core = FALSE)
	{
		// Set some default settings
		$settings = PeepSoConfigSettings::get_instance();
		$settings->set_option('reviews_profiles_enable', 1);
		// $settings->set_option('reviews_example_int', 1);
		// $settings->set_option('reviews_example_text', 'Custom Hello World!');
		// $settings->set_option('reviews_example_dropdown', 'two');


		parent::plugin_activation($is_core);

		return (TRUE);
	}

	// optional DB table creation
	public static function get_table_data()
	{
		$aRet = array(
			'hello' => "
				CREATE TABLE reviews (
					rvw_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					PRIMARY KEY (rvw_id),
				) ENGINE=InnoDB",
		);

		return $aRet;
	}

	// optional notification emails
	public function get_email_contents()
	{
		$emails = array(
			'email_review_message' => "Hi {userfullname},

Review From: {fromfullname}

Title: {review_title}

Star Rating: {star_rating}

Feedback: {review_experience}

Images: {review_images} 

Thank you.",
		);

		return $emails;
	}

	// optional page with shortcode
	protected function get_page_data()
	{
		// default page names/locations
		$aRet = array(
			'reviews' => array(
				'title' => __('PeepSo Reviews', 'peepsoreviews'),
				'slug' => 'reviews',
				'content' => '[peepso_hello]'
			),
		);

		return ($aRet);
	}
}
