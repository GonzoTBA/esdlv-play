<?php

/*
Plugin Name: ESDLV Play
Plugin URI: ---
Description: ESDLV gamification plugin
Version: 0.1
Author: Javier Malonda
Author URI: elsentidodelavida.net
License: GPL2
*/

defined('ABSPATH') or die("Bye bye");

// Install, on plugin activation, new table with user scores
global $pu_db_version;
$pu_db_version = '1.0';


/**
 * esdlv_play_table_install - Creates the esdlv_table on activation
 *
 * @return void
 */
function esdlv_play_table_install() {
	global $wpdb;
	global $esdlv_play_db_version;

	$table_name = $wpdb->prefix . 'esdlv_play';
	
	$charset_collate = $wpdb->get_charset_collate();

    // Two spaces between the words PRIMARY KEY and the definition of your primary key.
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id smallint NOT NULL,
        user_score mediumint NOT NULL default 0,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    // Execute DB delta changes. Actually create the table.
	dbDelta( $sql );

	add_option( 'esdlv_play_db_version', $esdlv_play_db_version );
}

register_activation_hook( __FILE__, 'esdlv_play_table_install' );


// Adds a shortcode calling the function below
add_shortcode( 'esdlv_scores', 'esdlv_play_show_scores' );
/**
 * esdlv_play_show_scores - Creates a table showing the user scores
 *
 * @return void
 */
function esdlv_play_show_scores() {
    global $wpdb;
    // Get every user with his score from the play table
    $table_name = $wpdb->prefix . 'esdlv_play';
    $charset_collate = $wpdb->get_charset_collate();

    $results = $wpdb->get_results( 
                $wpdb->prepare( "SELECT * FROM $table_name ORDER BY user_score DESC" ) 
             );

    // Shortcodes need a returned value, not echoed.
    // Start an output buffer that will capture all echoes
    ob_start();
    
    echo "<table class='esdlv-scores'>";
    echo "<tr>";
    echo "<th>Usuario</th><th>Puntuación</th>";
    foreach ( $results as $result ) {
        $user_id = $result->user_id;
        $username = get_user_by('id', $user_id)->display_name;
        // Print table
        echo "<tr>";
        echo "<td>" . $username . "</td><td>" . $result->user_score . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // End output buffer
    $output = ob_get_clean();

    return $output;
}


function esdlv_play_add_score_on_comment( $comment_ID ) {
    // Find out the user who commented
    $user_id = get_comment( $comment_ID )->user_id;

    // update user score in score table
    $score_comment = 10;
    global $wpdb;
    // Get user's current score
    $table_name = $wpdb->prefix . 'esdlv_play';
    $result = $wpdb->get_results( 
        $wpdb->prepare( "SELECT user_score FROM $table_name WHERE user_id = $user_id" ) 
     );
    $current_score = $result[0]->user_score;
    $new_score = $current_score + $score_comment;
    // Update score
    $wpdb->update( $table_name, array( 
        'user_score' => $new_score,
        'time'       => current_time('mysql')
    ), 
    array( 'user_id' => $user_id ) );
}

add_action( 'comment_post', 'esdlv_play_add_score_on_comment' );


/**
 * esdlv_play Load custom CSS 
 *
 * @return void
 */
function esdlv_play_css() {
    wp_register_style('esdlv_play_css', plugins_url('style.css',__FILE__ ));
    wp_enqueue_style('esdlv_play_css');
}

add_action( 'wp_enqueue_scripts','esdlv_play_css');