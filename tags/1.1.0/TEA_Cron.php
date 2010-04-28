<?php
if (file_exists('../SSI.php') && !defined('SMF'))
	require_once('../SSI.php');

require_once("TEA.php");
$tea -> update_api(FALSE, TRUE);

?>