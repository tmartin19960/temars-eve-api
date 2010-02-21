<?php
/*******************************************************************************
	This is a simplified script to add settings into SMF.

	ATTENTION: If you are trying to INSTALL this package, please access
	it directly, with a URL like the following:
		http://www.yourdomain.tld/forum/add_settings.php (or similar.)

================================================================================

	This script can be used to add new settings into the database for use
	with SMF's $modSettings array.  It is meant to be run either from the
	package manager or directly by URL.

*******************************************************************************/

// Set the below to true to overwrite already existing settings with the defaults. (not recommended.)
$overwrite_old_settings = false;

// List settings here in the format: setting_key => default_value.  Escape any "s. (" => \")
$mod_settings = array(
	'example_setting' => '1',
	'example_setting2' => '0',
);

/******************************************************************************/

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	
	function eveapi_check_table($table, $columns)
	{
		$fields = eveapi_select("EXPLAIN ".$table, MYSQL_ASSOC, FALSE);

		if (!empty($fields))
		{
			foreach ($fields AS $field)
			{
				$fcolumns[$field['Field']] = TRUE;
			}
		}
		else
			Return(array(FALSE, FALSE));
		
		foreach($columns as $c => $vars)
		{
			if(!isset($fcolumns[$c]))
				$missing[] = $c;
		}
		if(!empty($missing))
		{
			Return(array(FALSE, $missing));
		}
		else
			Return(array(TRUE));
	}

	function eveapi_select($sql, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
	{
		$data = "";
		$result = mysql_query($sql);

		if (!$result)
		{
			//echo $sql;
			if($error)
				echo "<BR>".mysql_error()."<BR>";
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		while ($row = mysql_fetch_array($result, $result_form))
		{
			$data[] = $row;
		}

		mysql_free_result($result);
		return $data;
	}
	function query($sql)
	{
		$return = mysql_query($sql);

		if (!$return)
		{
			//echo $sql;
			echo mysql_error();
			return false;
		}
		else
		{
			return true;
		}
	}

$info[1]['name'] = 'eve_api';	
$info[1]['primary'] = 'ID_MEMBER, userid';	
$tables[1]["ID_MEMBER"] = "INT";
$tables[1]["userid"] = "INT DEFAULT NULL";
$tables[1]["api"] = "VARCHAR(64) DEFAULT NULL";
//$tables[1]["characters"] = "VARCHAR(150) DEFAULT NULL";
//$tables[1]["charid"] = "INT DEFAULT NULL";
$tables[1]["status"] = "VARCHAR(20) DEFAULT NULL";
$tables[1]["errorid"] = "INT(5) DEFAULT NULL";
$tables[1]["error"] = "VARCHAR(254) DEFAULT NULL";
$tables[1]["status_change"] = "INT DEFAULT NULL";
$tables[1]["auto"] = "INT(1) DEFAULT 1";

$info[2]['name'] = 'eve_characters';
$info[2]['primary'] = 'userid, charid';
$tables[2]["userid"] = "INT DEFAULT NULL";
$tables[2]["charid"] = "INT DEFAULT NULL";
$tables[2]["name"] = "VARCHAR(50) DEFAULT NULL";
$tables[2]["corpid"] = "INT DEFAULT NULL";
$tables[2]["corp"] = "VARCHAR(50) DEFAULT NULL";
$tables[2]["corp_ticker"] = "VARCHAR(20) DEFAULT NULL";
$tables[2]["allianceid"] = "INT DEFAULT NULL";

Global $db_prefix;

foreach($tables as $i => $table)
{
	$check = eveapi_check_table($db_prefix.$info[$i]['name'], $table);
	if(!$check[0])
	{
		if($check[1])
		{
			foreach($check[1] as $f)
			{
				query("ALTER TABLE ".$db_prefix.$info[$i]['name']." ADD ".$f." ".$table[$f]);
			}
		}
		else
		{
			$sql = "CREATE TABLE ".$db_prefix.$info[$i]['name']." (";
			foreach($table as $c => $d)
				$sql .= " `".$c."` ".$d.",";
			$sql .= " PRIMARY KEY (".$info[$i]['primary']."))";
			query($sql);
		}
	}
	$check = eveapi_check_table($db_prefix.$info[$i]['name'], $table);
	if(!$check[0])
	{
		if($check[1])
		{
			echo '<b>Error:</b> Database modifications failed!';
			$msg = "These Columns are missing: ";
			$msg .= implode(", ", $check[1]);
			echo $msg;
		}
		else
			echo '<b>Error:</b> Database modifications failed!';
	}
}	

?>