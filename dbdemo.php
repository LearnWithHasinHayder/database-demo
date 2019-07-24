<?php
/*
Plugin Name: Database Demo
Plugin URI:
Description: Database Query Demo
Version: 1.0
Author: LWHH
Author URI: https://hasin.me
License: GPLv2 or later
Text Domain: database-demo
Domain Path: /languages/
*/

define( "DBDEMO_DB_VERSION", "1.3" );
require_once "class.dbdemousers.php";

function dbdemo_init() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'persons';
	$sql        = "CREATE TABLE {$table_name} (
			id INT NOT NULL AUTO_INCREMENT,
			name VARCHAR(250),
			email VARCHAR(250),
			PRIMARY KEY (id)
	);";
	require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
	dbDelta( $sql );

	add_option( "dbdemo_db_version", DBDEMO_DB_VERSION );

	if ( get_option( "dbdemo_db_version" ) != DBDEMO_DB_VERSION ) {
		$sql = "CREATE TABLE {$table_name} (
			id INT NOT NULL AUTO_INCREMENT,
			name VARCHAR(250),
			email VARCHAR(250),
			age INT,
			PRIMARY KEY (id)
		);";
		dbDelta( $sql );
		update_option( "dbdemo_db_version", DBDEMO_DB_VERSION );
	}

}

register_activation_hook( __FILE__, "dbdemo_init" );

function dbdemo_drop_column() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'persons';
	if ( get_option( "dbdemo_db_version" ) != DBDEMO_DB_VERSION ) {
		$query = "ALTER TABLE {$table_name} DROP COLUMN age";
		$wpdb->query( $query );
	}
	update_option( "dbdemo_db_version", DBDEMO_DB_VERSION );
}

add_action( "plugins_loaded", "dbdemo_drop_column" );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( "toplevel_page_dbdemo" == $hook ) {
		wp_enqueue_style( 'dbdemo-style', plugin_dir_url( __FILE__ ) . 'assets/css/form.css' );
	}
} );


function dbdemo_load_data() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'persons';
	$wpdb->insert( $table_name, [
		'name'  => 'John Doe',
		'email' => 'john@doe.com'
	] );
	$wpdb->insert( $table_name, [
		'name'  => 'Jane Doe',
		'email' => 'jane@doe.com'
	] );

}

register_activation_hook( __FILE__, "dbdemo_load_data" );

function dbdemo_flush_data() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'persons';
	$query      = "TRUNCATE TABLE {$table_name}";
	$wpdb->query( $query );
}

register_deactivation_hook( __FILE__, "dbdemo_flush_data" );


add_action( 'admin_menu', function () {
	add_menu_page( 'DB Demo', 'DB Demo', 'manage_options', 'dbdemo', 'dbdemo_admin_page' );
} );

function dbdemo_admin_page() {
	global $wpdb;
	echo '<h2>DB Demo</h2>';
	$id = $_GET['pid'] ?? 0;
	$id = sanitize_key( $id );
	if ( $id ) {
		$result = $wpdb->get_row( "select * from {$wpdb->prefix}persons WHERE id='{$id}'" );
		/*if($result){
			echo "Name: {$result->name}<br/>";
			echo "Email: {$result->email}<br/>";
		}*/
	}
	?>
    <!--<div class="notice notice-success is-dismissible">
		<p>Some Error Information</p>
	</div>-->
    <div class="form_box">
        <div class="form_box_header">
			<?php _e( 'Data Form', 'database-demo' ) ?>
        </div>
        <div class="form_box_content">
            <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
				<?php
				wp_nonce_field( 'dbdemo', 'nonce' );
				?>
                <input type="hidden" name="action" value="dbdemo_add_record">
                <label>
                    <strong>Name</strong>
                </label><br/>
                <input type="text" name="name" class="form_text" value="<?php if ( $id ) {
					echo $result->name;
				} ?>"><br/>
                <label>
                    <strong>Email</strong>
                </label><br/>
                <input type="text" name="email" class="form_text" value="<?php if ( $id ) {
					echo $result->email;
				} ?>"><br/>

				<?php
				if ( $id ) {
					echo '<input type="hidden" name="id" value="' . $id . '">';
					submit_button( "Update Record" );
				} else {
					submit_button( "Add Record" );
				}


				?>
            </form>
        </div>
    </div>
    <div class="form_box" style="margin-top: 30px;">
        <div class="form_box_header">
			<?php _e( 'Users', 'database-demo' ) ?>
        </div>
        <div class="form_box_content" >
			<?php
			global $wpdb;
			$dbdemo_users = $wpdb->get_results( "SELECT id, name, email FROM {$wpdb->prefix}persons ORDER BY id DESC", ARRAY_A );
			$dbtu         = new DBTableUsers($dbdemo_users);
			$dbtu->prepare_items();
			$dbtu->display();
			?>
        </div>
    </div>
	<?php


}

add_action( 'admin_post_dbdemo_add_record', function () {
	global $wpdb;
	$nonce = sanitize_text_field( $_POST['nonce'] );
	if ( wp_verify_nonce( $nonce, 'dbdemo' ) ) {
		$name  = sanitize_text_field( $_POST['name'] );
		$email = sanitize_text_field( $_POST['email'] );
		$id    = sanitize_text_field( $_POST['id'] );

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}persons", [ 'name' => $name, 'email' => $email ], [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=dbdemo&pid=' . $id ) );
		} else {
			$wpdb->insert( "{$wpdb->prefix}persons", [ 'name' => $name, 'email' => $email ] );
			/*$new_id = $wpdb->insert_id;
			wp_redirect(admin_url('admin.php?page=dbdemo&pid='.$new_id));*/
			wp_redirect( admin_url( 'admin.php?page=dbdemo' ) );
		}
	}

} );

