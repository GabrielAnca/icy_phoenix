<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*
* @Extra credits for this file
* (c) 2002 Meik Sievertsen (Acyd Burn)
*
*/

/**
* All Attachment Functions needed everywhere
*/

/**
* A simple dectobase64 function
*/
function base64_pack($number)
{
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+-';
	$base = strlen($chars);

	if ($number > 4096)
	{
		return;
	}
	elseif ($number < $base)
	{
		return $chars[$number];
	}

	$hexval = '';

	while ($number > 0)
	{
		$remainder = $number%$base;

		if ($remainder < $base)
		{
			$hexval = $chars[$remainder] . $hexval;
		}

		$number = floor($number/$base);
	}

	return $hexval;
}

/**
* base64todec function
*/
function base64_unpack($string)
{
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+-';
	$base = strlen($chars);

	$length = strlen($string);
	$number = 0;

	for($i = 1; $i <= $length; $i++)
	{
		$pos = $length - $i;
		$operand = strpos($chars, substr($string,$pos,1));
		$exponent = pow($base, $i-1);
		$decValue = $operand * $exponent;
		$number += $decValue;
	}

	return $number;
}

/**
* Used for determining if Forum ID is authed, please use this Function on all Posting Screens
*/
function is_forum_authed($auth_cache, $check_forum_id)
{
	$one_char_encoding = '#';
	$two_char_encoding = '.';

	if (trim($auth_cache) == '')
	{
		return true;
	}

	$auth = array();
	$auth_len = 1;

	for ($pos = 0; $pos < strlen($auth_cache); $pos+=$auth_len)
	{
		$forum_auth = substr($auth_cache, $pos, 1);
		if ($forum_auth == $one_char_encoding)
		{
			$auth_len = 1;
			continue;
		}
		elseif ($forum_auth == $two_char_encoding)
		{
			$auth_len = 2;
			$pos--;
			continue;
		}

		$forum_auth = substr($auth_cache, $pos, $auth_len);
		$forum_id = (int) base64_unpack($forum_auth);
		if ($forum_id == $check_forum_id)
		{
			return true;
		}
	}
	return false;
}

/**
* Init FTP Session
*/
function attach_init_ftp($mode = false)
{
	global $lang, $config;

	$server = (trim($config['ftp_server']) == '') ? 'localhost' : trim($config['ftp_server']);

	$ftp_path = ($mode == MODE_THUMBNAIL) ? trim($config['ftp_path']) . '/' . THUMB_DIR : trim($config['ftp_path']);

	$conn_id = @ftp_connect($server);

	if (!$conn_id)
	{
		message_die(GENERAL_ERROR, sprintf($lang['Ftp_error_connect'], $server));
	}

	$login_result = @ftp_login($conn_id, $config['ftp_user'], $config['ftp_pass']);

	if (!$login_result)
	{
		message_die(GENERAL_ERROR, sprintf($lang['Ftp_error_login'], $config['ftp_user']));
	}

	if (!@ftp_pasv($conn_id, intval($config['ftp_pasv_mode'])))
	{
		message_die(GENERAL_ERROR, $lang['Ftp_error_pasv_mode']);
	}

	$result = @ftp_chdir($conn_id, $ftp_path);

	if (!$result)
	{
		message_die(GENERAL_ERROR, sprintf($lang['Ftp_error_path'], $ftp_path));
	}

	return $conn_id;
}

/**
* Count Filesize of Attachments in Database based on the attachment id
*/
function get_total_attach_filesize($attach_ids)
{
	global $db;

	if (!is_array($attach_ids) || !sizeof($attach_ids))
	{
		return 0;
	}

	$attach_ids = implode(', ', array_map('intval', $attach_ids));

	if (!$attach_ids)
	{
		return 0;
	}

	$sql = 'SELECT filesize
		FROM ' . ATTACHMENTS_DESC_TABLE . "
		WHERE attach_id IN ($attach_ids)";
	$result = $db->sql_query($sql);

	$total_filesize = 0;
	while ($row = $db->sql_fetchrow($result))
	{
		$total_filesize += (int) $row['filesize'];
	}
	$db->sql_freeresult($result);

	return $total_filesize;
}

/**
* Realpath replacement for attachment mod
*/
function amod_realpath($path)
{
	return (function_exists('realpath')) ? realpath($path) : $path;
}

/**
* get all attachments from a post (could be an array of posts as well)
*/
function get_attachments_from_post($post_id_array)
{
	global $db, $config;

	$attachments = array();

	if (!is_array($post_id_array))
	{
		if (empty($post_id_array))
		{
			return $attachments;
		}

		$post_id = intval($post_id_array);

		$post_id_array = array();
		$post_id_array[] = $post_id;
	}

	$post_id_array = implode(', ', array_map('intval', $post_id_array));

	if ($post_id_array == '')
	{
		return $attachments;
	}

	$display_order = (intval($config['display_order']) == 0) ? 'DESC' : 'ASC';

	$sql = 'SELECT a.post_id, d.*
		FROM ' . ATTACHMENTS_TABLE . ' a, ' . ATTACHMENTS_DESC_TABLE . " d
		WHERE a.post_id IN ($post_id_array)
			AND a.attach_id = d.attach_id
		ORDER BY d.filetime $display_order";
	$result = $db->sql_query($sql);
	$num_rows = $db->sql_numrows($result);
	$attachments = $db->sql_fetchrowset($result);
	$db->sql_freeresult($result);

	if ($num_rows == 0)
	{
		return array();
	}

	return $attachments;
}

/**
* Update attachments stats
*/
function update_attachments_stats($attach_id)
{
	global $db, $user, $lang;

	$sql = 'UPDATE ' . ATTACHMENTS_DESC_TABLE . '
	SET download_count = download_count + 1
	WHERE attach_id = ' . (int) $attach_id;
	$db->sql_query($sql);

	if (!$user->data['is_bot'] && defined('USE_ATTACHMENTS_STATS') && USE_ATTACHMENTS_STATS)
	{
		$sql = "INSERT INTO " . ATTACHMENTS_STATS_TABLE . " (`attach_id`, `user_id`, `user_ip`, `user_browser`, `download_time`)
			VALUES ('" . $attach_id . "', '" . $user->data['user_id'] . "', '" . $db->sql_escape($user->ip) . "', '" . $db->sql_escape(addslashes($user->browser)) . "', '" . time() . "')";
		$result = $db->sql_query($sql);
	}

	return true;
}

/**
* Gets the upload dir
*/
function get_upload_dir($is_image = false)
{
	global $config;

	if (!intval($config['allow_ftp_upload']))
	{
		//$upload_dir = !empty($is_image) ? rtrim(POSTED_IMAGES_PATH, '/') : $config['upload_dir'];
		$upload_dir = $config['upload_dir'];
		if (defined('IN_ADMIN'))
		{
			if ($config['upload_dir'][0] == '/' || ($config['upload_dir'][0] != '/' && $config['upload_dir'][1] == ':'))
			{
				$upload_dir = $config['upload_dir'];
			}
			else
			{
				$upload_dir = IP_ROOT_PATH . $config['upload_dir'];
			}
		}
	}
	else
	{
		$upload_dir = $config['download_path'];
	}

	return $upload_dir;
}

/**
* Gets physical filename
*/
function get_physical_filename($physical_filename, $is_thumbnail = false)
{
	if (ATTACHMENT_MOD_BASENAME)
	{
		$physical_filename = ($is_thumbnail ? (THUMB_DIR . '/t_') : '') . basename($physical_filename);
	}

	return $physical_filename;
}

/**
* Move personal image to user subfolder
*/
function move_uploaded_image($filename)
{
	global $config, $user;

	return 1;
}

?>