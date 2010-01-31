<?php
if (file_exists('../SSI.php') && !defined('SMF'))
	require_once('../SSI.php');

require_once("Eve_API.php");
$eve_api -> update_api(FALSE, TRUE);

?>