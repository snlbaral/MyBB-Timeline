<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

$plugins->add_hook('member_profile_start', 'timeline_cp_start');

//Plugin Information
function timeline_cp_info()
{
	return array(
		'name' => 'MyBB Timeline',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugin allows to have timeline instead of mybb default member view',
		'version' => '1.0',
		'compatibility' => '18*',
		'guid' => '',
	);
}

function timeline_cp_install()
{
	global $db;
	$collation = $db->build_create_table_collation();
	if (!$db->table_exists('timeline_cp')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "timeline_cp(
                        id serial,
                        uid int NOT NULL,
                        coverpic varchar(255) NOT NULL DEFAULT '',
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "timeline_cp(
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `coverpic` varchar(255) NOT NULL DEFAULT '',
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}
}

function timeline_cp_is_installed()
{
	global $db;
	return $db->table_exists('timeline_cp');
}

//Plugin Uninstall
function timeline_cp_uninstall()
{
	global $db;
	if ($db->table_exists('timeline_cp')) {
		$db->drop_table('timeline_cp');
	}
}


function timeline_cp_activate()
{
	global $db, $mybb, $settings;

	//Admin CP Settings
	$timeline_group = array(
		'gid' => (int)'',
		'name' => 'timeline_cp',
		'title' => 'MyBB Timeline',
		'description' => 'Settings for MyBB Timeline',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$timeline_group);
	$gid = $db->insert_id();

	//Enable or Disable
	$timeline_enable = array(
		'sid' => 'NULL',
		'name' => 'timeline_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);


	$db->insert_query('settings',$timeline_enable);
	rebuild_settings();

	$q = $db->simple_select("templategroups", "COUNT(*) as count", "title = 'MyBB Timeline'");
	$c = $db->fetch_field($q, "count");
	$db->free_result($q);
	
	if($c < 1)
	{
		$ins = array(
			"prefix"		=> "timeline",
			"title"			=> "MyBB Timeline",
		);
		$db->insert_query("templategroups", $ins);
	}

	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_main',
		'template' => $db->escape_string('

<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - Timeline</title>
		{$headerinclude}
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
		<link rel="stylesheet" type="text/css" href="inc/plugins/MybbStuff/timeline/timeline.css">
	</head>
	<body>
		{$header}
		{$timeline_container}
		{$footer}
		<script type="text/javascript" src="jscripts/timeline.js"></script>
	</body>
</html>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_container',
		'template' => $db->escape_string('
<div class="top-profile">
	<div class="profile-cover" style=\'background:{$profile_cover_bg}\'>
		<div class="profile-pic">
			{$coverpic_change}
			<img src="{$profile_pic}"/>
			<div class="profile-username">{$profile_username}</div>
			<div class="profile-usergroup">{$profile_usertitle}</div>
		</div>
	</div>
	<div class="profile-tabs">
		<div class="profile-posts active">
		Posts (<span class="smalltext">{$profile_postnum}</span>)
		</div>
		<div class="profile-about">
		About
		</div>
		<div class="profile-friends">
		Buddies (<span class="smalltext">{$buddy_count}</span>)
		</div>
		<div class="profile-threads">
		Threads (<span class="smalltext">{$profile_threadnum}</span>)
		</div>
	</div>
</div>
<div class="profile-contents">
	<div class="profile-posts-content active">
		{$profile_posts}
	</div>
	<div class="profile-about-content">
		{$profile_about}
	</div>
	<div class="profile-friends-content">
		{$profile_friends}
	</div>
	<div class="profile-threads-content">
		{$profile_threads}
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_posts',
		'template' => $db->escape_string('
<div class="posts-container">
	{$post_lists}
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_posts_row',
		'template' => $db->escape_string('
<div class="post-row">
	<div class="post-head">
		<table class="post-lists-table">
			<tr>
				<td rowspan="2">
					<img src="{$profile_pic}"/>
				</td>
				<td>
					<span class="post-lists-username">{$profile_username}</span> replied to {$replied_text} thread "{$post_thread}".
				</td>
			</tr>
			<tr>
				<td>
					<span class="smalltext postdate">{$post_date}</span>
				</td>
			</tr>
		</table>		
	</div>
	<div class="post-content">
		<a href="{$post_url}"><div class="post-post">{$post_post}</div></a>	
	</div>
	<div class="post-stats">
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_about',
		'template' => $db->escape_string('
<div class="about-container">
	<div class="about-menu">
		<span class="about-title-font">About</span>
		<div class="about-overview current">Overview</div>
		<div class="about-contact">Contact</div>
		<div class="about-activity">Activity & Statistics</div>
	</div>
	<div class="about-info">
		<div class="about-info-overview act">
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-user"></i>
						</td>
						<td>
							{$profile_gender}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Gender</span>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-birthday-cake"></i>
						</td>
						<td>
							{$profile_birthdate}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Birth Date</span>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-map-marker"></i>
						</td>
						<td>
							{$profile_location}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Location</span>
						</td>
					</tr>
				</table>
			</div>				
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-paragraph"></i>
						</td>
						<td>
							{$profile_bio}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Bio</span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="about-info-contact">
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-envelope"></i>
						</td>
						<td>
							{$profile_email}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Email</span>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<table cellpadding="5">
					<tr>
						<td rowspan="2">
							<i class="fa fa-comment"></i>
						</td>
						<td>
							{$profile_pm}
						</td>
					</tr>
					<tr>
						<td>
							<span class="smalltext">Private Message</span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="about-info-activity">
			<table class="about-info-statistics-table" cellpadding="10">
				<tr>
					<td>
						<b>Joined</b>:
					</td>
					<td>
						{$profile_joined}
					</td>
				</tr>
				<tr>
					<td>
						<b>Last Active</b>:
					</td>
					<td>
						{$profile_lastactive}
					</td>
				</tr>
				<tr>
					<td>
						<b>Total Post</b>:
					</td>
					<td>
						{$profile_totalposts}
						<br/>{$profile_totalposts_link}
					</td>
				</tr>
				<tr>
					<td>
						<b>Total Threads</b>:
					</td>
					<td>
						{$profile_totalthreads}
						<br/>{$profile_totalthreads_link}
					</td>
				</tr>
				<tr>
					<td>
						<b>Time Spent</b>:
					</td>
					<td>
						{$profile_timespent}
					</td>
				</tr>
				<tr>
					<td>
						<b>Members Referred</b>:
					</td>
					<td>
						{$profile_referred}
					</td>
				</tr>
				<tr>
					<td>
						<b>Reputaion</b>:
					</td>
					<td>
						{$profile_reputation} {$profile_reputation_details}
					</td>
				</tr>
				<tr>
					<td>
						<b>Warn</b>:
					</td>
					<td>
						{$profile_warn} {$profile_warn_link}
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_friends',
		'template' => $db->escape_string('
<div class="friends-container">
	<div class="about-title-font">
		<span class="left">Friends</span>
		<a href="usercp.php?action=editlists"><span class="right">Friend Request</span></a>
	</div>
	<br style="clear:both"/><br/>
	<div class="friends-row">
		{$friend_row}
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_friends_row',
		'template' => $db->escape_string('
<div class="friend-row">
	<table cellpadding="10">
		<tr>
			<td>
				<img src="{$friend_profile_pic}"/>
			</td>
			<td style="width:52%">
				<span class="friend-username">
					{$friend_username}
				</span>
				<br/>
				<span class="smalltext">
					{$friend_usertitle}
				</span>
			</td>
			<td style="width:30%">
				{$friend_status}
			</td>
		</tr>
	</table>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_threads',
		'template' => $db->escape_string('
<div class="threads-container">
	{$thread_lists}
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'timeline_threads_row',
		'template' => $db->escape_string('
<div class="thread-row">
	<div class="thread-head">
		<table class="thread-lists-table">
			<tr>
				<td rowspan="2">
					<img src="{$profile_pic}"/>
				</td>
				<td>
					<span class="thread-lists-username">{$profile_username}</span> posted a thread in {$thread_forum}.
				</td>
			</tr>
			<tr>
				<td>
					<span class="smalltext threaddate">{$thread_date}</span>
				</td>
			</tr>
		</table>
	</div>
	<div class="thread-content">
		<div class="thread-title">{$thread_title}</div>
		<div class="thread-post">{$thread_post}</div>
	</div>
	<div class="thread-stats">
		<span class="thread-views"><i class="fa fa-eye"></i> {$thread_views}</span>
		<span class="thread-replies"><i class="fa fa-comment"></i> {$thread_replies}</span>
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);




}

//Deactivate Plugin
function timeline_cp_deactivate()
{
	global $db, $mybb, $settings;
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('timeline_cp')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('timeline_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."templategroups WHERE prefix IN ('timeline')");
	$db->query("DELETE from ".TABLE_PREFIX."templates WHERE title LIKE 'timeline%'");
	rebuild_settings();
}

function timeline_cp_start()
{
	global $db, $mybb, $settings;
	if($settings['timeline_enable']==1) {
		header("Location: timeline.php?action=profile&uid=".$mybb->input['uid']);		
	}
}