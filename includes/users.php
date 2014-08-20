<?php
if( is_admin() ) {

	/* Start of: WordPress Administration */

	// HTML template for User Sorting widget on Store Exporter screen
	function woo_ce_users_user_sorting() {

		$orderby = woo_ce_get_option( 'user_orderby', 'ID' );
		$order = woo_ce_get_option( 'user_order', 'ASC' );

		ob_start(); ?>
<p><label><?php _e( 'User Sorting', 'woo_ce' ); ?></label></p>
<div>
	<select name="user_orderby">
		<option value="ID"<?php selected( 'ID', $orderby ); ?>><?php _e( 'User ID', 'woo_ce' ); ?></option>
		<option value="display_name"<?php selected( 'display_name', $orderby ); ?>><?php _e( 'Display Name', 'woo_ce' ); ?></option>
		<option value="user_name"<?php selected( 'user_name', $orderby ); ?>><?php _e( 'Name', 'woo_ce' ); ?></option>
		<option value="user_login"<?php selected( 'user_login', $orderby ); ?>><?php _e( 'Username', 'woo_ce' ); ?></option>
		<option value="nicename"<?php selected( 'nicename', $orderby ); ?>><?php _e( 'Nickname', 'woo_ce' ); ?></option>
		<option value="email"<?php selected( 'email', $orderby ); ?>><?php _e( 'E-mail', 'woo_ce' ); ?></option>
		<option value="url"<?php selected( 'url', $orderby ); ?>><?php _e( 'Website', 'woo_ce' ); ?></option>
		<option value="registered"<?php selected( 'registered', $orderby ); ?>><?php _e( 'Date Registered', 'woo_ce' ); ?></option>
		<option value="rand"<?php selected( 'rand', $orderby ); ?>><?php _e( 'Random', 'woo_ce' ); ?></option>
	</select>
	<select name="user_order">
		<option value="ASC"<?php selected( 'ASC', $order ); ?>><?php _e( 'Ascending', 'woo_ce' ); ?></option>
		<option value="DESC"<?php selected( 'DESC', $order ); ?>><?php _e( 'Descending', 'woo_ce' ); ?></option>
	</select>
	<p class="description"><?php _e( 'Select the sorting of Users within the exported file. By default this is set to export User by User ID in Desending order.', 'woo_ce' ); ?></p>
</div>
<?php
		ob_end_flush();

	}

	/* End of: WordPress Administration */

}

// Returns a list of User export columns
function woo_ce_get_user_fields( $format = 'full' ) {

	$fields = array();
	$fields[] = array(
		'name' => 'user_id',
		'label' => __( 'User ID', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'user_name',
		'label' => __( 'Username', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'user_role',
		'label' => __( 'User Role', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'first_name',
		'label' => __( 'First Name', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'last_name',
		'label' => __( 'Last Name', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'full_name',
		'label' => __( 'Full Name', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'nick_name',
		'label' => __( 'Nickname', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'email',
		'label' => __( 'E-mail', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'url',
		'label' => __( 'Website', 'woo_ce' )
	);
	$fields[] = array(
		'name' => 'date_registered',
		'label' => __( 'Date Registered', 'woo_ce' )
	);

/*
	$fields[] = array(
		'name' => '',
		'label' => __( '', 'woo_ce' )
	);
*/

	// Allow Plugin/Theme authors to add support for additional User columns
	$fields = apply_filters( 'woo_ce_user_fields', $fields );

	if( $remember = woo_ce_get_option( 'users_fields', array() ) ) {
		$remember = maybe_unserialize( $remember );
		$size = count( $fields );
		for( $i = 0; $i < $size; $i++ ) {
			$fields[$i]['disabled'] = ( isset( $fields[$i]['disabled'] ) ? $fields[$i]['disabled'] : 0 );
			$fields[$i]['default'] = 1;
			if( !array_key_exists( $fields[$i]['name'], $remember ) )
				$fields[$i]['default'] = 0;
		}
	}

	switch( $format ) {

		case 'summary':
			$output = array();
			$size = count( $fields );
			for( $i = 0; $i < $size; $i++ )
				$output[$fields[$i]['name']] = 'on';
			return $output;
			break;

		case 'full':
		default:
			$size = count( $fields );
			for( $i = 0; $i < $size; $i++ )
				$fields[$i]['order'] = $i;
			return $fields;
			break;

	}

}

// Returns the export column header label based on an export column slug
function woo_ce_get_user_field( $name = null, $format = 'name' ) {

	$output = '';
	if( $name ) {
		$fields = woo_ce_get_user_fields();
		$size = count( $fields );
		for( $i = 0; $i < $size; $i++ ) {
			if( $fields[$i]['name'] == $name ) {
				switch( $format ) {

					case 'name':
						$output = $fields[$i]['label'];
						break;

					case 'full':
						$output = $fields[$i];
						break;

				}
				$i = $size;
			}
		}
	}
	return $output;

}

// Adds custom User columns to the User fields list
function woo_ce_extend_user_fields( $fields = array() ) {

	if( class_exists( 'WC_Admin_Profile' ) ) {
		$admin_profile = new WC_Admin_Profile();
		$show_fields = $admin_profile->get_customer_meta_fields();
		foreach( $show_fields as $fieldset ) {
			foreach( $fieldset['fields'] as $key => $field ) {
				$fields[] = array(
					'name' => $key,
					'label' => sprintf( '%s: %s', $fieldset['title'], esc_html( $field['label'] ) ),
					'disabled' => 1
				);
			}
		}
		unset( $show_fields, $fieldset, $field );
	}
	return $fields;

}
add_filter( 'woo_ce_user_fields', 'woo_ce_extend_user_fields' );

// Returns a list of User IDs
function woo_ce_get_users( $args = array() ) {

	global $export;

	$limit_volume = 0;
	$offset = 0;

	$args = array(
		'offset' => $offset,
		'number' => $limit_volume,
		'fields' => 'ids'
	);
	if( $user_ids = new WP_User_Query( $args ) ) {
		$users = array();
		$export->total_rows = $user_ids->total_users;
		foreach( $user_ids->results as $user_id )
			$users[] = $user_id;
		return $users;
	}

}

function woo_ce_get_user_data( $user_id = 0, $args = array() ) {

	global $export;

	$defaults = array();
	$args = wp_parse_args( $args, $defaults );

	// Get User details
	$user_data = get_userdata( $user_id );

	$user = new stdClass;
	$user->ID = $user_data->ID;
	$user->user_id = $user_data->ID;
	$user->user_name = $user_data->user_login;
	$user->user_role = $user_data->roles[0];
	$user->first_name = $user_data->first_name;
	$user->last_name = $user_data->last_name;
	$user->full_name = sprintf( '%s %s', $user->first_name, $user->last_name );
	$user->nick_name = $user_data->user_nicename;
	$user->email = $user_data->user_email;
	$user->url = $user_data->user_url;
	$user->date_registered = $user_data->user_registered;

	return apply_filters( 'woo_ce_user', $user );
	
}
?>