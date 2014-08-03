<?php
// Display admin notice on screen load
function woo_ce_admin_notice( $message = '', $priority = 'updated', $screen = '' ) {

	if( $priority == false || $priority == '' )
		$priority = 'updated';
	if( $message <> '' ) {
		ob_start();
		woo_ce_admin_notice_html( $message, $priority, $screen );
		$output = ob_get_contents();
		set_transient( WOO_CE_PREFIX . '_notice', base64_encode( $output ), MINUTE_IN_SECONDS );
		ob_end_clean();
		add_action( 'admin_notices', 'woo_ce_admin_notice_print' );
	}

}

// HTML template for admin notice
function woo_ce_admin_notice_html( $message = '', $priority = 'updated', $screen = '' ) {

	// Display admin notice on specific screen
	if( !empty( $screen ) ) {

		global $pagenow;

		if( is_array( $screen ) ) {
			if( in_array( $pagenow, $screen ) == false )
				return;
		} else {
			if( $pagenow <> $screen )
				return;
		}

	} ?>
<div id="message" class="<?php echo $priority; ?>">
	<p><?php echo $message; ?></p>
</div>
<?php

}

// Grabs the WordPress transient that holds the admin notice and prints it
function woo_ce_admin_notice_print() {

	if( $output = get_transient( WOO_CE_PREFIX . '_notice' ) ) {
		delete_transient( WOO_CE_PREFIX . '_notice' );
		$output = base64_decode( $output );
		echo $output;
	}

}

// HTML template header on Store Exporter screen
function woo_ce_template_header( $title = '', $icon = 'woocommerce' ) {

	if( $title )
		$output = $title;
	else
		$output = __( 'Store Export', 'woo_ce' ); ?>
<div class="wrap">
	<div id="icon-<?php echo $icon; ?>" class="icon32 icon32-woocommerce-importer"><br /></div>
	<h2>
		<?php echo $output; ?>
		<a href="<?php echo add_query_arg( array( 'tab' => 'export', 'empty' => null ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'woo_ce' ); ?></a>
	</h2>
<?php

}

// HTML template footer on Store Exporter screen
function woo_ce_template_footer() { ?>
</div>
<!-- .wrap -->
<?php
}

// Add Export and Docs links to the Plugins screen
function woo_ce_add_settings_link( $links, $file ) {

	static $this_plugin;

	if( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
	if( $file == $this_plugin ) {
		$docs_url = 'http://www.visser.com.au/docs/';
		$docs_link = sprintf( '<a href="%s" target="_blank">' . __( 'Docs', 'woo_ce' ) . '</a>', $docs_url );
		$export_link = sprintf( '<a href="%s">' . __( 'Export', 'woo_ce' ) . '</a>', add_query_arg( 'page', 'woo_ce', 'admin.php' ) );
		array_unshift( $links, $docs_link );
		array_unshift( $links, $export_link );
	}
	return $links;

}
add_filter( 'plugin_action_links', 'woo_ce_add_settings_link', 10, 2 );

// Load CSS and jQuery scripts for Store Exporter screen
function woo_ce_enqueue_scripts( $hook ) {

	$page = 'woocommerce_page_woo_ce';
	if( $page == $hook ) {

		// Simple check that WooCommerce is activated
		if( class_exists( 'WooCommerce' ) ) {

			global $woocommerce;

			// Load WooCommerce default Admin styling
			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

		}

		// Date Picker
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-datepicker', plugins_url( '/templates/admin/jquery-ui-datepicker.css', WOO_CE_RELPATH ) );

		// Chosen
		wp_enqueue_style( 'jquery-chosen', plugins_url( '/templates/admin/chosen.css', WOO_CE_RELPATH ) );
		wp_enqueue_script( 'jquery-chosen', plugins_url( '/js/jquery.chosen.js', WOO_CE_RELPATH ), array( 'jquery' ) );

		// Common
		wp_enqueue_style( 'woo_ce_styles', plugins_url( '/templates/admin/export.css', WOO_CE_RELPATH ) );
		wp_enqueue_script( 'woo_ce_scripts', plugins_url( '/templates/admin/export.js', WOO_CE_RELPATH ), array( 'jquery', 'jquery-ui-sortable' ) );

		if( WOO_CE_DEBUG ) {
			wp_enqueue_style( 'jquery-csvToTable', plugins_url( '/templates/admin/jquery-csvtable.css', WOO_CE_RELPATH ) );
			wp_enqueue_script( 'jquery-csvToTable', plugins_url( '/js/jquery.csvToTable.js', WOO_CE_RELPATH ), array( 'jquery' ) );
		}

	}

}
add_action( 'admin_enqueue_scripts', 'woo_ce_enqueue_scripts' );

// HTML active class for the currently selected tab on the Store Exporter screen
function woo_ce_admin_active_tab( $tab_name = null, $tab = null ) {

	if( isset( $_GET['tab'] ) && !$tab )
		$tab = $_GET['tab'];
	else if( !isset( $_GET['tab'] ) && woo_ce_get_option( 'skip_overview', false ) )
		$tab = 'export';
	else
		$tab = 'overview';

	$output = '';
	if( isset( $tab_name ) && $tab_name ) {
		if( $tab_name == $tab )
			$output = ' nav-tab-active';
	}
	echo $output;

}

// HTML template for each tab on the Store Exporter screen
function woo_ce_tab_template( $tab = '' ) {

	if( !$tab )
		$tab = 'overview';

	// Store Exporter Deluxe
	$woo_cd_url = 'http://www.visser.com.au/woocommerce/plugins/exporter-deluxe/';
	$woo_cd_link = sprintf( '<a href="%s" target="_blank">' . __( 'Store Exporter Deluxe', 'woo_ce' ) . '</a>', $woo_cd_url );

	$troubleshooting_url = 'http://www.visser.com.au/documentation/store-exporter-deluxe/';

	switch( $tab ) {

		case 'overview':
			$skip_overview = woo_ce_get_option( 'skip_overview', false );
			break;

		case 'export':
			$export_type = ( isset( $_POST['dataset'] ) ? $_POST['dataset'] : woo_ce_get_option( 'last_export', 'products' ) );

			$products = woo_ce_return_count( 'products' );
			$categories = woo_ce_return_count( 'categories' );
			$tags = woo_ce_return_count( 'tags' );
			$brands = woo_ce_return_count( 'brands' );
			$orders = woo_ce_return_count( 'orders' );
			$customers = woo_ce_return_count( 'customers' );
			$users = woo_ce_return_count( 'users' );
			$coupons = woo_ce_return_count( 'coupons' );
			$attributes = woo_ce_return_count( 'attributes' );
			$subscriptions = woo_ce_return_count( 'subscriptions' );

			if( $product_fields = woo_ce_get_product_fields() ) {
				foreach( $product_fields as $key => $product_field )
					$product_fields[$key]['disabled'] = ( isset( $product_field['disabled'] ) ? $product_field['disabled'] : 0 );
			}
			if( $category_fields = woo_ce_get_category_fields() ) {
				foreach( $category_fields as $key => $category_field )
					$category_fields[$key]['disabled'] = ( isset( $category_field['disabled'] ) ? $category_field['disabled'] : 0 );
				$category_orderby = woo_ce_get_option( 'category_orderby', 'ID' );
				$category_order = woo_ce_get_option( 'category_order', 'DESC' );
			}
			if( $tag_fields = woo_ce_get_tag_fields() ) {
				foreach( $tag_fields as $key => $tag_field )
					$tag_fields[$key]['disabled'] = ( isset( $tag_field['disabled'] ) ? $tag_field['disabled'] : 0 );
				$tag_orderby = woo_ce_get_option( 'tag_orderby', 'ID' );
				$tag_order = woo_ce_get_option( 'tag_order', 'DESC' );
			}
			if( $brand_fields = woo_ce_get_brand_fields() ) {
				foreach( $brand_fields as $key => $brand_field )
					$brand_fields[$key]['disabled'] = ( isset( $brand_field['disabled'] ) ? $brand_field['disabled'] : 0 );
			}
			$order_fields = woo_ce_get_order_fields();
			$customer_fields = woo_ce_get_customer_fields();
			$user_fields = woo_ce_get_user_fields();
			$coupon_fields = woo_ce_get_coupon_fields();
			$subscription_fields = woo_ce_get_subscription_fields();
			$attribute_fields = false;

			// Export options
			$upsell_formatting = woo_ce_get_option( 'upsell_formatting', 1 );
			$crosssell_formatting = woo_ce_get_option( 'crosssell_formatting', 1 );
			$limit_volume = woo_ce_get_option( 'limit_volume' );
			$offset = woo_ce_get_option( 'offset' );
			break;

		case 'archive':
			if( isset( $_GET['deleted'] ) ) {
				$message = __( 'Archived export has been deleted.', 'woo_ce' );
				woo_ce_admin_notice( $message );
			}
			if( $files = woo_ce_get_archive_files() ) {
				foreach( $files as $key => $file )
					$files[$key] = woo_ce_get_archive_file( $file );
			}
			break;

		case 'settings':
			$export_filename = woo_ce_get_option( 'export_filename', 'woo-export_%dataset%-%date%.csv' );
			$delete_csv = woo_ce_get_option( 'delete_csv', 0 );
			$timeout = woo_ce_get_option( 'timeout', 0 );
			$encoding = woo_ce_get_option( 'encoding', 'UTF-8' );
			$bom = woo_ce_get_option( 'bom', 1 );
			$delimiter = woo_ce_get_option( 'delimiter', ',' );
			$category_separator = woo_ce_get_option( 'category_separator', '|' );
			$escape_formatting = woo_ce_get_option( 'escape_formatting', 'all' );
			$date_format = woo_ce_get_option( 'date_format', 'd/m/Y' );
			$file_encodings = ( function_exists( 'mb_list_encodings' ) ? mb_list_encodings() : false );
			break;

		case 'tools':
			// Product Importer Deluxe
			if( function_exists( 'woo_pd_init' ) ) {
				$woo_pd_url = add_query_arg( 'page', 'woo_pd' );
				$woo_pd_target = false;
			} else {
				$woo_pd_url = 'http://www.visser.com.au/woocommerce/plugins/product-importer-deluxe/';
				$woo_pd_target = ' target="_blank"';
			}
			break;

	}
	if( $tab ) {
		if( file_exists( WOO_CE_PATH . 'templates/admin/tabs-' . $tab . '.php' ) ) {
			include_once( WOO_CE_PATH . 'templates/admin/tabs-' . $tab . '.php' );
		} else {
			$message = sprintf( __( 'We couldn\'t load the export template file <code>%s</code> within <code>%s</code>, this file should be present.', 'woo_ce' ), 'tabs-' . $tab . '.php', WOO_CE_PATH . 'templates/admin/...' );
			woo_ce_admin_notice_html( $message, 'error' );
			ob_start(); ?>
<p><?php _e( 'You can see this error for one of a few common reasons', 'woo_ce' ); ?>:</p>
<ul class="ul-disc">
	<li><?php _e( 'WordPress was unable to create this file when the Plugin was installed or updated', 'woo_ce' ); ?></li>
	<li><?php _e( 'The Plugin files have been recently changed and there has been a file conflict', 'woo_ce' ); ?></li>
	<li><?php _e( 'The Plugin file has been locked and cannot be opened by WordPress', 'woo_ce' ); ?></li>
</ul>
<p><?php _e( 'Jump onto our website and download a fresh copy of this Plugin as it might be enough to fix this issue. If this persists get in touch with us.', 'woo_ce' ); ?></p>
<?php
			ob_end_flush();
		}
	}
}

// HTML template for header prompt on Store Exporter screen
function woo_ce_support_donate() {

	$output = '';
	$show = true;
	if( function_exists( 'woo_vl_we_love_your_plugins' ) ) {
		if( in_array( WOO_CE_DIRNAME, woo_vl_we_love_your_plugins() ) )
			$show = false;
	}
	if( $show ) {
		$donate_url = 'http://www.visser.com.au/#donations';
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . WOO_CE_DIRNAME;
		$output = '
<div id="support-donate_rate" class="support-donate_rate">
	<p>' . sprintf( __( '<strong>Like this Plugin?</strong> %s and %s.', 'woo_ce' ), '<a href="' . $donate_url . '" target="_blank">' . __( 'Donate to support this Plugin', 'woo_ce' ) . '</a>', '<a href="' . add_query_arg( array( 'rate' => '5' ), $rate_url ) . '#postform" target="_blank">rate / review us on WordPress.org</a>' ) . '</p>
</div>
';
	}
	echo $output;

}
?>
