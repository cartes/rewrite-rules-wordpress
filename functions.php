function custom_init() {
	global $wp_rewrite;

	// Declare our permalink structure
	$post_type_structure = '/%post_type%/%taxonomy%/%term%/%page%';

	// Make wordpress aware of our custom querystring variables
	$wp_rewrite->add_rewrite_tag("%post_type%", '([^/]+)', "post_type=");
	$wp_rewrite->add_rewrite_tag("%taxonomy%", '([^/]+)', "taxonomy=");
	$wp_rewrite->add_rewrite_tag("%term%", '([^/]+)', "term=");
	$wp_rewrite->add_rewrite_tag('%page%', '([^/]+)', 'paged=)');

	// Only get custom and public post types
	$args=array(
		'public'   => true,
		'_builtin' => false
	);
	$output = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'
	$post_types=get_post_types($args,$output,$operator);
	$post_types_string = implode("|", $post_types);

	$taxonomies=get_taxonomies($args,$output,$operator); // Note the use of same arguments as with get_post_types()
	$taxonomies_string = implode("|", $taxonomies);

	// Now add the rewrite rules, note that the order in which we declare them are important
	//^(analisis|proyectos|litio|eng_directory|plantas|operacion|equipamiento)/(content_tax|country_tax|plants_tax|operacion_tax)/([^/]*)/?
	add_rewrite_rule('^('.$post_types_string.')/('.$taxonomies_string.')/([^/]*)/page/([0-9]{1,})/?','index.php?post_type=$matches[1]&$matches[2]=$matches[3]&paged=$matches[4]','top');
	add_rewrite_rule('^('.$post_types_string.')/('.$taxonomies_string.')/([^/]*)/?','index.php?post_type=$matches[1]&$matches[2]=$matches[3]','top');
	add_rewrite_rule('^('.$post_types_string.')/([^/]*)/?','index.php?post_type=$matches[1]&name=$matches[2]','top');
	add_rewrite_rule('^('.$post_types_string.')/?','index.php?post_type=$matches[1]','top');

	// Finally, flush and recreate the rewrite rules
	flush_rewrite_rules();
}

function post_type_permalink($permalink, $post_id, $leavename){
	$post = get_post($post_id);

	// An array of our custom query variables
	$rewritecode = array(
		'%post_type%',
		'/%taxonomy%',
		'/%term%',
		$leavename? '' : '%postname%',
		$leavename? '' : '%pagename%',
	);

	// Avoid trying to rewrite permalinks when not applicable
	if ('' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft'))) {
		// Fetch the post type
		$post_type = get_post_type( $post->ID );

		// Setting these isn't necessary if the taxonomy has rewrite = true,
		// otherwise you need to fetch the relevant data from the current post
		$taxonomy = "";
		$term = "";

		// Now we do the permalink rewrite
		$rewritereplace = array(
			$post_type,
			$taxonomy,
			$term,
			$post->post_name,
			$post->post_name,
		);
		$permalink = str_replace($rewritecode, $rewritereplace, $permalink);
	}

	return $permalink;
}

// Create custom rewrite rules
add_action('init', 'custom_init');

// Translate the custom post type permalink tags
add_filter('post_type_link', 'post_type_permalink', 10, 3);
