<?php
if (file_exists('SSI.php') && !defined('SMF'))
	require_once('SSI.php');

require_once("Sources/TEA.php");
$chars = $tea -> get_characters($_GET['userid'], $_GET['api']);

if(!empty($chars))
{
	if($_GET['page'] == 'settings')
		echo '<select name="tea_charid" id="tea_charid" >';
	else
		echo '<select name="tea_char">';
	foreach($chars as $char)
	{
		if($_GET['page'] == 'settings')
			echo '<option value="'.$char['charid'].'">'.$char['name'].'</option>';
		else
		{
			if($modSettings["tea_corptag_options"] == 2)
				$name = '['.$char['ticker'].'] '.$char['name'];
			else
				$name = $char['name'];
			echo '<option value="'.$name.'">'.$name.'</option>';
		}
	}
}
else
{
	$error = $tea -> get_error($tea -> data);
	echo 'Error '.$error[0].' ('.$error[1].')<Br><select name="tea_char"><option value="-">-</option>';
}
echo '</select> <button type="button" onclick="javascript: getchars()">Get Characters</button>';
?>