<?php
// Boring stuff..
define('IN_MYBB', 1);

$templatelist = 'timeline_main,timeline_container,timeline_posts,timeline_about,timeline_friends,timeline_threads,timeline_friends_row,timeline_threads_row,timeline_posts_row';
require_once './global.php';

//Errors
if ((int) $mybb->user['uid'] < 1) {
	error_no_permission();
}

if(!isset($settings['timeline_enable'])) {
	error_no_permission();
}

if(isset($mybb->input['coverpic']) && $mybb->input['do_coverpic']=="change") {
	$coverpic = trim(htmlentities(strip_tags($mybb->input['coverpic'])));
	$myuid = (int)$mybb->user['uid'];
	$baseimg = basename($coverpic);
	$baseimg = explode(".", $baseimg);
	$baseimg = end($baseimg);
	if($baseimg=="jpg" OR $baseimg=="jpeg" OR $baseimg=="png" OR $baseimg=="bmp") {
		$sql = $db->simple_select("timeline_cp","*","uid='$myuid'");
		$rows = $db->num_rows($sql);
		if($rows>0) {
			$update_array = array(
				'coverpic' => $db->escape_string($coverpic),
			);
			$db->update_query("timeline_cp",$update_array,"uid='$myuid'");
		} else {
			$insert_array = array(
				'uid' => $myuid,
				'coverpic' => $db->escape_string($coverpic)
			);
			$db->insert_query("timeline_cp",$insert_array);
		}
	}

	header("Location: ".$_SERVER['HTTP_REFERER']);
}


if($mybb->usergroup['canviewprofiles'] == 0)
{
	error_no_permission();
}

$uid = $mybb->get_input('uid', MyBB::INPUT_INT);
$uid = (int)$uid;
if($uid==NULL) {
	$uid = $mybb->user['uid'];
}

if($uid==$mybb->user['uid']) {
	$coverpic_change = '<span class="fa fa-pencil-square cpchange"></span>
		<form class="coverpicForm" action="">
			<input type="text" name="coverpic" placeholder="Add Image URL" required>
			<input type="hidden" name="do_coverpic" value="change">
			<input type="submit" value="Change">
		</form>';
}

//Cover
$coverquery = $db->simple_select("timeline_cp","*","uid='$uid'");
$coverrows = $db->num_rows($coverquery);
$coverrow = $db->fetch_array($coverquery);
if($coverrows>0) {
	$profile_cover_bg = 'url("'.$coverrow['coverpic'].'")';
} else {
	$profile_cover_bg = "rgb(225, 228, 242)";
}

//Users
$query = $db->simple_select("users","*","uid='$uid'");
$row = $db->fetch_array($query);

//Profile Pic
$profile_pic = $row['avatar'];
if($profile_pic==NULL) {
	$profile_pic = "images/default_avatar.png";
}
$profile_username = $row['username'];
$profile_usergroup = (int)$row['usergroup'];
$profile_birth = $row['birthdate'];
$profile_birthdate = date("F d", strtotime($profile_birth));
$profile_email = $row['email'];
if($row['hideemail']==1) {
	$profile_email = "(Hidden)";
}
$profile_joined = date("Y-m-d",$row['regdate']);
$profile_lastactive = my_date('relative', $row['lastactive']);
$profile_timespent = nice_time($row['timeonline']);
$profile_referred = $row['referrals'];
if($profile_referred==NULL) {
	$profile_referred = 0;
}
$profile_reputation = $row['reputation'];
$profile_reputation_details = '(<a href="reputation.php?uid='.$uid.'">Details</a>)';

//Posts Count
$daysreg = (TIME_NOW - $row['regdate']) / (24*3600);
if($daysreg < 1)
{
	$daysreg = 1;
}
$profile_postnum = $row['postnum'];
$profile_ppd = round(($profile_postnum/$daysreg),2);
if($profile_ppd > $row['postnum'])
{
	$profile_ppd = $row['postnum'];
}
$profile_totalposts = $profile_postnum." (".$profile_ppd." posts per day)";
$profile_totalposts_link = '(<a href="search.php?action=finduser&uid='.$uid.'">Find All Posts</a>)';

//Threads Count
$profile_threadnum = $row['threadnum'];
$profile_tpd = round(($profile_threadnum/$daysreg),2);
if($profile_tpd > $row['threadnum'])
{
	$profile_tpd = $row['threadnum'];
}
$profile_totalthreads = $profile_threadnum." (".$profile_tpd." threads per day)";
$profile_totalthreads_link = '(<a href="search.php?action=finduserthreads&uid='.$uid.'">Find All Threads</a>)';

//Warning Level
if($mybb->settings['maxwarningpoints'] < 1)
{
	$mybb->settings['maxwarningpoints'] = 10;
}

$profile_warn = round($row['warningpoints']/$mybb->settings['maxwarningpoints']*100);

if($profile_warn > 100)
{
	$profile_warn = 100;
}
$profile_warn = get_colored_warning_level($profile_warn);
$profile_warn = '<a href="warnings.php?uid='.$uid.'">'.$profile_warn.'</a>';
$profile_warn_link = '(<a href="warnings.php?action=warn&uid='.$uid.'">Warn</a>)';

//Usergroup
$ugquery = $db->simple_select("usergroups","*","gid='$profile_usergroup'");
$result = $db->fetch_array($ugquery);
$profile_namestyle = $result['namestyle'];
$profile_usertitle_main = $result['title'];
$profile_usertitle = str_replace("{username}", "{$profile_usertitle_main}", $profile_namestyle);

//Buddy Count
$profile_buddylist = $row['buddylist'];
$buddylist_count = explode(",", $profile_buddylist);
$buddy_count = count($buddylist_count);
if($row['buddylist']==0) {
	$buddy_count = 0;
}

//Posts Tab Part
$post_query = $db->simple_select("posts","*","uid='$uid'",array("order_by"=>'dateline',"order_dir"=>'DESC','limit'=>20));
$rows = $db->num_rows($post_query);
if($rows>0) {
	while($postrow=$db->fetch_array($post_query)) {
		$post_tid = (int)$postrow['tid'];
		$sql = $db->simple_select("threads","*","tid='$post_tid'");
		$result = $db->fetch_array($sql);		
		$post_firstpost = $result['firstpost'];
		$post_pid = $postrow['pid'];

		if($post_pid==$post_firstpost) {
		} else {
			$post_uid = $postrow['uid'];
			$post_thread_uid = $result['uid'];
			if($post_uid==$post_thread_uid) {
				$replied_text = "own";
			} else {
				$replied_text = "a";
			}
			$post_url = 'showthread.php?tid='.$post_tid.'&pid='.$post_pid.'#pid'.$post_pid;	
			$post_thread = $result['subject'];
			$post_thread = '<a href="showthread.php?tid='.$post_tid.'">'.$post_thread.'</a>';
			$post_post = $postrow['message'];
			if(strlen($post_post)>500) {
				$post_post = mb_substr($post_post,0, 500)."...<b>See More</b>.";
			}
			$tag = "img";
		    $re = sprintf("/\[(%s)\](.+?)\[\/\\1\]/", preg_quote($tag));
		    preg_match_all($re, $post_post, $matches);
		    $matches = $matches[2];
			$matches_size = sizeof($matches);
			if($matches_size>0) {
				$post_post = str_replace("[".$tag."]".$matches[0]."[/".$tag."]", '<img src="'.$matches[0].'"/>', $post_post);
			}

			$post_date = my_date('relative', $postrow['dateline']);
			eval("\$post_lists .= \"".$templates->get("timeline_posts_row")."\";");
		}

	}
}



//About Tab Part

//UserFields
$ufquery = $db->simple_select("userfields","*","ufid='$uid'");
$result = $db->fetch_array($ufquery);
$profile_location = ucfirst($result['fid1']);
if($profile_location==NULL) {
	$profile_location = "(Undisclosed)";
}
$profile_bio = $result['fid2'];
if($profile_bio==NULL) {
	$profile_bio = "(Undisclosed)";
}
$profile_gender = $result['fid3'];
if($profile_gender==NULL) {
	$profile_gender = "(Undisclosed)";
}
$profile_pm = '<a href="private.php?action=send&uid='.$uid.'">Send PM</a>';



//Buddy Part
for($i=0;$i<$buddy_count;$i++) {
	$tempuid = (int)$buddylist_count[$i];
	$tempquery = $db->simple_select("users","*","uid='$tempuid'");
	$temprow = $db->fetch_array($tempquery);
	$friend_username = $temprow['username'];
	$friend_username = '<a href="member.php?action=profile&uid='.$tempuid.'">'.$friend_username.'</a>';
	$friend_profile_pic = $temprow['avatar'];
	if($friend_profile_pic==NULL) {
		$friend_profile_pic = "images/default_avatar.png";
	}
	//Usergroup
	$friend_usergroup = (int)$temprow['usergroup'];
	$ugquery = $db->simple_select("usergroups","*","gid='$friend_usergroup'");
	$result = $db->fetch_array($ugquery);
	$friend_namestyle = $result['namestyle'];
	$friend_usertitle_main = $result['title'];
	$friend_usertitle = str_replace("{username}", "{$friend_usertitle_main}", $friend_namestyle);
	$my_buddylist_arr = explode(",", $mybb->user['buddylist']);
	if($mybb->user['uid']==$tempuid) {
		$friend_status = "UserCP";
		$friend_status = '<div class="friend-button"><a href="usercp.php">'.$friend_status.'</a></div>';
	} else if(in_array($tempuid, $my_buddylist_arr)) {
		$friend_status = "Remove";
		$friend_status = '<div class="friend-button"><a href="usercp.php?action=do_editlists&amp;my_post_key='.$mybb->post_code.'&amp;manage=buddy&amp;delete='.$tempuid.'">'.$friend_status.'</a></div>';
	} else {
		$friend_status = "Add Friend";
		$friend_status = '<div class="friend-button add"><a href="usercp.php?action=do_editlists&add_username='.$temprow['username'].'&my_post_key='.$mybb->post_code.'">'.$friend_status.'</a></div>';
	}


	eval("\$friend_row .= \"".$templates->get("timeline_friends_row")."\";");
}

//Threads Part
$thread_query = $db->simple_select("threads","*","uid='$uid'",array("order_by"=>'dateline',"order_dir"=>'DESC',"limit"=>20));
$rows = $db->num_rows($thread_query);
if($rows>0) {
	while($row=$db->fetch_array($thread_query)) {
		$thread_forum_id = (int)$row['fid'];
		$sql = $db->simple_select("forums","*","fid='$thread_forum_id'");
		$result = $db->fetch_array($sql);
		$thread_forum = $result['name'];
		$thread_forum = '<a href="forumdisplay.php?fid='.$thread_forum_id.'">'.$thread_forum.'</a>';
		$thread_date = my_date('relative', $row['dateline']);
		$thread_title = $row['subject'];
		$thread_title = '<a href="showthread.php?tid='.$row['tid'].'">'.$thread_title.'</a>';
		$thread_views = $row['views'];
		$thread_replies = $row['replies'];

		//Posts
		$thread_firstpost_pid = (int)$row['firstpost'];
		$sql = $db->simple_select("posts","*","pid='$thread_firstpost_pid'");
		$result = $db->fetch_array($sql);
		$thread_post = $result['message'];
		if(strlen($thread_post)>500) {
			$thread_post = mb_substr($thread_post,0, 500)."...";
		}
		$tag = "img";
	    $re = sprintf("/\[(%s)\](.+?)\[\/\\1\]/", preg_quote($tag));
	    preg_match_all($re, $thread_post, $matches);
	    $matches = $matches[2];
		$matches_size = sizeof($matches);
		if($matches_size>0) {
			$thread_post = str_replace("[".$tag."]".$matches[0]."[/".$tag."]", '<img src="'.$matches[0].'"/>', $thread_post);
		}

		eval("\$thread_lists .= \"".$templates->get("timeline_threads_row")."\";");			
	}
}

$content = '';
eval("\$profile_posts = \"" . $templates->get('timeline_posts') . "\";");
eval("\$profile_about = \"" . $templates->get('timeline_about') . "\";");
eval("\$profile_friends = \"" . $templates->get('timeline_friends') . "\";");
eval("\$profile_threads = \"" . $templates->get('timeline_threads') . "\";");
eval("\$timeline_container = \"" . $templates->get('timeline_container') . "\";");
eval("\$content = \"" . $templates->get('timeline_main') . "\";");

output_page($content);