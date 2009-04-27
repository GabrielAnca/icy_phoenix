<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

/**
* Create a profile link for the user with his own color
*/
// Mighty Gorgon: OLD COLORIZE FUNCTION - BEGIN
/*

//define('COLORIZE_CACHE_REFRESH', 2592000); // Caching time for user colors cache (Seconds) (60*60*24=86400) (86400*30=2592000)
function groups_colorize_username($user_id, $no_profile = false, $get_only_color_style = false)
{
	global $board_config, $db;

	// First check if user logged in
	if($user_id != ANONYMOUS)
	{
		// Change following two variables if you need to:
		$cache_update = 2592000;
		$cache_file = USERS_CACHE_FOLDER . POST_USERS_URL . '_' . $user_id . '.' . PHP_EXT;
		$update_cache = true;

		if(@file_exists($cache_file))
		{
			$last_update = 0;
			include($cache_file);
			if($last_update > (time() - $cache_update))
			{
				$update_cache = false;
			}
		}

		if($update_cache == true)
		{
			// Get the user info and see if they are assigned a color_group //
			$sql = "SELECT u.username, u.user_active, u.user_color, u.user_color_group
				FROM " . USERS_TABLE . " u
				WHERE u.user_id = '" . $user_id . "'
					LIMIT 1";
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$username = htmlspecialchars($row['username']);
			//$username = $row['username']);
			if($row['user_active'] == 0)
			{
				$style_color = '';
			}
			else
			{
				if($row['user_color'] != '')
				{
					$usercolor = check_valid_color($row['user_color']);
				}
				elseif($row['user_color_group'] != 0)
				{
					$sql_cg = "SELECT g.group_color FROM " . GROUPS_TABLE . " g
						WHERE g.group_id = '" . $row['user_color_group'] . "'
							LIMIT 1";
					$result_cg = $db->sql_query($sql_cg);
					$row_cg = $db->sql_fetchrow($result_cg);
					$usercolor = check_valid_color($row_cg['group_color']);
				}
				else
				{
					$usercolor = $board_config['active_users_color'];
				}
				$usercolor = ($usercolor != false) ? $usercolor : $board_config['active_users_color'];
			}

			if(@$f = fopen($cache_file, 'w'))
			{
				$username = addslashes($username);
				fwrite($f, '<' . '?php $last_update = ' . time() . '; $usercolor = \'' . $usercolor . '\'; $username = \'' . $username . '\'; ?' . '>');
				fclose($f);
				@chmod($cache_file, 0666);
			}
		}

		$style_color = 'style="font-weight:bold;text-decoration:none;color:' . $usercolor . ';"';

		if ($no_profile == false)
		{
			$user_link = '<a href="' . append_sid(IP_ROOT_PATH . PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . $user_id) . '" ' . $style_color . '>' . $username . '</a>';
		}
		else
		{
			$user_link = '<span ' . $style_color . '>' . $username . '</span>';
		}

		if ($get_only_color_style == true)
		{
			$user_link = $style_color;
		}

		return($user_link);
	}
	else
	{
		if($get_only_color_style == true)
		{
			return('');
		}
		return false;
	}
}
*/
// Mighty Gorgon: OLD COLORIZE FUNCTION - END

/**
* Get all users in a group
*/
function get_users_in_group($group_id)
{
	global $db;
	$sql = "SELECT u.user_id, u.username, u.user_active, u.user_color, u.user_email, g.group_id, g.group_name
		FROM " . USER_GROUP_TABLE . " ug, " . USERS_TABLE . " u, " . GROUPS_TABLE . " g
		WHERE ug.group_id = " . $group_id . "
			AND ug.user_pending = 0
			AND u.user_id = ug.user_id
			AND u.user_active = 1
			AND g.group_id = ug.group_id";
	if (!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, 'Error getting users information', '', __LINE__, __FILE__, $sql);
	}
	$users_array = array();
	$users_array = $db->sql_fetchrowset($result);
	$db->sql_freeresult($result);

	return $users_array;
}

/**
* Count all users in a group
*/
function count_users_in_group($group_id)
{
	global $db;
	$sql = "SELECT SUM(user_pending = 0) as members, SUM(user_pending = 1) as pending
		FROM " . USER_GROUP_TABLE . "
		WHERE group_id = '" . $group_id . "'";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Error getting user count information', '', __LINE__, __FILE__, $sql);
	}
	$counting_list = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	return $counting_list;
}

/**
* Count all active users
*/
function count_active_users()
{
	global $db;
	$sql = "SELECT SUM(user_active = 1) as active_members
		FROM " . USERS_TABLE;
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Error getting user count information', '', __LINE__, __FILE__, $sql);
	}
	$counting_list = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	return $counting_list;
}

/**
* Update all users colors and ranks.
*
* @param => group_id
* @return => true on success
*/
function update_all_users_colors_ranks($group_id)
{
	global $db, $board_config;
	$group_color = get_group_color($group_id);
	$group_rank = get_group_rank($group_id);
	$sql = "SELECT user_id
		FROM " . USER_GROUP_TABLE . "
		WHERE group_id = '" . $group_id . "'
			AND user_pending = 0";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not group information', '', __LINE__, __FILE__, $sql);
	}

	while ($row = $db->sql_fetchrow($result))
	{
		$sql_set = '';
		if ($group_color)
		{
			$sql_set .= ', user_color = \'' . $group_color . '\'';
		}
		if ($group_rank > 0)
		{
			$sql_set .= ', user_rank = \'' . $group_rank . '\'';
		}
		$sql_users = "UPDATE " . USERS_TABLE . "
			SET user_color_group = '" . $group_id . "'" . $sql_set . "
			WHERE user_id = '" . $row['user_id'] . "'";
		if (!$db->sql_query($sql_users))
		{
			message_die(GENERAL_ERROR, 'Could not update user color', '', __LINE__, __FILE__, $sql_users);
		}
		clear_user_color_cache($row['user_id']);
	}
	$db->sql_freeresult($result);

	return true;
}

/**
* Update user color and group.
*
* @param => user_id
* @param => user_color
* @param => user_color_group
* @return => true on success
*/
function update_user_color($user_id, $user_color, $user_color_group = false, $force_color = false, $force_group_color = false)
{
	global $db, $board_config;
	$sql = "SELECT u.user_color, u.user_color_group
					FROM " . USERS_TABLE . " as u
					WHERE u.user_id = '" . $user_id . "'
					LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group color', '', __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	$old_user_color = $row['user_color'];
	$old_user_color_group = $row['user_color_group'];
	$db->sql_freeresult($result);

	$exec_update = false;
	$user_color_sql = '';

	$user_color = check_valid_color($user_color);
	$user_color = ($user_color != false) ? $user_color : $board_config['active_users_color'];

	if ($force_color || ($old_user_color == '') || ($old_user_color == $board_config['active_users_color']) || ($old_user_color_group == '0'))
	{
		$exec_update = true;
		$user_color_sql .= (($user_color_sql == '') ? '' : ', ') . ('user_color = \'' . $user_color . '\'');
	}

	$user_color_group_sql = '';
	// 0 is different from false...
	if ($user_color_group === 0)
	{
		$exec_update = true;
		$user_color_sql .= (($user_color_sql == '') ? '' : ', ') . ('user_color_group = \'0\'');
	}
	elseif ($force_group_color || (($old_user_color_group == '0') && ($user_color_group !== false)))
	{
		$exec_update = true;
		$new_group_color = get_group_color($user_color_group);
		$user_color = ($new_group_color != false) ? $new_group_color : $user_color;
		$user_color_sql .= ($new_group_color != false) ? ((($user_color_sql == '') ? '' : ', ') . ('user_color_group = \'' . $user_color_group . '\'')) : '';
	}

	if ($exec_update)
	{
		$sql_users = "UPDATE " . USERS_TABLE . "
			SET " . $user_color_sql . "
			WHERE user_id = '" . $user_id . "'";
		if (!$db->sql_query($sql_users))
		{
			message_die(GENERAL_ERROR, 'Could not update user color group', '', __LINE__, __FILE__, $sql_users);
		}

		clear_user_color_cache($user_id);
	}
	return true;
}

/**
* Creates a list with groups name and a link to the group page.
*
* @param => none
* @return => array
*/
function build_groups_list_array()
{
	global $db;
	$sql = "SELECT group_id, group_name, group_color
		FROM " . GROUPS_TABLE . "
		WHERE group_single_user <> " . TRUE . "
			AND group_legend = 1
		ORDER BY group_name ASC";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group list', '', __LINE__, __FILE__, $sql);
	}

	$i = 0;
	$groups_list = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$i++;
		$groups_list[$i]['group_id'] = $row['group_id'];
		$groups_list[$i]['group_name'] = $row['group_name'];
		$groups_list[$i]['group_url'] = append_sid('groupcp.' . PHP_EXT . '?' . POST_GROUPS_URL . '=' . $row['group_id']);
		$groups_list[$i]['group_color'] = check_valid_color($row['group_color']);
		$groups_list[$i]['group_color_style'] = ($groups_list[$i]['group_color'] ? ' style="color:' . $row['group_color'] . ';font-weight:bold;"' : ' style="font-weight:bold;"');
	}
	$db->sql_freeresult($result);
	if ($i > 0)
	{
		return $groups_list;
	}
	else
	{
		return false;
	}
}

/**
* Creates a list with groups name and a link to the group page.
*
* @param => none
* @return => template var
*/
function build_groups_list_template()
{
	global $db, $template, $board_config, $lang;
	$sql = "SELECT group_id, group_name, group_color
		FROM " . GROUPS_TABLE . "
		WHERE group_single_user <> " . TRUE . "
			AND group_legend = 1
		ORDER BY group_legend_order ASC, group_name ASC";
	if (!($result = $db->sql_query($sql, false, 'groups_', USERS_CACHE_FOLDER)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group list', '', __LINE__, __FILE__, $sql);
	}

	$i = 0;
	$groups_list = '';
	while ($row = $db->sql_fetchrow($result))
	{
		$groups_list .= '&nbsp;<a href="' . append_sid('groupcp.' . PHP_EXT . '?' . POST_GROUPS_URL . '=' . $row['group_id']) . '" style="font-weight:bold;text-decoration:none;' . (check_valid_color($row['group_color']) ? ('color:' . check_valid_color($row['group_color']) . ';') : '') . '">' . $row['group_name'] . '</a>,';
	}
	$db->sql_freeresult($result);
	if ($board_config['active_users_legend'] == true)
	{
		$groups_list .= '&nbsp;<a href="' . append_sid('memberlist.' . PHP_EXT) . '" style="font-weight:bold;text-decoration:none;' . (check_valid_color($board_config['active_users_color']) ? ('color:' . check_valid_color($board_config['active_users_color']) . ';') : '') . '">' . $lang['Active_Users_Group'] . '</a>,';
	}
	if ($board_config['bots_legend'] == true)
	{
		$groups_list .= '&nbsp;<span style="font-weight:bold;text-decoration:none;' . (check_valid_color($board_config['bots_color']) ? ('color:' . check_valid_color($board_config['bots_color']) . ';') : '') . '">' . $lang['Bots_Group'] . '</span>,';
	}
	if ($groups_list != '')
	{
		$groups_list = substr($groups_list, 0, strlen($groups_list) - 1);
	}
	$template->assign_var('GROUPS_LIST_LEGEND', $groups_list);
}

/**
* Creates a list with all the groups a member subscribed.
*
* @param => user_id
* @return => array
*/
function build_groups_user($user_id, $show_hidden = true)
{
	global $db, $lang;
	$sql = "SELECT g.group_id, g.group_name, g.group_type
					FROM " . USER_GROUP_TABLE . " as l, " . GROUPS_TABLE . " as g
					WHERE l.user_pending = 0
						AND g.group_single_user = 0
						AND l.user_id = '" . $user_id . "'
						AND g.group_id = l.group_id
					ORDER BY g.group_name ASC, g.group_id ASC";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group list', '', __LINE__, __FILE__, $sql);
	}

	$i = 0;
	$groups_list = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$show_this = true;
		if (($show_hidden == false) && ($row['group_type'] == GROUP_HIDDEN))
		{
			$show_this = false;
		}

		if ($show_this == true)
		{
			$i++;
			$groups_list[$i]['group_id'] = $row['group_id'];
			$groups_list[$i]['group_name'] = $row['group_name'];
		}
	}
	$db->sql_freeresult($result);
	if ($i > 0)
	{
		return $groups_list;
	}
	else
	{
		return false;
	}
}

/**
* Query the group color.
*
* @param => group_id
* @return => string
*/
function get_group_color($group_id)
{
	global $db;
	$sql = "SELECT g.group_color
					FROM " . GROUPS_TABLE . " as g
					WHERE g.group_id = '" . $group_id . "'
					LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group color', '', __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	$group_color = $row['group_color'];
	$db->sql_freeresult($result);

	if ($group_color != '')
	{
		return $group_color;
	}
	else
	{
		return false;
	}
}

/**
* Query the group rank.
*
* @param => group_id
* @return => string
*/
function get_group_rank($group_id)
{
	global $db;
	$sql = "SELECT g.group_rank
					FROM " . GROUPS_TABLE . " as g
					WHERE g.group_id = '" . $group_id . "'
					LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group color', '', __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	$group_rank = $row['group_color'];
	$db->sql_freeresult($result);

	if ($group_rank != '0')
	{
		return $group_rank;
	}
	else
	{
		return false;
	}
}

/**
* Query the user color.
*
* @param => user_id
* @return => string
*/
function get_user_color($user_id)
{
	global $db;
	$sql = "SELECT u.user_color
					FROM " . USERS_TABLE . " as u
					WHERE u.group_id = '" . $user_id . "'
					LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain group color', '', __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	$user_color = $row['user_color'];
	$db->sql_freeresult($result);

	if ($user_color != '')
	{
		return $user_color;
	}
	else
	{
		return false;
	}
}

/**
* Query the user color.
*
* @param => group_id
* @param => move: direction
* @return => string
*/
function change_legend_order($group_id, $move)
{
	global $db, $lang;
	$move = ($move == '1') ? '1' : '0';
	$sql = "SELECT group_id, group_name, group_legend_order
		FROM " . GROUPS_TABLE . "
		WHERE group_single_user <> " . TRUE . "
		ORDER BY group_legend_order ASC, group_name ASC";
	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not query menu table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$item_order = 0;
	$weight_assigned = 0;
	$last_g_id = 0;
	$to_change_g_id = 0;
	while($row = $db->sql_fetchrow($result))
	{
		$item_order++;

		if ($row['group_id'] == $group_id)
		{
			$weight_assigned = $item_order;
			if (($move == '0') && ($item_order > 1))
			{
				$to_change_g_id = $last_g_id;
			}
		}

		if (($weight_assigned == ($item_order - 1)) && ($move == '1') && ($item_order > 1))
		{
			$to_change_g_id = $row['group_id'];
		}

		$sql_alt = "UPDATE " . GROUPS_TABLE . " SET group_legend_order = '" . $item_order . "' WHERE group_id = '" . $row['group_id'] . "'";
		if(!$result_alt = $db->sql_query($sql_alt))
		{
			message_die(GENERAL_ERROR, "Could not update menu table", $lang['Error'], __LINE__, __FILE__, $sql_alt);
		}
		$last_g_id = $row['group_id'];
	}
	if ($to_change_g_id != 0)
	{
		$item_order = ($move == '1') ? ($weight_assigned + 1) : ($weight_assigned - 1);
		$sql = "UPDATE " . GROUPS_TABLE . " SET group_legend_order = '" . $item_order . "' WHERE group_id = '" . $group_id . "'";
		if(!$result = $db->sql_query($sql))
		{
			message_die(GENERAL_ERROR, "Could not update menu table", $lang['Error'], __LINE__, __FILE__, $sql);
		}

		//$item_order = ($move == '1') ? ($weight_assigned - 1) : ($weight_assigned + 1);
		$item_order = $weight_assigned;
		$sql = "UPDATE " . GROUPS_TABLE . " SET group_legend_order = '" . $item_order . "' WHERE group_id = '" . $to_change_g_id . "'";
		if(!$result = $db->sql_query($sql))
		{
			message_die(GENERAL_ERROR, "Could not update menu table", $lang['Error'], __LINE__, __FILE__, $sql);
		}
	}
}

/**
* Adjust legend order.
*/
function adjust_legend_order()
{
	global $db, $lang;
	$sql = "SELECT group_id, group_name, group_legend_order
		FROM " . GROUPS_TABLE . "
		WHERE group_single_user <> " . TRUE . "
		ORDER BY group_legend_order ASC, group_name ASC";
	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not query groups table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$item_order = 0;
	while($row = $db->sql_fetchrowset($result))
	{
		$item_order++;
		$sql_alt = "UPDATE " . GROUPS_TABLE . " SET group_legend_order = '" . $item_order . "' WHERE group_id = '" . $row['group_id'] . "'";
		if(!$result_alt = $db->sql_query($sql_alt))
		{
			message_die(GENERAL_ERROR, "Could not update groups table", $lang['Error'], __LINE__, __FILE__, $sql_alt);
		}
	}
}

/**
* Add user(s) to group
*
* @return mixed false if no errors occurred, else the user lang string for the relevant error, for example 'NO_USER'
*/
function group_user_add($group_id, $user_id, $clear_cache = false)
{
	// 2 => User already member
	// 1 => User added
	// 0 => User not added
	global $db, $lang;

	$this_userdata = get_userdata($user_id);

	$sql = "SELECT ug.user_id, g.group_type, g.group_rank, g.group_color, g.group_count, g.group_count_max
		FROM " . USER_GROUP_TABLE . " ug, " . GROUPS_TABLE . " g
		WHERE g.group_id = '" . $group_id . "'
			AND ug.group_id = g.group_id";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not obtain user and group information', '', __LINE__, __FILE__, $sql);
	}

	if ($row = $db->sql_fetchrow($result))
	{
		$group_rank = $row['group_rank'];
		$group_color = $row['group_color'];
		do
		{
			if ($user_id == $row['user_id'])
			{
				return 2;
			}
		}
		while ($row = $db->sql_fetchrow($result));
	}
	else
	{
		return 0;
	}

	$sql = "INSERT INTO " . USER_GROUP_TABLE . " (group_id, user_id, user_pending) VALUES (" . $group_id . ", " . $user_id . ", 0)";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, "Error inserting user group subscription", "", __LINE__, __FILE__, $sql);
	}

	update_user_color($user_id, $group_data['group_color'], $this_userdata['user_color_group'], false, false);

	if (($this_userdata['user_rank'] == '0') && ($group_rank != '0'))
	{
		$sql_users = "UPDATE " . USERS_TABLE . "
			SET user_rank = '" . $group_rank . "'
			WHERE user_id = '" . $this_userdata['user_id'] . "'";
		if (!$db->sql_query($sql_users))
		{
			message_die(GENERAL_ERROR, 'Could not update users in groups', '', __LINE__, __FILE__, $sql);
		}
	}

	if ($clear_cache)
	{
		$db->clear_cache();
	}
	return 1;
}

?>