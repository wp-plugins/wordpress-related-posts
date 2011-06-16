<?php
/*
Plugin Name: Related Posts/Pages
Plugin URI: http://www.wphelpforum.com/
Description: Will add related links for posts & pages with your own anchor text
Author URI: http://www.wphelpforum.com/
Author: Thamizhchelvan
Version: 2.0
*/



/**
 * Function to remove the related post data while deactivating the plugin
**/

if(!function_exists('wprp_related_uninstall')){	
	function related_uninstall(){
		global $table_prefix;
		mysql_query("DROP TABLE IF EXISTS `".$table_prefix."relatedposts`") or (mysql_error());
	}
}

/*
 * Function to install the related post table while activating the plugin
*/

if(!function_exists('wprp_install_related')){
	function wprp_install_related(){
		global $table_prefix;
			mysql_query('CREATE TABLE IF NOT EXISTS `'.$table_prefix.'relatedposts` (
		  `r_id` int(11) NOT NULL auto_increment,
		  `post_id` int(11) NOT NULL,
		  `related_post_id` int(11) NOT NULL,
		  `anchor_text` varchar(255) NOT NULL,
		  `alt_text` varchar(100) NOT NULL,
		  `r_order` int(11) NOT NULL,
		  PRIMARY KEY  (`r_id`)
		);') or die(mysql_error());
	}
}

/**
 * Function to check wheather the post is valid one.
 * @param int $ID postid
 * @return boolean/string false or the post title
 */

function wprp_valid_post($ID){
	$post = get_post($ID);

	if ( !is_object($post) )
		return false;	
	if($post->post_status != 'publish')
		return false;
	if($post->post_type == 'post' || $post->post_type == 'page')
		return $post->post_title;		
	return false;		
}

/**
 * Function to get the related post of a post id
 * @param int $post_id postid
 * @return array list of related posts
 */

function wprp_get_related_posts($post_id){
	global $table_prefix;
	$sql = "SELECT * FROM ".$table_prefix."relatedposts WHERE post_id = " . $post_id. " ORDER BY r_order ";
	$result = mysql_query($sql);
	$related_posts = array();
	while($row = mysql_fetch_object($result)){
	$title_post = wprp_valid_post($row->related_post_id);
		if($title_post !== false){
			$row->related_post_title = $title_post;
			$related_posts[] = $row;
		}
	}
	return $related_posts;
}

/**
 * Function to add necessary JS code
 */

function wprp_js_code(){
?>
<script language="javascript">

function wprp_get_child_elements(name,maxl,size){
	var newElement = document.createElement("input");
	newElement.setAttribute("type","text");
	newElement.setAttribute("name",name);
	newElement.setAttribute("id",name);
	newElement.setAttribute("maxlength",maxl);
	newElement.setAttribute("size",size);
	if(name == 'wprp_post_id[]'){
		newElement.setAttribute("class","thickbox");
		newElement.setAttribute("alt","#TB_inline?height=300&width=400&inlineId=wprp_post_suggestion");
		newElement.setAttribute("title","Select/Change Related Post");
		newElement.setAttribute("onblur","javascript:wprp_set_element(this);");
	}
	return newElement;
}


function wprp_add_more(){
	var str_inner_row_elements = "<span onclick='javascript:wprp_remove_row(this);'><a title='Remove This Row' alt='Remove This Row' href='javascript:void(0);'>X</a></span>";
	var r_post_container = document.createElement("div");
	r_post_container.setAttribute("id","rpost_entires[]");
	
	var anchor_box = wprp_get_child_elements('wprp_anchor_text[]','100',40);
	var alt_box = wprp_get_child_elements('wprp_alt_text[]','100',40);
	var postid_box = wprp_get_child_elements('wprp_post_id[]','100',5);
	r_post_container.appendChild(anchor_box);
	r_post_container.appendChild(alt_box);
	r_post_container.appendChild(postid_box);
	var r_span_del = '<span onclick="javascript:wprp_remove_row(this);"><a title="Remove This Row" alt="Remove This Row" href="javascript:void(0);">X</a></span>';
	r_post_container.innerHTML = r_post_container.innerHTML + r_span_del;	
	document.getElementById("related_posts").appendChild(r_post_container);
	


	
}
function wprp_remove_row(rmvButton){
	document.getElementById("related_posts").removeChild(rmvButton.parentNode);	
}
</script>
<script type="text/javascript">

var wprp_focused_element = false;
function wprp_set_element(element){
	wprp_focused_element = element;
}
function wprp_set_postid(postid){
	wprp_focused_element.value = postid;
	tb_remove();
}

function wprp_post_suggest(post_type,post_text,post_cat){
jQuery(document).ready(function($) {

	var data = {
		action:"wprp_post_suggestion",	
		post_text: post_text,
		post_cat: post_cat,
		post_type:post_type
	};


	jQuery.post(ajaxurl, data, function(response) {
		if(response != 0){

			var post_text = "<ul>";
			 for(var key in response) {

				   post_row = "<a title='Set This Post' alt='Set This Post' href='javascript:void(0);' onclick='javascript:wprp_set_postid("+response[key].ID+");'>"+response[key].title+"</a>";
				 post_text += "<li>"+ post_row + "</li>";
				  }
			post_text += "</ul";
			document.getElementById("wprp_suggested_posts").innerHTML = post_text;
			
		}
	},"json");
});
}
function wprp_suggest_post(text){
	cat_id = document.getElementById("wprp_cat_list").value;	 
	p_type = document.getElementById("wprp_post_type").value;
	wprp_post_suggest(p_type,text,cat_id);
}
</script>
<?php 
}


/**
 * Function to save the related posts
 * @param int $id post id
 */

function wprp_save_rposts($id){
	if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;
      
	global $table_prefix;
	$nonce = $_POST['nonce-wprp-edit'];
	
	
	if(wp_verify_nonce($nonce, 'edit-wprp-nonce')){
		//delete the previous values
		mysql_query("DELETE FROM ".$table_prefix."relatedposts WHERE post_id = ".$id);
		//get all the rows
		$total_rows = count($_POST['wprp_anchor_text']);
		$sql_save = "INSERT INTO ".$table_prefix."relatedposts(post_id,related_post_id,anchor_text,alt_text,r_order) VALUES ";
		$save_now = false;

		
		for($i=0;$i<$total_rows;$i++){
			$anchor_text = trim($_POST['wprp_anchor_text'][$i]);
			$alt_text 	 = trim($_POST['wprp_alt_text'][$i]);
			$r_post_id 	 = (int) $_POST['wprp_post_id'][$i];
	
			if(wprp_valid_post($r_post_id) != false && $anchor_text != ""){
				$sql_save .= "('$id','$r_post_id','$anchor_text','$alt_text','$i'),";
				$save_now = true;
			}
		}
		if($save_now === true){
			$sql_save = rtrim($sql_save,",");
			mysql_query($sql_save);			
		}
	}	
	
	
}

/**
 * Function to add the meta box in the post/page edit section 
 */

function wprp_add_rposts(){
	global $post;
	$post_id = $post;
	if (is_object($post_id))
		 $post_id = $post_id->ID;
	$post_id = (int) $post_id;	 
	//get all the related posts of this post id
	if($post_id > 0){
		$related_posts = wprp_get_related_posts($post_id);		
	}	 
	?>
	<div id="wp_related_post_containers">
	<a href="javascript:void(0);" onclick="javascript:wprp_add_more();">Add More</a>
	<noscript><span style="color:red;font-weight: bold;">You should have javascript enabled to use this plugin</span></noscript>
			<input type="hidden" name="nonce-wprp-edit" value="<?php echo wp_create_nonce('edit-wprp-nonce') ?>" />
		<div id="related_posts">
		<BR>
		<span style="font-weight:bold;text-align:center;padding-left:100px;">Anchor Text</span>  <span style="padding-left:220px;font-weight:bold;text-align:center;">Alt Text</span> <span style="padding-left:100px;font-weight:bold;text-align:center;">Post ID</span>
		<?php 
		if(count($related_posts) == 0){
		?>
		<div id="rpost_entires[]">			
			<input size="40" maxlength="100" type="text" name="wprp_anchor_text[]" id="wprp_anchor_text[]"><input type="text" size="40" name="wprp_alt_text[]" id="wprp_alt_text[]"><input onblur="javascript:wprp_set_element(this);" class="thickbox" alt="#TB_inline?height=300&width=400&inlineId=wprp_post_suggestion" title="Select/Change Related Post" size="5" type="text" name="wprp_post_id[]" id="wprp_post_id[]"><span onclick="javascript:wprp_remove_row(this);"><a title="Remove This Row" alt="Remove This Row" href="javascript:void(0);">X</a></span>
		</div>	
	<?php
		}
		else{
			foreach($related_posts as $wprp_related_post){
			?>
			<div id="rpost_entires[]">
			<input size="40" maxlength="100" type="text" value="<?php echo $wprp_related_post->anchor_text;?>" name="wprp_anchor_text[]" id="wprp_anchor_text[]"><input type="text" size="40" name="wprp_alt_text[]" value="<?php echo $wprp_related_post->alt_text;?>" id="wprp_alt_text[]"><input  onblur="javascript:wprp_set_element(this);"  size="5" type="text" value="<?php echo $wprp_related_post->related_post_id;?>" name="wprp_post_id[]" id="wprp_post_id[]"  class="thickbox" alt="#TB_inline?height=300&width=400&inlineId=wprp_post_suggestion" title="<?php echo $wprp_related_post->related_post_title; ?>"><span onclick="javascript:wprp_remove_row(this);"><a title="Remove This Row" alt="Remove This Row" href="javascript:void(0);">X</a></span>
		</div>
			<?php 
			}
		}
		?>		
		</div>	
	</div>
	<div id="wprp_post_suggestion" style="display: none; z-index: 300002; outline: 0px none; position: absolute; height: auto; width: 480px; top: 67px; left: 265px;" class="ui-dialog ui-widget ui-widget-content ui-corner-all wp-dialog ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-wp-link">
	
	<table width="100%">
	<caption>Post Suggestion</caption>
	<tr><td>Type some text to get the list of posts</td><td><input type="text" size="40" onkeyup="javascript:wprp_suggest_post(this.value);"></td></tr>
	<tr><td>Post Type</td><td>
	<select name="wprp_post_type" id="wprp_post_type">
	<option value="post">Post</option>
	<option value="page">Page</option>
</select>

	</td></tr>
	<tr><td>Post Category</td><td>
	<?php 
	$wprp_cats = get_categories();	
	?>
	<select name="wprp_cat_list" id="wprp_cat_list">
	<option value="all">All</option>
	<?php 
		foreach($wprp_cats as $cat_obj){
			echo "<option value='".$cat_obj->cat_ID."'>".$cat_obj->name."</option>";
		}
	?>
	</select>
	</td></tr>
	</table>	
	<center>
	<h3>Click any post to set the post id</h3>	</center>
	<div id="wprp_suggested_posts">
		
	</div>

	</div>
	<?php 
}

function wprp_rposts_box(){
	if(function_exists('add_meta_box')){ 
		add_meta_box('wprp_cust_atext','Related Posts','wprp_add_rposts','post');
		add_meta_box('wprp_cust_atext','Related Posts','wprp_add_rposts','page');
	}
	else{ // for wp version < 2.5
		add_action('dbx_post_advanced', 'wprp_add_rposts');
		add_action('dbx_page_advanced', 'wprp_add_rposts');
	}
}

function wprp_display_related_posts($content){
	
	if(is_single() || is_page()){
		$related_table = $GLOBALS['table_prefix']."relatedposts";
		global $post;
		$now_post_ = $post->ID;
		//delete related posts for post revisions
		if(WP_POST_REVISIONS && function_exists('wp_get_post_revisions')){
			wprp_remove_post_revisions($now_post_);
		}
		
		
		//getting current posts details
		$result_related = mysql_query("SELECT related_post_id,anchor_text,alt_text FROM $related_table WHERE post_id=".$now_post_." ORDER BY r_order") or die(mysql_error());
		if(mysql_num_rows($result_related)){
			$content .= '<fieldset>
			<legend align="left">Related Posts</legend><ul>';
			while($related_row = mysql_fetch_assoc($result_related)){
				$urltopost = get_permalink($related_row['related_post_id']);
				$content .= '<li><a href="'.$urltopost.'" title="'.trim($related_row['alt_text']).'">'.trim($related_row['anchor_text']).'</a></li>';
			}
			$content .= '</ul></fieldset>';
		}
		
	}
	
	return $content;
}

/**
 * Function to remove the post revisions ids in the related post entries
 * @param int $postid post id
 */

function wprp_remove_post_revisions($postid){
	$revisions = wp_get_post_revisions($postid);
	global $table_prefix;
	if(count($revisions) > 0){
		$postids = array_keys($revisions);
		if(count($postids) > 0){
			$all_post_ids = implode(",",$postids);
			$sql = "DELETE FROM ".$table_prefix."relatedposts WHERE post_id IN($all_post_ids)";
			mysql_query($sql);
		}
	}
}

/**
 * Function to delete the related entries while a post is deleted
 * @param int $post_id post id
 */

function wprp_remove_related_for_deleted_posts($post_id){
	global $table_prefix;
	//delete  source
	$sql_source = "DELETE FROM ".$table_prefix."relatedposts WHERE post_id = $post_id";
	mysql_query($sql_source);
	//delete destinations
	$sql_destination = "DELETE FROM ".$table_prefix."relatedposts WHERE related_post_id = $post_id";
	mysql_query($sql_destination);
}

if($wp_version < '3.1.0'){
function wp_link_query( $args = array() ) {
	$pts = get_post_types( array( 'publicly_queryable' => true ), 'objects' );
	$pt_names = array_keys( $pts );

	$query = array(
		'post_type' => $pt_names,
		'suppress_filters' => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'post_status' => 'publish',
		'order' => 'DESC',
		'orderby' => 'post_date',
		'posts_per_page' => 20,
	);

	$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;

	if ( isset( $args['s'] ) )
		$query['s'] = $args['s'];

	$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

	// Do main query.
	$get_posts = new WP_Query;
	$posts = $get_posts->query( $query );
	// Check if any posts were found.
	if ( ! $get_posts->post_count )
		return false;

	// Build results.
	$results = array();
	foreach ( $posts as $post ) {
		if ( 'post' == $post->post_type )
			$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
		else
			$info = $pts[ $post->post_type ]->labels->singular_name;

		$results[] = array(
			'ID' => $post->ID,
			'title' => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
			'permalink' => get_permalink( $post->ID ),
			'info' => $info,
		);
	}

	return $results;
}
}



function wp_ajax_wprp_post_suggestion() {
	global $wpdb,$wp_version; // this is how you get access to the database
	$str = stripslashes($_POST['post_text']);
	#$cat = (int) $_POST['post_cat'];
	$type = $_POST['post_type'];
	if($wp_version >= '3.1.0'){
		require_once ABSPATH . 'wp-admin/includes/internal-linking.php';
	}
	$args = array("s" => $str,"post_type" => array($type));
	$results = wp_link_query( $args );
	
	if ( ! isset( $results ) )
		die( '0' );
	echo json_encode( $results );
	echo "\n";
	exit;
	
}
//===========================================
//function for pagination
function doPaging($total,$perpage,$page,$curr_page,$extra='')
{
echo "<table>";
echo "<tr>";

$total_pages = round($total/$perpage)+1;

    if($total_pages <= 20)
	{
	               if($total_pages > 1)
				   {
        		 for($i=1;$i<=$total_pages;$i++)	
				   {
				   $reqPage = $page;
    				      if($i == $curr_page)
						   {
						  echo "<td style='font-family:tahoma;font-size:12px;color:#722308'><b>[$i]</b></td>"; 
						   }
						   else
						   {
						   echo "<td style='font-family:tahoma;font-size:12px;color:#722308'>[<a class='admin_links' $extra href='$reqPage&curr_page=$i'>$i</a>]</td>";
						   }
				   }
				   }
	}
	else
	{
	

   $start = $curr_page - 5;
   $start = ($start > 0)?$start:1;
   

	if(($start+20) < $total_pages)
	{
	$remain = $start+20;
	}
	else
	{
	$remain = $total_pages;
	}
	
	for($i=$start;$i<=$remain;$i++)	
				   {
				   $reqPage = $page;
    				      if($i == $curr_page)
						   {
						  echo "<td style='font-family:tahoma;font-size:12px;color:#722308'><b>[$i]</b></td>"; 
						   }
						   else
						   {
						   echo "<td style='font-family:tahoma;font-size:12px;color:#722308'>[<a class='admin_links' href='$reqPage&curr_page=$i'>$i</a>]</td>";
						   }
				   }
	
	
	
	
	
	
	
	}
echo "</tr>";
echo "</table>";	
}
//=======================
function wprp_show_related_posts(){
	global $table_prefix,$wp_query;
	//getting total records
	$total_records = mysql_query("SELECT count(r_id) AS Total FROM ".$table_prefix."relatedposts");
	$curr_page = (isset($_GET['curr_page'])) ? $_GET['curr_page'] : 1;
	$perpage = 10;
	$start = ($curr_page-1) * $perpage;
	$result = mysql_query("SELECT r_id,post_id,related_post_id,anchor_text,alt_text FROM ".$table_prefix."relatedposts ORDER BY post_id LIMIT $start,$perpage") or die(mysql_error());
	$wprp_data = array();
	
	while($row = mysql_fetch_array($result)){
		$wprp_data[$row['post_id']][] = $row;		
	}

	
	
	

	echo '<div class="icon32" id="icon-options-general"><br></div><h2>Related Posts</h2>';
	echo '<center>';
		doPaging(mysql_result($total_records,0,'Total'),$perpage,'options-general.php?page=wprp_related_posts',$curr_page);
	echo '</center>';
	
	echo '<BR><table cellspacing="0" class="wp-list-table widefat fixed posts">';
	
	echo '<thead><tr><th>Source Post</th><th>Destination Posts</th><th>Anchor Text</th><th>Alt Text</th><th>Edit</th></tr></thead>';
	echo '<tfoot><tr><th>Source Post</th><th>Destination Posts</th><th>Anchor Text</th><th>Alt Text</th><th>Edit</th></tr></tfoot><tbody>';
	
	foreach($wprp_data as $source_post_id => $wprp_r_data){
		if(!wprp_valid_post($source_post_id))
			continue;
		
		$dest_post_title = '<ol>';
		$anchor_text = '<ol>';
		$alt_text = '<ol>';
		$total_related_posts = 0;
		foreach($wprp_r_data as $row_data){
			$dest_post_title .= '<li>' . get_the_title($row_data['related_post_id']) .'</li>';
			$anchor_text .= '<li>' . $row_data['anchor_text'] .'</li>';
			$alt_text .= '<li>' . $row_data['alt_text'] .'</li>';
			$total_related_posts++;
		}
		$dest_post_title .= '</ol>';
		$anchor_text .= '</ol>';
		$alt_text .= '</ol>';
		
		echo '<tr style="color:#464646"><td>'.get_the_title($source_post_id).' ('.$total_related_posts.')</td><td>'.$dest_post_title.'</td><td>'.$anchor_text.'</td><td>'.$alt_text.'</td><td><a href="post.php?post='.$source_post_id.'&action=edit">Edit</a></td></tr>';
	}
	echo '</tbody></table>';?>
	<BR><BR>
	<div>
	<a href="http://www.wphelpforum.com/" title="Get help, report issues on this plugin" target="_blank"><b>Plugin Help / Discussion</b></a> | <a href="http://www.yourtemplatezone.com/wordpress/" title="Download free & buy cheapest wordpress themes." target="_blank"><b>Cheap Wordpress Themes</b></a>
	</div> 	
	<?php 
	

}

function wprp_admin_menu(){
	add_options_page('Related Posts', 'Related Posts', 'manage_options', 'wprp_related_posts', 'wprp_show_related_posts');
}
add_action('admin_menu', 'wprp_admin_menu');
add_action('wp_ajax_wprp_post_suggestion', 'wp_ajax_wprp_post_suggestion');
add_action('admin_menu', 'wprp_rposts_box');
add_action('admin_head','wprp_js_code');
add_action('edit_post', 'wprp_save_rposts');
add_action('publish_post', 'wprp_save_rposts');
add_action('save_post', 'wprp_save_rposts');
add_action('edit_page_form', 'wprp_save_rposts');
add_action('the_content','wprp_display_related_posts');
add_action('delete_post', 'wprp_remove_related_for_deleted_posts');
register_activation_hook( __FILE__, 'wprp_install_related');
register_deactivation_hook( __FILE__, 'wprp_related_uninstall');
?>