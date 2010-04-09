<?php
if (file_exists('../SSI.php') && !defined('SMF'))
	require_once('../SSI.php');

require_once("TEA.php");
$chars = $tea -> get_characters($_GET['userid'], $_GET['api']);
if(!empty($chars))
{
	echo '<select name="tea_char">';
	foreach($chars as $char)
	{
		if($modSettings["tea_corptag_options"] == 2)
			$name = '['.$char['ticker'].'] '.$char['name'];
		else
			$name = $char['name'];
		echo '<option value="'.$name.'">'.$name.'</option>';
	}
}
else
{
	$error = $tea -> get_error($tea -> data);
	echo 'Error '.$error[0].' ('.$error[1].')<Br><select name="tea_char"><option value="-">-</option>';
}
echo '</select> <A href="javascript: getchars()">get</A>';
?>