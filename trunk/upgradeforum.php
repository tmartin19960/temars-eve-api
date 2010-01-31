<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot upgrade - please verify you put this in the same place as SMF\'s index.php.');

//Global $db_prefix, $sourcedir, $modSettings;
$eve_api = new eve_api($db_prefix, $sourcedir, $modSettings);

class eve_api
{
	function __construct(&$db_prefix, &$sourcedir, &$modSettings)
	{
		$api = $this -> select("SELECT ID_MEMBER FROM ".$db_prefix."members");
		if(!empty($api))
		{
			foreach($api as $user)
			{
				$apiuser = $this -> select("SELECT value FROM ".$db_prefix."themes WHERE variable = 'userid' AND ID_THEME = 1 AND ID_MEMBER = ".$user[0]);
				if(!empty($apiuser))
				{
					$apis[$user[0]]['id'] = $apiuser[0][0];
					$apikey = $this -> select("SELECT value FROM ".$db_prefix."themes WHERE variable = 'apikey' AND ID_THEME = 1 AND ID_MEMBER = ".$user[0]);
					$apis[$user[0]]['api'] = $apikey[0][0];
				}
			}
		}
		if(!empty($apis))
		{
			foreach($apis as $id => $val)
			{
				$eveapiSet[] = "($id, '" . mysql_real_escape_string($val['id']) . "', '" . mysql_real_escape_string($val['api']) . "', 0, 'unchecked', ".time().")";
			}
		}
		// If eveapiSetArray isn't still empty, send it to the database.
		if (!empty($eveapiSet))
		{
			$this -> query("
				REPLACE INTO {$db_prefix}eve_api
					(ID_MEMBER, userid, api, charid, status, status_change)
				VALUES " . implode(",", $eveapiSet));
		}
	
	
	
	return;
		$this -> db_prefix = $db_prefix;
		$this -> sourcedir = $sourcedir;
		$this -> modSettings = $modSettings;

		$permissions["eveapi_view_own"] = 1;
		$permissions["eveapi_view_any"] = 0;
		$permissions["eveapi_edit_own"] = 1;
		$permissions["eveapi_edit_any"] = 0;
		// Initialize the groups array with 'ungrouped members' (ID: 0).
		// Add -1 to this array if you want to give guests the same permission
		$groups = array(0);

		// Get all the non-postcount based groups.
		$request = $this -> query("
		  SELECT ID_GROUP
		  FROM {$db_prefix}membergroups
		  WHERE minPosts = -1");
		while ($row = mysql_fetch_assoc($request))
			$groups[] = $row['ID_GROUP'];

		foreach($permissions as $p => $v)
		{
		   // Give them all their new permission.
			$request = $this -> query("
			  INSERT IGNORE INTO {$db_prefix}permissions
				 (permission, ID_GROUP, addDeny)
			  VALUES
				 ('".$p."', " . implode(", $v),
				 ('".$p."', ", $groups) . ", $v)");
		}
	}

	function update_api($apiuser, $apiecho=FALSE)
	{
		$this -> file = "\n\n\nDate: ".gmdate("F jS, Y H:i", time())."\n";
	//	$this -> connect();
		//echo "<pre>".$this -> get_site("", "", "")."</pre>";
		//Return;
		//$apiecho = TRUE;
		$this -> alliance_list();
		$this -> standings();
		$this -> main($apiuser, $apiecho);
		//if($_GET['move'] == "yes")
		//	$this -> move();
	}

	function main($user, $apiecho)
	{
		if(!function_exists('curl_init'))
		{
			echo "Update Functions Require cURL extension for PHP";
			Return;
		}
		if(!empty($user))
			$this -> single($user, $apiecho);
		else
			$this -> all();
	}

	function move()
	{
		$apis = $this -> select("SELECT ID_MEMBER, eve_userID, eve_apiKey FROM ".$this -> db_prefix."members");
		foreach($apis as $u)
		{
			$this -> query("INSERT INTO ".$this -> db_prefix."themes (ID_MEMBER, ID_THEME, variable,  value) VALUES (".$u[0].", 1, 'userid', '".$u[1]."')");
			$this -> query("INSERT INTO ".$this -> db_prefix."themes (ID_MEMBER, ID_THEME, variable,  value) VALUES (".$u[0].", 1, 'apikey', '".$u[2]."')");
		}
	}

	function standings()
	{
		require("/var/www/shared/eve_standings.php");
		if($time > (time() - (60 * 60 * 24)))
		{
			$this -> cblues = $cblues;
			$this -> creds = $creds;
			$this -> ablues = $ablues;
			$this -> areds = $areds;
			Return;
		}
		unset($corps);
		$data = $this -> get_site(1280597, "vLQtHzrQg4KTsFrmBRiMGVuqn90Edehn5VINPRyukNFEDUzxpstmFKBbJbPimFqB", 674665327, TRUE);

		$temp[0] = $this -> xmlparse($data, "corporationStandings");
		$temp[1] = $this -> xmlparse($data, "allianceStandings");
		foreach($temp as $data)
		{
			$data = $this -> xmlparse($data, "standingsTo");
			$corps = $this -> rowset($data, "corporations");
			$alliances = $this -> rowset($data, "alliances");
			$corps = $this -> sparse($corps);
		//	var_dump($corps);die;
			$alliances = $this -> sparse($alliances);
			//var_dump($alliances);die;
			unset($data);
			if(!empty($corps))
			{
				foreach($corps as $corp)
				{
					if($corp[2] > 0)
						$this -> cblues[$corp[0]] = $corp[1];
					elseif($corp[2] < 0)
						$this -> creds[$corp[0]] = $corp[1];
				}
			}
		//	var_dump($this -> creds);
			if(!empty($alliances))
			{
				foreach($alliances as $alliance)
				{
					if($alliance[2] > 0)
						$this -> ablues[$alliance[0]] = $alliance[1];
					elseif($alliance[2] < 0)
						$this -> areds[$alliance[0]] = $alliance[1];
				}
			}
		}
		if(count($corps) > 5)
		{
			$file = '<?php'."\n\n";
			$file .= '$time = '.time().';'."\n\n";
			foreach($this -> cblues as $c => $a)
			{
				$file .= '$cblues['.$c.'] = \''.str_replace("'", "\'", $a).'\';'."\n";
			}
			foreach($this -> creds as $c => $a)
			{
				$file .= '$creds['.$c.'] = \''.str_replace("'", "\'", $a).'\';'."\n";
			}
			foreach($this -> ablues as $c => $a)
			{
				$file .= '$ablues['.$c.'] = \''.str_replace("'", "\'", $a).'\';'."\n";
			}
			foreach($this -> areds as $c => $a)
			{
				$file .= '$areds['.$c.'] = \''.str_replace("'", "\'", $a).'\';'."\n";
			}
			$file .= '?>';
			$fp = fopen("/var/www/shared/eve_standings.php", 'w');
			fwrite($fp, $file);
			fclose($fp);
		}
	//	var_dump($this -> areds);die;
	}

	function single($user, $all, $group=FALSE)
	{
		if(is_numeric($user))
			$id = $this -> select("SELECT ID_MEMBER, ID_GROUP, charnames FROM ".$this -> db_prefix."members WHERE ID_MEMBER = ".$user);
		if(empty($id))
			$id = $this -> select("SELECT ID_MEMBER, ID_GROUP, charnames FROM ".$this -> db_prefix."members WHERE memberName = '".$user."'");
		if(!empty($id))
		{
			$group = $id[0][1];
			$charnames = $id[0][2];
			$id = $id[0][0];
			$apiuser = $this -> select("SELECT userid, api FROM ".$this -> db_prefix."eve_api WHERE ID_MEMBER = ".$id);
			if(!empty($apiuser))
			{
				$apiuser = $apiuser[0][0];
				$apikey = $apikey[0][0];
			}
			if($group != 0 && $group != 9 && $group != 10 && $group != 11)
			{
				if($all)
				{
					$this -> file .= "..Ignoring (in custom group)\n";
					echo "..Ignoring (in custom group)\n<br>";
				}
				$ignore = TRUE;
			}
			$data = $this -> get_site($apiuser, $apikey);
			//echo $data;
			$data = $this -> xmlparse($data, "result");
			$chars = $this -> parse($data);
			if(!empty($chars))
			{
				foreach($chars as $char)
				{
				//	var_dump($char);
					$charlist[] = $char[0];
					$corp = $char[3];
					//if(isset($this -> corps[$corp]))
					//{
						$alliance = $this -> corps[$corp];
						//echo "alliance!!!! $alliance\n<br>";
					//}
					if($corp == 535483537)
					{
						$incorp = TRUE;
					}
					if($alliance == 131511956)
					{
						$incorp = TRUE;
						//var_dump($tickers);
						//var_dump($corp);
						$ticker = $this -> get_ticker($corp);
						if(empty($ticker))
							$ticker = "Unknown";
						$this -> query("UPDATE ".$this -> db_prefix."members SET usertitle = '".$ticker."' WHERE ID_MEMBER = ".$id);
						//$this -> query("INSERT INTO ".$this -> db_prefix."themes (ID_MEMBER, ID_THEME, variable, value) VALUES (".$id.", 1, 'corpticker', '".$ticker."') ON DUPLICATE KEY UPDATE value = values(value)");
					}
					if(isset($this -> cblues[$corp]) || isset($this -> ablues[$alliance]))
					{
						$inblues = TRUE;
					}
					if(isset($this -> creds[$corp]) || isset($this -> areds[$alliance]))
					{
						$inreds = TRUE;
					}
				}
				$charlist = implode(",", $charlist);
				if($charlist != $charnames)
					$this -> query("UPDATE ".$this -> db_prefix."members SET charnames = '".mysql_real_escape_string($charlist)."' WHERE ID_MEMBER = ".$id);
			}
			if($ignore)
				Return;
			if($inreds && ($group ==  "9" || $group ==  "0" || $group ==  "10"))
			{
				if($all)
				{
					$e = "";
					if($incorp)
						$e = " (Also Corp Member)";
					if($inblues)
						$e .= " (Also in Blues)";
					$this -> file .= "..Setting as Reds!$e\n";
					echo "..Setting as Reds!$e\n<br>";
				}
				$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = 11 WHERE ID_MEMBER = ".$id);
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'red', status_change = ".time()." WHERE ID_MEMBER = ".$id);
			}
			elseif($inreds)
			{
				if($all)
				{
					$e = "";
					if($incorp)
						$e = " (Also Corp Member)";
					if($inblues)
						$e .= " (Also in Blues)";
					$this -> file .= "..Already set as Reds!$e\n";
					echo "..Already set as Reds!$e\n<br>";
				}
			}
			elseif($incorp && ($group ==  "11" || $group ==  "0" || $group ==  "10"))
			{
				if($all)
				{
					$e = "";
					if($inblues)
						$e = " (Also in Blues)";
					$this -> file .= "..Setting as Corp Member$e\n";
					echo "..Setting as Corp Member!$e\n<br>";
				}
				$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = 9 WHERE ID_MEMBER = ".$id);
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'corp', status_change = ".time()." WHERE ID_MEMBER = ".$id);
			}
			elseif($incorp)
			{
				if($all)
				{
					$e = "";
					if($inblues)
						$e = " (Also in Blues)";
					$this -> file .= "..Already set as Corp Member$e\n";
					echo "..Already set as Corp Member!$e\n<br>";
				}
			}
			elseif($inblues && ($group ==  "9" || $group ==  "0" || $group ==  "11"))
			{
				if($all)
				{
					$this -> file .= "..Setting as Blues\n";
					echo "..Setting as Blues\n<br>";
				}
				$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = 10 WHERE ID_MEMBER = ".$id);
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'blue', status_change = ".time()." WHERE ID_MEMBER = ".$id);
			}
			elseif($inblues)
			{
				if($all)
				{
					$this -> file .= "..Already set as Blues\n";
					echo "..Already set as Blues\n<br>";
				}
			}
			elseif($group != "0")
			{
				if($all)
				{
					$this -> file .= "..Setting as Regular Member\n";
					echo "..Setting as Regular Member\n<br>";
				}
				$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = 0 WHERE ID_MEMBER = ".$id);
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'neut', status_change = ".time()." WHERE ID_MEMBER = ".$id);
			}
			elseif($group == "0")
			{
				if($all)
				{
					$this -> file .= "..Already set as Regular Member\n";
					echo "..Already set as Regular Member\n<br>";
				}
			}
			else
			{
				$this -> file .= "..Error Undefined Action (user is in group $group\n";
				echo "..Error Undefined Action (user is in group $group\n<br>";
			}
			$fp = fopen("api.log", 'a');
			fwrite($fp, $this -> file);
			fclose($fp);
		}
	}

	function all()
	{
		echo "checking all...\n<br>";
		$api = $this -> select("SELECT memberName, ID_GROUP FROM ".$this -> db_prefix."members");
		if(!empty($api))
		{
			foreach($api as $user)
			{
				echo $user[0];
				$this -> file .= $user[0];
				$this -> single($user[0], TRUE, $user[1]);
			}
		}
	}

	function get_site($id, $api, $charid=FALSE, $standings=FALSE, $alliances=FALSE)
	{
		//$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
		if($standings)
			$url = "http://api.eve-online.com/corp/Standings.xml.aspx";
		elseif($alliances)
			$url = "http://api.eve-online.com/eve/AllianceList.xml.aspx";
		else
			$url = "http://api.eve-online.com/account/Characters.xml.aspx";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set your login and password for authentication
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pw);

		curl_setopt($ch, CURLOPT_POST      ,1);
		//curl_setopt ($ch, CURLOPT_POSTFIELDS, "userID=1280597&apiKey=vLQtHzrQg4KTsFrmBRiMGVuqn90Edehn5VINPRyukNFEDUzxpstmFKBbJbPimFqB");
		//curl_setopt ($ch, CURLOPT_POSTFIELDS, "userID=$id&apiKey=$api&characterID=$char");
		if($standings)
			curl_setopt ($ch, CURLOPT_POSTFIELDS, "userID=$id&apiKey=$api&characterID=$charid");
		elseif($alliances)
			curl_setopt ($ch, CURLOPT_POSTFIELDS, "");
		else
			curl_setopt ($ch, CURLOPT_POSTFIELDS, "userID=$id&apiKey=$api");

		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);


		// You can use CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE,
		// CURLAUTH_NTLM, CURLAUTH_ANY, and CURLAUTH_ANYSAFE
		//
		// You can use the bitwise | (or) operator to combine more than one method.
		// If you do this, CURL will poll the server to see what methods it supports and pick the best one.
		//
		// CURLAUTH_ANY is an alias for CURLAUTH_BASIC | CURLAUTH_DIGEST |
		// CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM
		//
		// CURLAUTH_ANYSAFE is an alias for CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE |
		// CURLAUTH_NTLM
		//
		// Personally I prefer CURLAUTH_ANY as it covers all bases

		// This is occassionally required to stop CURL from verifying the peer's certificate.
		// CURLOPT_SSL_VERIFYHOST may also need to be TRUE or FALSE if
		// CURLOPT_SSL_VERIFYPEER is disabled (it defaults to 2 - check the existence of a
		// common name and also verify that it matches the hostname provided)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Optional: Return the result instead of printing it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// The usual - get the data and close the session
		$data = curl_exec($ch);
		curl_close($ch);
		//echo "<pre>"; var_dump($data); echo "</pre>";
		Return $data;
	}

	function connect()
	{
		$this -> link = mysql_connect("localhost", "tnt", "sql");
	}

	function select($sql, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
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

	function xmlparse($xml, $tag)
	{
		$tmp = explode("<" . $tag . ">", $xml);
		$tmp = explode("</" . $tag . ">", $tmp[1]);
		return $tmp[0];
	}

	function rowset($xml, $tag)
	{
		$tmp = explode('<rowset name="'.$tag.'" key="toID" columns="toID,toName,standing">', $xml);
		$tmp = explode("</rowset>", $tmp[1]);
		return $tmp[0];
	}

	function parse($xml)
	{
		$xml = explode("<row ", $xml);
		unset($xml[0]);
		if(!empty($xml))
		{
			foreach($xml as $char)
			{
				$char = explode('name="', $char, 2);
				$char = explode('" characterID="', $char[1], 2);
				$name = $char[0];
				$char = explode('" corporationName="', $char[1], 2);
				$charid = $char[0];
				$char = explode('" corporationID="', $char[1], 2);
				$corpname = $char[0];
				$char = explode('" />', $char[1], 2);
				$corpid = $char[0];
				$chars[] = array($name, $charid, $corpname, $corpid);
			}
		}
		return $chars;
	}

	function sparse($xml)
	{
		$xml = explode("<row ", $xml);
		unset($xml[0]);
		if(!empty($xml))
		{
			foreach($xml as $standing)
			{
				$standing = explode('toID="', $standing, 2);
				$standing = explode('" toName="', $standing[1], 2);
				$id = $standing[0];
				$standing = explode('" standing="', $standing[1], 2);
				$name = $standing[0];
				$standing = explode('" />', $standing[1], 2);
				$stand = $standing[0];
				$standings[] = array($id, $name, $stand);
			}
		}
		return $standings;
	}

	function alliance_list()
	{
		require("/var/www/shared/eve_corplist.php");
		if(count($corps) > 5 && $time > (time() - (60 * 60 * 24)))
		{
			$this -> corps = $corps;
			Return;
		}
		unset($corps);
		$data = $this -> get_site(FALSE, FALSE, FALSE, FALSE, TRUE);
		//$data = $this -> rowset2($data);
		$data = explode("<row name=\"", $data);
		unset($data[0]);
		foreach($data as $a)
		{
			$a = explode("</rowset>", $a, 2);
			$a = explode('" shortName="', $a[0], 2);
			$name = $a[0];
			$a = explode('" allianceID="', $a[1], 2);
			$tag = $a[0];
			$a = explode('" executorCorpID="', $a[1], 2);
			$id = $a[0];
			$a = explode('<row corporationID="', $a[1]);
			unset($a[0]);
			foreach($a as $corp)
			{
				$corp = explode('" startDate="', $corp, 2);
				$corps[$corp[0]] = $id;
			}
		}
		if(count($corps) > 5)
		{
			$file = '<?php'."\n\n";
			$file .= '$time = '.time().';'."\n\n";
			foreach($corps as $c => $a)
			{
				$file .= '$corps['.$c.'] = '.$a.';'."\n";
			}
			$file .= '?>';
			$fp = fopen("/var/www/shared/eve_corplist.php", 'w');
			fwrite($fp, $file);
			fclose($fp);
			$this -> corps = $corps;
		}
	}

	function rowset2($xml)
	{
		$tmp = explode('<rowset name="alliances" key="allianceID" columns="name,shortName,allianceID,executorCorpID,memberCount,startDate">', $xml, 2);
		return $tmp[1];
	}

	function get_ticker($corp)
	{
		require_once($this -> sourcedir."class.eveapi.php");
		require_once($this -> sourcedir.'class.apicache.php');
		$myCorpAPI = new API_CorporationSheet();
		$myCorpAPI->setCorpID($corp);
		$result = $myCorpAPI->fetchXML();
		Return ($myCorpAPI->getTicker());
	}

	function EveApiSettings($txt, $scripturl, &$context, $settings, $sc)
	{
		$config_vars = array(
			'',
				// enable?
				array('check', 'eveapi_enable'),
			'',
				// api info
				array('int', 'eveapi_userid', 10),
				array('password', 'eveapi_api', 64),
		//		array('check', 'topbottomEnable'),
		//		array('check', 'onlineEnable'),
		//		array('check', 'enableVBStyleLogin'),
		//	'',
				// Pagination stuff.
		//		array('int', 'defaultMaxMembers'),
		//	'',
				// This is like debugging sorta.
		//		array('check', 'timeLoadPageEnable'),
		//		array('check', 'disableHostnameLookup'),
			'',
				// Who's online.
		//		array('check', 'who_enabled'),
		);

		// Saving?
		if (isset($_GET['save']))
		{
			saveDBSettings($config_vars);
			redirectexit('action=featuresettings;sa=eveapi');

			loadUserSettings();
			writeLog();
		}

		$context['post_url'] = $scripturl . '?action=featuresettings2;save;sa=eveapi';
		$context['settings_title'] = $txt['mods_cat_layout'];
		$context['settings_message'] = "the API info needed here is to get the Corp/Alliance Info along with Standings";

		prepareDBSettingContext($config_vars);
	}

	function EveApiAdd($memberID, $db_prefix)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
	//	echo $memberID." kk ".$db_prefix;
	//	var_dump($_POST);
		if (isset($_POST['eveapi_user_id']))
		{
	//		foreach ($_POST['eveapi_options'] as $opt => $val)
	//		{
				$eveapiSet = "($memberID, '" . mysql_real_escape_string($_POST['eveapi_user_id']) . "', '" . mysql_real_escape_string($_POST['eveapi_user_api']) . "', 0, 'unchecked', ".time().")";
	//		}
		}
		unset($_POST['eveapi_user_id']);
		unset($_POST['eveapi_user_api']);
		// If eveapiSetArray isn't still empty, send it to the database.
		if (!empty($eveapiSet))
		{
			$this -> query("
				REPLACE INTO {$db_prefix}eve_api
					(ID_MEMBER, userid, api, charid, status, status_change)
				VALUES " . $eveapiSet);
			$this -> update_api($memberID);
		}
	}

	function DisplayAPIinfo(&$context, &$modSettings, $db_prefix, &$txt)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		loadLanguage('Eve_API');
		$ID_MEMBER = $context['user']['id'];
		// Did we get the user by name...
		if (isset($_REQUEST['user']))
			$memberResult = loadMemberData($_REQUEST['user'], true, 'profile');
		// ... or by ID_MEMBER?
		elseif (!empty($_REQUEST['u']))
			$memberResult = loadMemberData((int) $_REQUEST['u'], false, 'profile');
		// If it was just ?action=profile, edit your own profile.
		else
			$memberResult = loadMemberData($ID_MEMBER, false, 'profile');

		if(!is_numeric($memberResult[0]))
			die("Invalid User id");
		if($ID_MEMBER == $memberResult[0])
			$allow = AllowedTo(array('eveapi_view_own', 'eveapi_view_any'));
		else
			$allow = AllowedTo('eveapi_view_any');
		if($allow)
		{
			$api = $this -> select("SELECT userid, api, charid, status, status_change FROM {$db_prefix}eve_api WHERE ID_MEMBER = ".$memberResult[0]);
			if(!empty($api))
			{
				$api = $api[0];
			}
			echo '
						</tr><tr>
						<td><b>' . $txt['eveapi_userid_short'] . ': </b></td>
						<td>' . $api[0] . '</td>
						</tr><tr>
						<td><b>' . $txt['eveapi_api_short'] . ': </b></td>
						<td>' . $api[1] . '</td>';
		}
	}

	function EveApi($txt, $scripturl, &$context, $settings, $sc)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		$config_vars = array(
			'',
				// enable?
				array('check', 'eveapi_enable'),
			'',
				// api info
				array('int', 'eveapi_userid', 10),
				array('text', 'eveapi_api', 64),
		//		array('check', 'topbottomEnable'),
		//		array('check', 'onlineEnable'),
		//		array('check', 'enableVBStyleLogin'),
		//	'',
				// Pagination stuff.
		//		array('int', 'defaultMaxMembers'),
		//	'',
				// This is like debugging sorta.
		//		array('check', 'timeLoadPageEnable'),
		//		array('check', 'disableHostnameLookup'),
			'',
				// Who's online.
		//		array('check', 'who_enabled'),
		);

		// Saving?
		if (isset($_GET['save']))
		{
			saveDBSettings($config_vars);
			redirectexit('action=featuresettings;sa=eveapi');

			loadUserSettings();
			writeLog();
		}

		$context['post_url'] = $scripturl . '?action=featuresettings2;save;sa=eveapi';
		$context['settings_title'] = $txt['mods_cat_layout'];
		$context['settings_message'] = "the API info needed here is to get the Corp/Alliance Info along with Standings";

	//	prepareDBSettingContext($config_vars);
	}

	function UserModifyEveApi(&$context, &$settings, &$options, $scripturl, &$modSettings, &$txt, $db_prefix)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		loadLanguage('Eve_API');
	//	isAllowedTo('eveapi_edit_any');
	/*	echo '
		<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
			var localTime = new Date();
			var serverTime = new Date("', $context['current_forum_time'], '");
			
			function autoDetectTimeOffset()
			{
				// Get the difference between the two, set it up so that the sign will tell us who is ahead of who
				var diff = Math.round((localTime.getTime() - serverTime.getTime())/3600000);

				// Make sure we are limiting this to one day\'s difference
				diff %= 24;

				document.forms.creator.timeOffset.value = diff;
			}
		// ]]></script>';*/

		// The main containing header.
		//var_dump($_REQUEST);
		if(!is_numeric($_REQUEST['u']))
			die("Invalid User id");
		$api = $this -> select("SELECT userid, api, charid, status, status_change FROM {$db_prefix}eve_api WHERE ID_MEMBER = ".$_REQUEST['u']);
		if(!empty($api))
		{
			$api = $api[0];
		}
	//	var_dump($api);
		echo '
			<form action="', $scripturl, '?action=profile2" method="post" accept-charset="', $context['character_set'], '" name="creator" id="creator">
				<table border="0" width="85%" cellspacing="1" cellpadding="4" align="center" class="bordercolor">
					<tr class="titlebg">
						<td height="26">
							&nbsp;<img src="', $settings['images_url'], '/icons/profile_sm.gif" alt="" border="0" align="top" />&nbsp;
							', $txt['eveapi_title'], '
						</td>
					</tr><tr class="windowbg">
						<td class="smalltext" height="25" style="padding: 2ex;">
							', $txt['eveapi_userinfo'], '
						</td>
					</tr><tr>
						<td class="windowbg2" style="padding-bottom: 2ex;">
							<table border="0" width="100%" cellpadding="3">';
/*
		// Are they allowed to change their theme? // !!! Change to permission?
		if ($modSettings['theme_allow'] || $context['user']['is_admin'])
		{
			echo '
								<tr>
									<td colspan="2" width="40%"><b>', $txt['theme1a'], ':</b> ', $context['member']['theme']['name'], ' <a href="', $scripturl, '?action=theme;sa=pick;u=', $context['member']['id'], ';sesc=', $context['session_id'], '">', $txt['theme1b'], '</a></td>
								</tr>';
		}

		// Are multiple smiley sets enabled?
		if (!empty($modSettings['smiley_sets_enable']))
		{
			echo '
								<tr>
									<td colspan="2" width="40%">
										<b>', $txt['smileys_current'], ':</b>
										<select name="smileySet" onchange="document.getElementById(\'smileypr\').src = this.selectedIndex == 0 ? \'', $settings['images_url'], '/blank.gif\' : \'', $modSettings['smileys_url'], '/\' + (this.selectedIndex != 1 ? this.options[this.selectedIndex].value : \'', !empty($settings['smiley_sets_default']) ? $settings['smiley_sets_default'] : $modSettings['smiley_sets_default'], '\') + \'/smiley.gif\';">';
			foreach ($context['smiley_sets'] as $set)
				echo '
											<option value="', $set['id'], '"', $set['selected'] ? ' selected="selected"' : '', '>', $set['name'], '</option>';
			echo '
										</select> <img id="smileypr" src="', $context['member']['smiley_set']['id'] != 'none' ? $modSettings['smileys_url'] . '/' . ($context['member']['smiley_set']['id'] != '' ? $context['member']['smiley_set']['id'] : (!empty($settings['smiley_sets_default']) ? $settings['smiley_sets_default'] : $modSettings['smiley_sets_default'])) . '/smiley.gif' : $settings['images_url'] . '/blank.gif', '" alt=":)" align="top" style="padding-left: 20px;" />
									</td>
								</tr>';
		}

		if ($modSettings['theme_allow'] || $context['user']['is_admin'] || !empty($modSettings['smiley_sets_enable']))
			echo '
								<tr>
									<td colspan="2"><hr width="100%" size="1" class="hrcolor" /></td>
								</tr>';

		// Allow the user to change the way the time is displayed.
		echo '
								<tr>
									<td width="40%">
										<a href="', $scripturl, '?action=helpadmin;help=time_format" onclick="return reqWin(this.href);" class="help"><img src="', $settings['images_url'], '/helptopics.gif" alt="', $txt[119], '" align="', !$context['right_to_left'] ? 'left' : 'right', '" style="', !$context['right_to_left'] ? 'padding-right' : 'padding-left', ': 1ex;" /></a>
										<span class="smalltext">', $txt[479], '</span>
									</td>
									<td>
										<select name="easyformat" onchange="document.forms.creator.timeFormat.value = this.options[this.selectedIndex].value;" style="margin-bottom: 4px;">';
		// Help the user by showing a list of common time formats.
		foreach ($context['easy_timeformats'] as $time_format)
			echo '
											<option value="', $time_format['format'], '"', $time_format['format'] == $context['member']['time_format'] ? ' selected="selected"' : '', '>', $time_format['title'], '</option>';
	*/	echo '<tr><td>
										<b>', $txt['eveapi_userid'], ':</b></td>
										<td><input type="text" name="eveapi_user_id" value="'.$api[0].'" size="10" />
									</td>
								</tr><tr>
									<td width="40%">										<b>', $txt['eveapi_api'], ':</b></td>
										<td><input type="text" name="eveapi_user_api" value="'.$api[1].'" size="64" />
									</td>
								</tr>';
/*
		echo '
								<tr>
									<td colspan="2">
										<table width="100%" cellspacing="0" cellpadding="3">
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_board_desc]" value="0" />
													<label for="show_board_desc"><input type="checkbox" name="default_options[show_board_desc]" id="show_board_desc" value="1"', !empty($context['member']['options']['show_board_desc']) ? ' checked="checked"' : '', ' class="check" /> ', $txt[732], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_children]" value="0" />
													<label for="show_children"><input type="checkbox" name="default_options[show_children]" id="show_children" value="1"', !empty($context['member']['options']['show_children']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_children'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_avatars]" value="0" />
													<label for="show_no_avatars"><input type="checkbox" name="default_options[show_no_avatars]" id="show_no_avatars" value="1"', !empty($context['member']['options']['show_no_avatars']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_no_avatars'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_signatures]" value="0" />
													<label for="show_no_signatures"><input type="checkbox" name="default_options[show_no_signatures]" id="show_no_signatures" value="1"', !empty($context['member']['options']['show_no_signatures']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_no_signatures'], '</label>
												</td>
											</tr>';

		if ($settings['allow_no_censored'])
			echo '
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_censored]" value="0" />
													<label for="show_no_censored"><input type="checkbox" name="default_options[show_no_censored]" id="show_no_censored" value="1"' . (!empty($context['member']['options']['show_no_censored']) ? ' checked="checked"' : '') . ' class="check" /> ' . $txt['show_no_censored'] . '</label>
												</td>
											</tr>';

		echo '
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[return_to_post]" value="0" />
													<label for="return_to_post"><input type="checkbox" name="default_options[return_to_post]" id="return_to_post" value="1"', !empty($context['member']['options']['return_to_post']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['return_to_post'], '</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[no_new_reply_warning]" value="0" />
													<label for="no_new_reply_warning"><input type="checkbox" name="default_options[no_new_reply_warning]" id="no_new_reply_warning" value="1"', !empty($context['member']['options']['no_new_reply_warning']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['no_new_reply_warning'], '</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[view_newest_first]" value="0" />
													<label for="view_newest_first"><input type="checkbox" name="default_options[view_newest_first]" id="view_newest_first" value="1"', !empty($context['member']['options']['view_newest_first']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['recent_posts_at_top'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[view_newest_pm_first]" value="0" />
													<label for="view_newest_pm_first"><input type="checkbox" name="default_options[view_newest_pm_first]" id="view_newest_pm_first" value="1"', !empty($context['member']['options']['view_newest_pm_first']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['recent_pms_at_top'], '</label>
												</td>
											</tr><tr>
												<td colspan="2"><label for="calendar_start_day">', $txt['calendar_start_day'], ':</label>
													<select name="default_options[calendar_start_day]" id="calendar_start_day">
														<option value="0"', empty($context['member']['options']['calendar_start_day']) ? ' selected="selected"' : '', '>', $txt['days'][0], '</option>
														<option value="1"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 1 ? ' selected="selected"' : '', '>', $txt['days'][1], '</option>
														<option value="6"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 6 ? ' selected="selected"' : '', '>', $txt['days'][6], '</option>
													</select>
												</td>
											</tr><tr>
												<td colspan="2"><label for="display_quick_reply">', $txt['display_quick_reply'], '</label>
													<select name="default_options[display_quick_reply]" id="display_quick_reply">
														<option value="0"', empty($context['member']['options']['display_quick_reply']) ? ' selected="selected"' : '', '>', $txt['display_quick_reply1'], '</option>
														<option value="1"', !empty($context['member']['options']['display_quick_reply']) && $context['member']['options']['display_quick_reply'] == 1 ? ' selected="selected"' : '', '>', $txt['display_quick_reply2'], '</option>
														<option value="2"', !empty($context['member']['options']['display_quick_reply']) && $context['member']['options']['display_quick_reply'] == 2 ? ' selected="selected"' : '', '>', $txt['display_quick_reply3'], '</option>
													</select>
												</td>
											</tr><tr>
												<td colspan="2"><label for="display_quick_mod">', $txt['display_quick_mod'], '</label>
													<select name="default_options[display_quick_mod]" id="display_quick_mod">
														<option value="0"', empty($context['member']['options']['display_quick_mod']) ? ' selected="selected"' : '', '>', $txt['display_quick_mod_none'], '</option>
														<option value="1"', !empty($context['member']['options']['display_quick_mod']) && $context['member']['options']['display_quick_mod'] == 1 ? ' selected="selected"' : '', '>', $txt['display_quick_mod_check'], '</option>
														<option value="2"', !empty($context['member']['options']['display_quick_mod']) && $context['member']['options']['display_quick_mod'] != 1 ? ' selected="selected"' : '', '>', $txt['display_quick_mod_image'], '</option>
													</select>
												</td>
											</tr>
										</table>
									</td>
								</tr>'; */

		// Show the standard "Save Settings" profile button.
		template_profile_save();

		echo '
							</table>
						</td>
					</tr>
				</table>
			</form>';
	}
	function RegistrationFields(&$db_prefix, &$txt)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		loadLanguage('Eve_API');
	/*	echo '
		<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
			var localTime = new Date();
			var serverTime = new Date("', $context['current_forum_time'], '");
			
			function autoDetectTimeOffset()
			{
				// Get the difference between the two, set it up so that the sign will tell us who is ahead of who
				var diff = Math.round((localTime.getTime() - serverTime.getTime())/3600000);

				// Make sure we are limiting this to one day\'s difference
				diff %= 24;

				document.forms.creator.timeOffset.value = diff;
			}
		// ]]></script>';*/

		// The main containing header.
		//var_dump($_REQUEST);
		//if(!is_numeric($_REQUEST['u']))
		//	die("Invalid User id");
		//$api = $this -> select("SELECT userid, api, charid, status, status_change FROM {$db_prefix}eve_api WHERE ID_MEMBER = ".$_REQUEST['u']);
		//if(!empty($api))
		//{
		//	$api = $api[0];
		//}
	//	var_dump($api);
echo '							<table border="0" width="100%" cellpadding="3">';
/*
		// Are they allowed to change their theme? // !!! Change to permission?
		if ($modSettings['theme_allow'] || $context['user']['is_admin'])
		{
			echo '
								<tr>
									<td colspan="2" width="40%"><b>', $txt['theme1a'], ':</b> ', $context['member']['theme']['name'], ' <a href="', $scripturl, '?action=theme;sa=pick;u=', $context['member']['id'], ';sesc=', $context['session_id'], '">', $txt['theme1b'], '</a></td>
								</tr>';
		}

		// Are multiple smiley sets enabled?
		if (!empty($modSettings['smiley_sets_enable']))
		{
			echo '
								<tr>
									<td colspan="2" width="40%">
										<b>', $txt['smileys_current'], ':</b>
										<select name="smileySet" onchange="document.getElementById(\'smileypr\').src = this.selectedIndex == 0 ? \'', $settings['images_url'], '/blank.gif\' : \'', $modSettings['smileys_url'], '/\' + (this.selectedIndex != 1 ? this.options[this.selectedIndex].value : \'', !empty($settings['smiley_sets_default']) ? $settings['smiley_sets_default'] : $modSettings['smiley_sets_default'], '\') + \'/smiley.gif\';">';
			foreach ($context['smiley_sets'] as $set)
				echo '
											<option value="', $set['id'], '"', $set['selected'] ? ' selected="selected"' : '', '>', $set['name'], '</option>';
			echo '
										</select> <img id="smileypr" src="', $context['member']['smiley_set']['id'] != 'none' ? $modSettings['smileys_url'] . '/' . ($context['member']['smiley_set']['id'] != '' ? $context['member']['smiley_set']['id'] : (!empty($settings['smiley_sets_default']) ? $settings['smiley_sets_default'] : $modSettings['smiley_sets_default'])) . '/smiley.gif' : $settings['images_url'] . '/blank.gif', '" alt=":)" align="top" style="padding-left: 20px;" />
									</td>
								</tr>';
		}

		if ($modSettings['theme_allow'] || $context['user']['is_admin'] || !empty($modSettings['smiley_sets_enable']))
			echo '
								<tr>
									<td colspan="2"><hr width="100%" size="1" class="hrcolor" /></td>
								</tr>';

		// Allow the user to change the way the time is displayed.
		echo '
								<tr>
									<td width="40%">
										<a href="', $scripturl, '?action=helpadmin;help=time_format" onclick="return reqWin(this.href);" class="help"><img src="', $settings['images_url'], '/helptopics.gif" alt="', $txt[119], '" align="', !$context['right_to_left'] ? 'left' : 'right', '" style="', !$context['right_to_left'] ? 'padding-right' : 'padding-left', ': 1ex;" /></a>
										<span class="smalltext">', $txt[479], '</span>
									</td>
									<td>
										<select name="easyformat" onchange="document.forms.creator.timeFormat.value = this.options[this.selectedIndex].value;" style="margin-bottom: 4px;">';
		// Help the user by showing a list of common time formats.
		foreach ($context['easy_timeformats'] as $time_format)
			echo '
											<option value="', $time_format['format'], '"', $time_format['format'] == $context['member']['time_format'] ? ' selected="selected"' : '', '>', $time_format['title'], '</option>';
	*/	echo '<tr><td>
										<b>', $txt['eveapi_userid'], ':</b></td>
										<td><input type="text" name="eveapi_user_id" value="'.$api[0].'" size="10" />
									</td>
								</tr><tr>
									<td width="40%">										<b>', $txt['eveapi_api'], ':</b></td>
										<td><input type="text" name="eveapi_user_api" value="'.$api[1].'" size="64" />
									</td>
								</tr>';
/*
		echo '
								<tr>
									<td colspan="2">
										<table width="100%" cellspacing="0" cellpadding="3">
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_board_desc]" value="0" />
													<label for="show_board_desc"><input type="checkbox" name="default_options[show_board_desc]" id="show_board_desc" value="1"', !empty($context['member']['options']['show_board_desc']) ? ' checked="checked"' : '', ' class="check" /> ', $txt[732], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_children]" value="0" />
													<label for="show_children"><input type="checkbox" name="default_options[show_children]" id="show_children" value="1"', !empty($context['member']['options']['show_children']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_children'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_avatars]" value="0" />
													<label for="show_no_avatars"><input type="checkbox" name="default_options[show_no_avatars]" id="show_no_avatars" value="1"', !empty($context['member']['options']['show_no_avatars']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_no_avatars'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_signatures]" value="0" />
													<label for="show_no_signatures"><input type="checkbox" name="default_options[show_no_signatures]" id="show_no_signatures" value="1"', !empty($context['member']['options']['show_no_signatures']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['show_no_signatures'], '</label>
												</td>
											</tr>';

		if ($settings['allow_no_censored'])
			echo '
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[show_no_censored]" value="0" />
													<label for="show_no_censored"><input type="checkbox" name="default_options[show_no_censored]" id="show_no_censored" value="1"' . (!empty($context['member']['options']['show_no_censored']) ? ' checked="checked"' : '') . ' class="check" /> ' . $txt['show_no_censored'] . '</label>
												</td>
											</tr>';

		echo '
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[return_to_post]" value="0" />
													<label for="return_to_post"><input type="checkbox" name="default_options[return_to_post]" id="return_to_post" value="1"', !empty($context['member']['options']['return_to_post']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['return_to_post'], '</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[no_new_reply_warning]" value="0" />
													<label for="no_new_reply_warning"><input type="checkbox" name="default_options[no_new_reply_warning]" id="no_new_reply_warning" value="1"', !empty($context['member']['options']['no_new_reply_warning']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['no_new_reply_warning'], '</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input type="hidden" name="default_options[view_newest_first]" value="0" />
													<label for="view_newest_first"><input type="checkbox" name="default_options[view_newest_first]" id="view_newest_first" value="1"', !empty($context['member']['options']['view_newest_first']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['recent_posts_at_top'], '</label>
												</td>
											</tr><tr>
												<td colspan="2">
													<input type="hidden" name="default_options[view_newest_pm_first]" value="0" />
													<label for="view_newest_pm_first"><input type="checkbox" name="default_options[view_newest_pm_first]" id="view_newest_pm_first" value="1"', !empty($context['member']['options']['view_newest_pm_first']) ? ' checked="checked"' : '', ' class="check" /> ', $txt['recent_pms_at_top'], '</label>
												</td>
											</tr><tr>
												<td colspan="2"><label for="calendar_start_day">', $txt['calendar_start_day'], ':</label>
													<select name="default_options[calendar_start_day]" id="calendar_start_day">
														<option value="0"', empty($context['member']['options']['calendar_start_day']) ? ' selected="selected"' : '', '>', $txt['days'][0], '</option>
														<option value="1"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 1 ? ' selected="selected"' : '', '>', $txt['days'][1], '</option>
														<option value="6"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 6 ? ' selected="selected"' : '', '>', $txt['days'][6], '</option>
													</select>
												</td>
											</tr><tr>
												<td colspan="2"><label for="display_quick_reply">', $txt['display_quick_reply'], '</label>
													<select name="default_options[display_quick_reply]" id="display_quick_reply">
														<option value="0"', empty($context['member']['options']['display_quick_reply']) ? ' selected="selected"' : '', '>', $txt['display_quick_reply1'], '</option>
														<option value="1"', !empty($context['member']['options']['display_quick_reply']) && $context['member']['options']['display_quick_reply'] == 1 ? ' selected="selected"' : '', '>', $txt['display_quick_reply2'], '</option>
														<option value="2"', !empty($context['member']['options']['display_quick_reply']) && $context['member']['options']['display_quick_reply'] == 2 ? ' selected="selected"' : '', '>', $txt['display_quick_reply3'], '</option>
													</select>
												</td>
											</tr><tr>
												<td colspan="2"><label for="display_quick_mod">', $txt['display_quick_mod'], '</label>
													<select name="default_options[display_quick_mod]" id="display_quick_mod">
														<option value="0"', empty($context['member']['options']['display_quick_mod']) ? ' selected="selected"' : '', '>', $txt['display_quick_mod_none'], '</option>
														<option value="1"', !empty($context['member']['options']['display_quick_mod']) && $context['member']['options']['display_quick_mod'] == 1 ? ' selected="selected"' : '', '>', $txt['display_quick_mod_check'], '</option>
														<option value="2"', !empty($context['member']['options']['display_quick_mod']) && $context['member']['options']['display_quick_mod'] != 1 ? ' selected="selected"' : '', '>', $txt['display_quick_mod_image'], '</option>
													</select>
												</td>
											</tr>
										</table>
									</td>
								</tr>'; */

		// Show the standard "Save Settings" profile button.

		echo '
							</table>';
	}
}
?>