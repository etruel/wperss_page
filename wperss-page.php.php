<?php 
/*
Plugin Name: WPeRSS Page
Plugin URI: http://www.etruel.com/downloads/wperss-page
Description: Inserts a meta box when edit pages for insert a Feed URL to show on front-end into a standard page or using a custom page template..
Version: 1.0
Author: etruel
Author URI: http://www.netmdp.com
/* ----------------------------------------------*/

// don't load directly
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

require_once 'pagetemplater.php';
add_action("admin_init", "rss_page_meta_init");
add_action('save_post', 'rss_page_feed_url_save');

add_action('loop_start', 'rsspage_initactions');

function rsspage_initactions(){
	global $post;
	if (!is_page()) return;
	$howtoshow = get_post_meta($post->ID, 'howtoshow', true);
	switch($howtoshow) {
	case 'the_content':
		add_action('the_content', 'rss_page_add_content',999);
		break;
	case 'shortcode':
		add_shortcode( 'wpersspage', 'rss_page_add_content' );
		break;
	default:  //page_template
		add_action('the_content', 'rss_page_add_content',999);
		break;
	}
}

function rss_page_add_content ($content = '') {
	global $post, $rss_page_feed_url, $rss_page_template;
	if (!is_page()) return $content;
	$howtoshow = get_post_meta($post->ID, 'howtoshow', true);
//	$current_filter = current_filter();
	$mensaje = "";
	$rss_page_feed_url = get_post_meta($post->ID, 'rss_page_feed_url', true);

	
	$rss_page_template = apply_filters('rss_page_get_itemtemplate', plugin_dir_url( __FILE__ ).'rss_item_tpl.html'); //get_post_meta($post->ID, 'rss_page_template', true);
	if (isset($rss_page_feed_url) && !empty($rss_page_feed_url)) {
		ob_start();
		include_once 'rss2html.php';
		$mensaje = ob_get_contents();
		ob_end_clean();
	}else{
		$mensaje = $content; //apply_filters( 'the_content', $content );
	}
	
	
	return $mensaje;
}

function rss_page_meta_init(){
  add_meta_box("rss-page-feed-url-save", "Feed_URl", "rss_page_feed_url", "page", "side", "high");
}

function rss_page_feed_url( $post ){
	$rss_page_feed_url = get_post_meta($post->ID, 'rss_page_feed_url', true);
	$howtoshow = get_post_meta($post->ID, 'howtoshow', true);
	?>			
	<p><b><?php echo '<label for="rss_page_feed_url">' . __('Insert the RSS feed link for this page:', 'wperss-page' ) . '</label>'; ?></b>
	<input name="rss_page_feed_url" id="rss_page_feed_url" type="text" value="<?php echo $rss_page_feed_url; ?>" class="large-text" />
	<?php _e('Leave empty to ignore this field.', 'wperss-page' ); ?>
	</p>
	<b><?php _e('How to display the feed content:',  'wperss-page') ?></b><br />
	<label><input type="radio" name="howtoshow" <?php echo checked('the_content',$howtoshow,false); ?> value="the_content" /> <span style="background-color: lightblue; padding-left: 3px; padding-right: 3px;">get_the_content</span> <?php _e('Wordpress filter', 'wperss-page'); ?></label><br />
	<label><input type="radio" name="howtoshow" <?php echo checked('page_template',$howtoshow,false); ?> value="page_template" /> 
		<?php _e('RSS Page Template.', 'wperss-page'); ?><br /><?php _e('Must selected also on Page Attributes.', 'wperss-page'); ?></label><br />
	<label><input type="radio" name="howtoshow" <?php echo checked('shortcode',$howtoshow,false); ?> value="shortcode" /> <span style="background-color: lightblue; padding-left: 3px; padding-right: 3px;">[wpersspage]</span> <?php _e('Shortcode', 'wperss-page'); ?></label><br />
	<?php _e('The option apply only for show this page.', 'wperss-page' ); ?>
	<?php	
	wp_nonce_field( plugin_basename( __FILE__ ), 'rss_page_nonce' );

}

// Save Meta Details
function rss_page_feed_url_save($post_id){
	global $post_type;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

	// authentication checks    // make sure data came from our meta box
	if(isset($_POST['rss_page_nonce']) && !empty($_POST['rss_page_nonce']) &&
		!wp_verify_nonce($_POST['rss_page_nonce'],  plugin_basename( __FILE__ ) ) )  return $post_id;
 
	if (!current_user_can('edit_page', $post_id)) {
		return $post_id;
	}
    // check user permissions
    if ($post_type == 'page') {
		$howtoshow = (isset($_POST["howtoshow"]) && !empty($_POST["howtoshow"]) ) ? $_POST["howtoshow"] : '';

		update_post_meta($post_id, "howtoshow", $howtoshow ) or add_post_meta($post_id, "howtoshow", $howtoshow, true);
		$rss_page_feed_url = (isset($_POST["rss_page_feed_url"]) && !empty($_POST["rss_page_feed_url"]) ) ? $_POST["rss_page_feed_url"] : '';
		update_post_meta($post_id, "rss_page_feed_url", $rss_page_feed_url ) or add_post_meta($post_id, "rss_page_feed_url", $rss_page_feed_url, true);
		
/*		if ( !update_post_meta($post_id, "rss_page_feed_url", ( (isset($_POST["rss_page_feed_url"]) && !empty($_POST["rss_page_feed_url"]) ) ? $_POST["rss_page_feed_url"] : '' ) ) ) {
			//add_post_meta($post_id, "rss_page_feed_url", $_POST["rss_page_feed_url"]);
		}
*/
	}
}

?>