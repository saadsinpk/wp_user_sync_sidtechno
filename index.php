<?php /*
Plugin Name: User Sync by Sid Techno
Plugin URI: http://sidtechno.com
description: User Sync by Sid Techno
Version: 1
Author: Muhammad Saad
Author URI: http://sidtechno.com
*/

register_deactivation_hook( __FILE__, 'sid_on_deactive' );
function sid_on_deactive() {
   global $wpdb;
   $the_removal_query = "DROP TABLE IF EXISTS `{$wpdb->prefix}sync_user_sid`";
   $wpdb->query( $the_removal_query ); 
}
register_activation_hook ( __FILE__, 'sid_on_activate' );
function sid_on_activate() {
   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   global $wpdb;
   $create_table_query = "
           CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}sync_user_sid` (
           `id` int(11) NOT NULL auto_increment,
           `current_userid` int(11) NOT NULL DEFAULT 0,
           `sync_userid` int(11) NOT NULL DEFAULT 0,
           `updated_status` int(11) NOT NULL DEFAULT 0,
           `updated` int(11) NOT NULL DEFAULT 0,
           `update_time` timestamp NOT NULL DEFAULT current_timestamp(),
               PRIMARY KEY  (`id`)
           ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
   ";
   dbDelta( $create_table_query );
}


add_action( 'profile_update', 'update_user_sidtechno',10, 2);
add_action( 'user_register', 'add_new_user_sidtechno', 10, 1);
add_action( 'delete_user', 'delete_user_sidtechno');

function add_new_user_sidtechno( $user_id ) {
	global $wpdb;
	curl_user_function_sid($user_id, 1);
}

function update_user_sidtechno( $user_id, $old_user_data ) {
	global $wpdb;
	curl_user_function_sid($user_id, 2);
}

function delete_user_sidtechno( $user_id ) {
	global $wpdb;
	curl_user_function_sid($user_id, 3);
}

add_action( 'init', 'update_user_sidtechno_init' );
function update_user_sidtechno_init() {
	global $wpdb;
	if(isset($_GET['update_return_user_sid'])) {
		$post = file_get_contents('php://input');
		$post = json_decode($post);

		$table_name = "{$wpdb->prefix}sync_user_sid";
        $where = array(
          'current_userid' => $post->user_id,
        );
		if($post->status == 3) {
			$wpdb->delete($table_name, $where);
			exit();
		}
	    $user_data = array(
	      'sync_userid' => $post->sync_id,
	      'updated' => 2
	    );
        $result = $wpdb->update($table_name, $user_data, $where);
        $user_id = $userDetails_get->ID;
		exit();
	}

	if(isset($_GET['update_user_sid'])) {
		$post = file_get_contents('php://input');

		if(!empty($post)) {
			$post = json_decode($post)->data;

			$user_id = 0;
			$user_login = '';
			$user_pass = '';
			$user_nicename = '';
			$user_registered = '';
			$display_name = '';
			$user_email = '';

			if(!$post->user_data->ID) {
				exit;
			}
			if(isset($post->user_data)) {
				$user_id = $post->user_data->ID;
			}
			if(isset($post->user_data->user_login)) {
				$user_login = $post->user_data->user_login;
			}
			if(isset($post->user_data->user_pass)) {
				$user_pass = $post->user_data->user_pass;
			}
			if(isset($post->user_data->user_nicename)) {
				$user_nicename = $post->user_data->user_nicename;
			}
			if(isset($post->user_data->user_registered)) {
				$user_registered = $post->user_data->user_registered;
			}
			if(isset($post->user_data->display_name)) {
				$display_name = $post->user_data->display_name;
			}
			if(isset($post->user_data->user_email)) {
				$user_email = $post->user_data->user_email;
			}

			$table_name = "{$wpdb->prefix}users";
			$table_meta_name = "{$wpdb->prefix}usermeta";

			if($post->user_data->updated_status == 3) {
		        $where = array(
		          'ID' => $post->user_data->sync_userid,
		        );
				$wpdb->delete($table_name, $where);
		        $where_meta = array(
		          'user_id' => $post->user_data->sync_userid,
		        );
				$wpdb->delete($table_meta_name, $where_meta);
				curl_user_return_function_sid($post->user_data->ID, $post->user_data->sync_userid, 3);
				exit();
			} elseif($post->user_data->updated_status == 2 AND $post->user_data->sync_userid != 0) {
			    $user_data = array(
			      'user_email' => $user_email,
			      'user_login' => $user_login,
			      'user_nicename' => $user_nicename,
			      'user_pass' => $user_pass,
			      'user_registered' => $user_registered,
			      'display_name' => $display_name,
			    );

			    $userDetails_get = $wpdb->get_row(" 
		        SELECT {$wpdb->prefix}users.*
		        FROM {$wpdb->prefix}users
		        WHERE  {$wpdb->prefix}users.ID='" . $post->user_data->sync_userid . "' ");
		       
		        if (!$userDetails_get) {
		            $result = $wpdb->insert($table_name, $user_data, $format = null);
		            $user_id = $wpdb->insert_id;
		        } else {
			        $where = array(
			          'ID' => $userDetails_get->ID,
			        );
		            $result = $wpdb->update($table_name, $user_data, $where);
		            $user_id = $userDetails_get->ID;
		        }

				if(isset($post->user_meta))	{
					foreach ($post->user_meta as $meta_key => $meta_value) {
						update_user_meta($user_id, $meta_key, $meta_value);
					}
				}
				if(isset($post->user_roles)) {
					$wp_get_user = get_user_by('ID', $user_id);
					foreach ($post->user_roles as $user_roles_key => $user_roles_value) {
						$wp_get_user->add_role($user_roles_value);
					}
				}
				curl_user_return_function_sid($post->user_data->ID, $user_id, 2);
				exit();
			} else {
			    $user_data = array(
			      'user_email' => $user_email,
			      'user_login' => $user_login,
			      'user_nicename' => $user_nicename,
			      'user_pass' => $user_pass,
			      'user_registered' => $user_registered,
			      'display_name' => $display_name,
			    );
			    $userDetails_get = $wpdb->get_row(" 
		        SELECT {$wpdb->prefix}users.*
		        FROM {$wpdb->prefix}users
		        WHERE  {$wpdb->prefix}users.user_email='" . $user_email . "' || {$wpdb->prefix}users.user_login='" . $user_login . "' ");

		        if (!$userDetails_get) {
		            $result = $wpdb->insert($table_name, $user_data, $format = null);
		            $user_id = $wpdb->insert_id;
		        } else {
			        $where = array(
			          'ID' => $userDetails_get->ID,
			        );
		            $result = $wpdb->update($table_name, $user_data, $where);
		            $user_id = $userDetails_get->ID;
		        }

				if(isset($post->user_meta))	{
					foreach ($post->user_meta as $meta_key => $meta_value) {
						update_user_meta($user_id, $meta_key, $meta_value);
					}
				}
				if(isset($post->user_roles)) {
					$wp_get_user = get_user_by('ID', $user_id);
					foreach ($post->user_roles as $user_roles_key => $user_roles_value) {
						$wp_get_user->add_role($user_roles_value);
					}
				}
				curl_user_return_function_sid($post->user_data->ID, $user_id, 1);
				exit();
			}
		}
	}
}

/**
 * Register a custom menu page.
 */
function sidtechno_register_my_custom_menu_page(){
	add_menu_page( 
		__( 'User Sync', 'textdomain' ),
		'User Sync',
		'manage_options',
		'user_sync_sidtecho',
		'user_sync_sidtecho',
		plugins_url( 'myplugin/images/icon.png' ),
		6
	); 
}
add_action( 'admin_menu', 'sidtechno_register_my_custom_menu_page' );

/**
 * Display a custom menu page
 */
function user_sync_sidtecho(){
	if(isset($_POST['update_user_sync'])) {
		update_option( 'user_sync_url', $_POST['sync_wordpress_url'] );
	}
	echo '<h2>USER Sync Setting</h2>
		<form action="" method="POST">
			<div class="form-group">
				<label for="sync_wordpress_url">USER Sync wordpress URL</label>
				<input type="text" class="form-control" id="sync_wordpress_url" aria-describedby="usersyncHelp" placeholder="Enter Wordpress URL" name="sync_wordpress_url" value="'.get_option('user_sync_url').'">
				<br>
				<small id="emailHelp" class="form-text text-muted">Add URL where you want to sync USERS.</small>
			</div>
			<button type="submit" class="btn btn-primary" name="update_user_sync">Submit</button>
		</form>';
}

function curl_user_return_function_sid($user_id, $sync_id, $status) {
	$postdata['user_id'] = $user_id;
	$postdata['sync_id'] = $sync_id;
	$postdata['status'] = $status;

	$url = get_option('user_sync_url')."?update_return_user_sid";
	$data = json_encode($postdata);

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
	   "Content-Type: application/json",
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	//for debug only!
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	$resp = curl_exec($curl);
	curl_close($curl);
}

function curl_user_function_sid($user_id, $current_status) {
	global $wpdb;

    $get_sync_data_by_user_id = $wpdb->get_row(" 
    SELECT {$wpdb->prefix}sync_user_sid.*
    FROM {$wpdb->prefix}sync_user_sid
    WHERE  {$wpdb->prefix}sync_user_sid.current_userid='" . $user_id . "' ");

    if (!$get_sync_data_by_user_id) {
		$table_name = "{$wpdb->prefix}sync_user_sid";
	    $user_data = array(
	      'current_userid' => $user_id,
	      'updated_status' => $current_status,
	      'updated' => 1
	    );
        $result = $wpdb->insert($table_name, $user_data, $format = null);
        $user_id = $wpdb->insert_id;
    } else {
		$table_name = "{$wpdb->prefix}sync_user_sid";
	    $user_data = array(
	      'updated_status' => $current_status,
	      'updated' => 1
	    );
        $where = array(
          'current_userid' => $user_id,
        );
        $result = $wpdb->update($table_name, $user_data, $where);
        $user_id = $get_sync_data_by_user_id->id;
    }

    $get_sync_data = $wpdb->get_results(" 
    SELECT {$wpdb->prefix}sync_user_sid.*
    FROM {$wpdb->prefix}sync_user_sid
    WHERE  {$wpdb->prefix}sync_user_sid.updated = 1");

    foreach ($get_sync_data as $get_sync_data_key => $get_sync_data_value) {
    	if($get_sync_data_value->updated_status == 3) {
			$postdata['data']['user_data']['ID'] = $get_sync_data_value->current_userid;
			$postdata['data']['user_data']['sync_userid'] = $get_sync_data_value->sync_userid;
			$postdata['data']['user_data']['updated_status'] = 3;

			$url = get_option('user_sync_url')."?update_user_sid";
			$data = json_encode($postdata);

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
			   "Content-Type: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp = curl_exec($curl);
			curl_close($curl);
    	} else {
	    	$user_id = $get_sync_data_value->current_userid;

		    $user_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}users WHERE id = '$user_id'");
		    $user_data_array = array();
		    foreach ($user_data as $user_data_key => $user_data_value) {
		    	$user_data_array[$user_data_key] = $user_data_value;
		    }
	    	$user_data_array['sync_userid'] = $get_sync_data_value->sync_userid;
	    	$user_data_array['updated_status'] = $get_sync_data_value->updated_status;

		    $user_meta_array = array();
			$user_meta = get_user_meta($user_id,'');
			foreach ($user_meta as $user_metas_key => $user_metas_value) {
				$user_meta_array[$user_metas_key] = $user_metas_value;
			}
			$user_roles_data = get_userdata( $user_id );
			$user_roles = $user_roles_data->roles;

			$post_data = array();
			$post_data['user_data'] = $user_data_array;
			$post_data['user_meta'] = $user_meta_array;
			$post_data['user_roles'] = $user_roles;
			$postdata['data'] = $post_data;

			$url = get_option('user_sync_url')."?update_user_sid";
			$data = json_encode($postdata);

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
			   "Content-Type: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp = curl_exec($curl);
			curl_close($curl);
		}
	}

}

?>