<?php
/*
Plugin Name: Related Posts/Pages
Plugin URI: http://www.wphelpforum.com/
Description: Will add related links for posts & pages with your own anchor text
Author: Thamizhchelvan
Version: 1.0
Author URI: http://www.wphelpforum.com/
*/

if(!function_exists('related_uninstall'))
{
function related_uninstall()
{
mysql_query("DROP TABLE IF EXISTS `".$GLOBALS['table_prefix']."relatedposts`") or (mysql_error());
}
 }
 
if(!function_exists('make_related'))
{
function make_related($content)
{



if(is_single() || is_page())
{
$related_table = $GLOBALS['table_prefix']."relatedposts";
global $post;
$now_post_ = $post->ID;
//getting current posts details
$result_related = mysql_query("SELECT related_post_id,anchor_text,alt_text FROM $related_table WHERE post_id=".$now_post_." ORDER BY r_order DESC") or die(mysql_error());
if(mysql_num_rows($result_related))
{

$content .= '<fieldset>
<legend align="left">Related Posts</legend><ul>';
while($related_row = mysql_fetch_assoc($result_related))
{
$urltopost = get_permalink($related_row['related_post_id']);
$content .= '<li><a href="'.$urltopost.'" title="'.trim($related_row['alt_text']).'">'.trim($related_row['anchor_text']).'</a></li>';
}
$content .= '</ul></fieldset>';


}


}
return $content;
}
}


add_action('the_content', 'make_related');


class admin_add_related
{
	function init()
	{
	mysql_query('CREATE TABLE IF NOT EXISTS `'.$GLOBALS['table_prefix'].'relatedposts` (
  `r_id` int(11) NOT NULL auto_increment,
  `post_id` int(11) NOT NULL,
  `related_post_id` int(11) NOT NULL,
  `anchor_text` varchar(255) NOT NULL,
  `alt_text` varchar(100) NOT NULL,
  `r_order` int(11) NOT NULL,
  PRIMARY KEY  (`r_id`)
);
') or die(mysql_error());

		add_action('admin_menu', array('admin_add_related', 'add_option_page'));
		
	} 


	
	function add_option_page()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_options_page(
					__('Related&nbsp;Posts'),
					__('Related&nbsp;Posts'),
					7,
					str_replace("\\", "/", __FILE__),
					array('admin_add_related', 'display_options')
					);
		}
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
//=======================================================================





	function display_options()
	{
	$custom_error='';
	$post_table = $GLOBALS['table_prefix']."posts";
	$related_table = $GLOBALS['table_prefix']."relatedposts";
	
	//taking deletion
	if(isset($_GET['mode']) && $_GET['mode'] == 'delete')
	{
	if(mysql_query("DELETE FROM $related_table WHERE r_id=$_GET[r_id]"))
	{
	$custom_error='Deleted';
	}
		
	}
	
	//taking insertion/updation
	 if(isset($_POST['btnAction']))
	  {
	  
	    if($_POST['btnAction'] == 'ADD')
		  {
		    //check if already exists
			$result = mysql_query("SELECT post_id FROM $related_table WHERE post_id = $_POST[post_id] AND related_post_id = $_POST[linkto_id]");			
			 if(mysql_num_rows($result) > 0)
			  {
			$custom_error='Already Exists';		    
			  }
			  else
			  {
			 if(mysql_query("INSERT INTO $related_table VALUES('','$_POST[post_id]','$_POST[linkto_id]','".trim($_POST['anchor_text'])."','".trim($_POST['alt_text'])."','$_POST[order]')"))
			  { 
			 $custom_error='Added';		   
			 }
			 else
			 {
			 $custom_error = 'Error:'.mysql_error();		
			 }
			  }
 
		  }
	  
	  else if($_POST['btnAction'] == 'EDIT')
		  {
		    //check if already exists
			
			 if(mysql_query("UPDATE $related_table SET post_id='$_POST[post_id]',related_post_id='$_POST[linkto_id]',anchor_text='".trim($_POST['anchor_text'])."',alt_text='".trim($_POST['alt_text'])."',r_order='$_POST[order]' WHERE r_id=$_GET[r_id]"))
			  { 
			 $custom_error='Updated';		   
			 }
			 else
			 {
			 $custom_error = 'Error:'.mysql_error();		
			 }
			 
 
		  }
	  
	  
	  
	  
	  
	  
	  }
	
	
	
//============================================================================
//displaying all the added values

//getting total records
$total_records = mysql_query("SELECT count(r_id) AS Total FROM $related_table");

$curr_page = (isset($_GET['curr_page']))?$_GET['curr_page']:1;
$perpage = 10;
$start = ($curr_page-1)*$perpage;


$disp_values = mysql_query("SELECT R.r_id,R.post_id,P.post_title AS source_post_title,P1.post_title AS dest_post_title,R.anchor_text FROM $post_table P,$post_table P1,$related_table R WHERE R.post_id=P.ID AND R.related_post_id=P1.ID LIMIT $start,$perpage") or die(mysql_error());
echo '<center>';
admin_add_related::doPaging(mysql_result($total_records,0,'Total'),$perpage,'options-general.php?page=related-posts.php',$curr_page);
echo '</center>';
echo '<BR><table width="100%" style="border-collapse:collapse" border="1">';
echo '<tr style="background-color:#E4F2FD;color:#464646"><td>Serial</td><td>Source Post</td><td>Destination Post</td><td>Anchor Text</td><td>Edit</td><td>Delete</td></tr>';
$serial = ($curr_page-1)*$perpage;
while($disp_row = mysql_fetch_assoc($disp_values))
{
$serial++;
echo '<tr style="color:#464646"><td>'.$serial.'</td><td>'.$disp_row['source_post_title'].'</td><td>'.$disp_row['dest_post_title'].'</td><td>'.$disp_row['anchor_text'].'</td><td><a href="options-general.php?page=related-posts.php&mode=edit&r_id='.$disp_row['r_id'].'&id='.$disp_row['post_id'].'">Edit</a></td><td><a href="options-general.php?page=related-posts.php&mode=delete&r_id='.$disp_row['r_id'].'">Delete</a></td></tr>';
}
echo '</table>';
//============================================================================	
	
	
	
	
	
	
	
	
	
	echo '<center><h3 style="color:red">'.$custom_error.'</h3></center>';
	echo '<form method="post" name="related_form" onsubmit="return check_post();">';
	echo '<center><table style="border-collapse:collapse;" width="70%"><tr><td>Select a Post or <span style="background-color:#E7E9F1">Page</span></td><td>';
		
		
		
		
		 $edit_ids = array();
		 $post_page_result = mysql_query("SELECT $post_table.ID,$post_table.post_title,$post_table.post_type FROM $post_table") or die(mysql_error());
         

		 echo '<select name="post_id"><option value="-1">Select</option>';
		 while($post_row = mysql_fetch_assoc($post_page_result))
		 {
		 $bgcolor = ($post_row['post_type'] == 'page')?'#E7E9F1':'#FFFFFF';
		 $selected = (isset($_GET['id']) && $_GET['id'] == $post_row['ID'])?' selected ':'';
		 echo '<option '.$selected.' style="background-color:'.$bgcolor.';font-weight:normal" value="'.$post_row['ID'].'">'.trim($post_row['post_title']).'</option>';	
		 }
		
	
	echo '</select></td></tr>';
	
	
	
	
	if(isset($_GET['mode']) && $_GET['mode'] == 'edit')
	 {
$get_edit_values = mysql_query("SELECT * FROM $related_table WHERE r_id=$_GET[r_id]");	 
$edit_row = mysql_fetch_assoc($get_edit_values);
$anchor_value = trim($edit_row['anchor_text']);
$alt_value = trim($edit_row['alt_text']);
$rid = $edit_row['related_post_id'];
$order = $edit_row['r_order'];
	 }
	
	
	
	
	

	
if(!isset($anchor_value))
{
$anchor_value = '';
}	

if(!isset($alt_value))
{
$alt_value = '';
}

echo '<tr><td>Add Anchor Text</td><td><input type="text" name="anchor_text" value="'.$anchor_value.'" size="40">*</td></tr>';		
echo '<tr><td>Add ALT Text</td><td><input type="text" name="alt_text" value="'.$alt_value.'" size="40"></td></tr>';		
echo '<tr><td>Link To Post/Page</td><td>';

echo '<select name="linkto_id">';

$post_page_result1 = mysql_query("SELECT $post_table.ID,$post_table.post_title,$post_table.post_type FROM $post_table") or die(mysql_error());

		 while($post_row = mysql_fetch_assoc($post_page_result1))
		 {
		 $selected = (isset($rid) && $rid == $post_row['ID'])?' selected ':'';
		 $bgcolor = ($post_row['post_type'] == 'page')?'#E7E9F1':'#FFFFFF';
    	 echo '<option '.$selected.' style="background-color:'.$bgcolor.';font-weight:normal" value="'.$post_row['ID'].'">'.trim($post_row['post_title']).'</option>';	
		 }
		
	
	echo '</select></td></tr>';




echo '</td></tr>';		
if(!isset($order))
{
$order = 0;
}	

	echo '<tr><td>Ordering</td><td><input type="text" name="order" value="'.$order.'" size="5"></td></tr>';		
	
echo '<tr><td>&nbsp;</td><td><input type="submit" value="';
if(isset($_GET['mode']) && $_GET['mode'] == 'edit')
{
echo 'EDIT';
}
else
{
echo 'ADD';
}
echo '" name="btnAction"></td></tr>';
		
	echo '</table></form><BR><a href="http://www.phpbits.info/wordpress-plugins/wp-related-post-plugin/">More About This Plugin</a> | <a href="http://www.freshprlinks.com/">Fresh PR Links[PR3]</a> | <a href="http://www.homecare.co.in/">Homecare Directory</a> | <a href="http://www.ukbusinesslistings.info/">UK Business Directory</a><BR><script language="javascript">
	
	
function check_post()
{
  if(document.related_form.post_id.value == -1)
   {
   alert("Choose a Post");
   document.related_form.post_id.focus();
   return false;
   }
if(document.related_form.anchor_text.value == "")
   {
   alert("Enter Anchor Text");
   document.related_form.anchor_text.focus();
   return false;
   }
   
   if(document.related_form.order.value != "" && isNaN(document.related_form.order.value))
   {
   alert("Enter Numeric Value Only");
   document.related_form.order.focus();
   return false;
   }
 return true;  
}
</script>';
	} 
	
	
	
	
	
	
	
	
	
	
} 

admin_add_related::init();
add_action('deactivate_related-posts.php', 'related_uninstall');
?>