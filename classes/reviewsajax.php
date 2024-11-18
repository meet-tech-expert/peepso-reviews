<?php

class PeepSoReviewsAjax extends PeepSoAjaxCallback
{
	public function submitreview(PeepSoAjaxResponse $resp)
	{

		$title 			= $_POST['title'];
		$rating 		= $this->_input->int('rating', 0, FALSE);
		$view_user_id   = $this->_input->int('view_user_id', 0, FALSE);
		$description 	= $_POST['description'];
		$title_limit    = PeepSo::get_option('reviews_max_char_title', REVIEWS_MAX_CHAR_TITLE, TRUE);
		$desc_limit     = PeepSo::get_option('reviews_max_char_desc', REVIEWS_MAX_CHAR_DESC, TRUE);
		$max_image_upload_size = PeepSo::get_option('reviews_image_max_size', REVIEWS_MAX_UPLOAD_SIZE, TRUE);
		$max_image_files = PeepSo::get_option('reviews_image_max_files', REVIEWS_MAX_FILES, TRUE);
		$profile_slug   = PeepSo::get_option('reviews_profiles_slug', 'reviews', TRUE);
		$PeepSoUser = PeepSoUser::get_instance($view_user_id);
		// var_dump($title);var_dump($rating);var_dump($description);
		if ($title != '' && strlen($title) > $title_limit) {
			$resp->error(sprintf(__('Title must be less than %1$d characters.', 'peepsoreviews'), $title_limit));
			$resp->success(FALSE);
			return;
		}
		if ($description != '' && strlen($description) > $desc_limit) {
			$resp->error(sprintf(__('Description must be less than %1$d characters.', 'peepsoreviews'), $desc_limit));
			$resp->success(FALSE);
			return;
		}
		// $user = PeepSoUser::get_instance(get_current_user_id());
		$args = array(
			'post_type' 	=> 'review',
			'post_title'    => sanitize_text_field($title),
			'post_content'  => sanitize_textarea_field($description),
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
		);
		$post_id = wp_insert_post($args);
		if (!is_wp_error($post_id)) {
			//the post is valid
			update_field('review_rating', $rating, $post_id);
			update_field('profile_user', $view_user_id, $post_id);
			if (count($_FILES) == 0 && !isset($_FILES['filedata'])) {
				$resp->success(TRUE);
				$resp->set('post_id', $post_id);
				$resp->set('redirect_url', $PeepSoUser->get_profileurl() . $profile_slug);
				$resp->set('msg', __('Your review has been submitted!', 'peepsoreviews'));

				do_action('peepso_profile_new_review', $view_user_id, $post_id);
			}
		} else {
			//there was an error in the post insertion, 
			$resp->error($post_id->get_error_message());
			$resp->success(FALSE);
			return;
		}
		if (count($_FILES) > 0 && isset($_FILES['filedata'])) {
			// Including file library if not exist
			if (!function_exists('wp_handle_upload')) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			// print_r($_FILES);
			$attachments = array();
			foreach ($_FILES['filedata']['tmp_name'] as $key => $value) {
				if ($_FILES['filedata']['size'][$key] >= $max_image_upload_size * 1048576) {
					$resp->error(sprintf(__('Only files up to %1$dMB are allowed', 'peepsoreviews'), $max_image_upload_size));
					$resp->success(FALSE);
					return;
				}
			}
			$files = $_FILES['filedata'];
			$file_errors = array();
			// print_r($_FILES['filedata']);
			foreach ($files['name'] as $key => $value) {
				if ($files['name'][$key]) {
					$file = array(
						'name' 		=> $files['name'][$key],
						'type' 		=> $files['type'][$key],
						'tmp_name' 	=> $files['tmp_name'][$key],
						'error' 	=> $files['error'][$key],
						'size' 		=> $files['size'][$key]
					);
					// Uploading file to server
					$movefile = wp_handle_upload($file, ['test_form' => false]);
					// print_r($movefile);
					// If uploading success & No error
					if ($movefile && !isset($movefile['error'])) {
						$filename = $movefile['file'];
						$filetype = wp_check_filetype(basename($filename), null);
						$wp_upload_dir = wp_upload_dir();

						$attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
							'post_mime_type' => $filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
							'post_content' => '',
							'post_status' => 'inherit',
						);

						// Adding file to media
						$attach_id = wp_insert_attachment($attachment, $filename);
						// If attachment success
						if ($attach_id) {
							require_once ABSPATH . 'wp-admin/includes/image.php';
							// Updating attachment metadata
							$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
							wp_update_attachment_metadata($attach_id, $attach_data);
							$attachments[] = $attach_id;
						}
					} else {
						$file_errors[] = $movefile['error'];
					}
				}
			}
			if (!empty($file_errors)) {
				$resp->error(implode('\n', $file_errors));
				$resp->success(FALSE);
				return;
			}
			update_field('review_images', $attachments, $post_id);
		}
		$resp->success(TRUE);
		$resp->set('post_id', $post_id);
		$resp->set('redirect_url', $PeepSoUser->get_profileurl() . $profile_slug);
		$resp->set('msg', __('Your review has been submitted!', 'peepsoreviews'));

		do_action('peepso_profile_new_review', $view_user_id, $post_id);
	}
}
