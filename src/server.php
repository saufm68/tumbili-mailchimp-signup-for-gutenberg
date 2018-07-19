<?php
register_block_type('cgb/kona-instagram-for-gutenberg', array(
	'render_callback' => 'gutengram_render_callback',
		'attributes' => array(
				'numberCols' => array(
					'type' 		=> 'number',
					'default'	=> '4' // nb: a default is needed!
				),
				'token'=> array(
						'type' 		=> 'string',
						'default' => ''
				),
				'numberImages' => array(
					'type' 		=> 'number',
					'default' => 4
				),
				'gridGap' => array(
					'type' 		=> 'number',
					'default'	=> 0
				),
		)
	)
);

/**
 * Generic data fetching wrapper
 * Uses the WP-API for fetching
 */
function fetchData($url) {
	$result = wp_remote_get( $url ); 
	return $result;
}

/**
 * Caching functions
 * The number of images is used as a suffix in the case that the user
 * adds/removes images and expects a refreshed feed.
 */
function add_to_cache( $result, $suffix = '' ) {
	$expire = 24 * 60 * 60; // 24 hours in seconds
	set_transient( 'kona-api_'.$suffix, $result, '', $expire );
}

function get_from_cache( $suffix = '' ) {
	return get_transient( 'kona-api_'.$suffix );
}

/**
 * Server side rendering functions
 */
function gutengram_render_callback( array $attributes ){
	
	$token				= $attributes[ 'token' ];
	$numberImages	= $attributes[ 'numberImages' ];
	$numberCols 	= $attributes[ 'numberCols' ];
	$gridGap 			= $attributes[ 'gridGap' ];

	if ( !get_from_cache() ) {
		// no valid cache found
		// hit the network
		$result = json_decode(fetchData("https://api.instagram.com/v1/users/self/media/recent/?access_token={$token}&count={$numberImages}"));
		add_to_cache( $result, $numberImages ); // add the result to the cache
	} else {
		$result = get_from_cache( $numberImages ); // hit the cache
	}
	
	$thumbs = $result->data;

	$markup = '<div class="wp-block-cgb-kona-instagram-for-gutenberg">
	<div class="display-grid kona-grid" 
	style="grid-template-columns: repeat('.esc_attr($numberCols).', 1fr); 
	margin-left: -'.esc_attr($gridGap).'px; 
	margin-right: -'.esc_attr($gridGap).'px";>';

	foreach( $thumbs as $thumb ) {
		$markup .= '
		<a href="'.esc_attr($thumb->link).'" target="_blank" rel="noopener noreferrer">
			<img
			class="kona-image"
			key="'.esc_attr($thumb->id).'"
			src="'.esc_attr($thumb->images->standard_resolution->url).'"
			alt="'.esc_attr($thumb->caption->text).'"
			style="padding: '.esc_attr($gridGap).'px"
			/>
		</a>';
	}

	return "{$markup}</div></div>";
}