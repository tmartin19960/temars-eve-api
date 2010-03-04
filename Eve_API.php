<?php

if (!defined('SMF'))
	die('Hacking attempt...');

Global $eve_api, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc;
loadLanguage('Eve_API');
$eve_api = new eve_api($db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc);

class eve_api
{
	var $corps;

	function __construct(&$db_prefix, &$sourcedir, &$modSettings, &$user_info, &$context, &$txt, &$smcFunc)
	{
		$this -> db_prefix = &$db_prefix;
		$this -> sourcedir = &$sourcedir;
		$this -> modSettings = &$modSettings;
		$this -> user_info = &$user_info;
		$this -> context = &$context;
		$this -> txt = &$txt;
		$this -> smcFunc = &$smcFunc;

		$this -> version = "1.0.3";

		$permissions["eveapi_view_own"] = 1;
		$permissions["eveapi_view_any"] = 0;
		$permissions["eveapi_edit_own"] = 1;
		$permissions["eveapi_edit_any"] = 0;
		// Initialize the groups array with 'ungrouped members' (ID: 0).
		// Add -1 to this array if you want to give guests the same permission
		$groups = array(0);

		// Get all the non-postcount based groups.
		$request = $this -> select("
		  SELECT ID_GROUP
		  FROM ".$this -> db_prefix."membergroups
		  WHERE min_posts = -1");
		foreach($request as $row)
			$groups[] = $row[0];

		foreach($permissions as $p => $v)
		{
		   // Give them all their new permission.
			$request = $this -> query("
			  INSERT IGNORE INTO ".$this -> db_prefix."permissions
				 (permission, ID_GROUP, add_deny)
			  VALUES
				 ('".$p."', " . implode(", $v),
				 ('".$p."', ", $groups) . ", $v)");
		}
	}

	function update_api($apiuser, $apiecho=FALSE)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		$this -> file = "\n\n\nDate: ".gmdate("F jS, Y H:i", time())."\n";

		//.$this -> get_site("", "", "")."</pre>";
		//echo "<pre>"; var_dump($this -> modSettings);die;
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
			$this -> all($apiecho);
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
		$sfile = $this -> sourcedir."/../cache/eve_standings.php";
		if(file_exists($sfile))
		{
			require($sfile);
			if($time > (time() - (60 * 60 * 24)))
			{
				$this -> cblues = $cblues;
				$this -> creds = $creds;
				$this -> ablues = $ablues;
				$this -> areds = $areds;
				Return;
			}
			unset($corps);
		}
		$data = $this -> get_site($this -> modSettings["eveapi_userid"], $this -> modSettings["eveapi_api"], $this -> modSettings["eveapi_charid"], 'standings');

		$temp[1] = $this -> xmlparse($data, "corporationStandings");
		$temp[2] = $this -> xmlparse($data, "allianceStandings");
		foreach($temp as $i => $data)
		{
			if($i == 1)
				$oi = 2;
			else
				$oi = 1;
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
					{
						$this -> cblues[$corp[0]][0] = $corp[1];
						$this -> cblues[$corp[0]][$i] = $corp[2];
						$count++;
						if(!isset($this -> cblues[$corp[0]][$oi]))
							$this -> cblues[$corp[0]][$oi] = 0;
					}
					elseif($corp[2] < 0)
					{
						$this -> creds[$corp[0]][0] = $corp[1];
						$this -> creds[$corp[0]][$i] = $corp[2];
						$count++;
						if(!isset($this -> creds[$corp[0]][$oi]))
							$this -> creds[$corp[0]][$oi] = 0;
					}
				}
			}
		//	var_dump($this -> creds);die;
			if(!empty($alliances))
			{
				foreach($alliances as $alliance)
				{
					if($alliance[2] > 0)
					{
						$this -> ablues[$alliance[0]][0] = $alliance[1];
						$this -> ablues[$alliance[0]][$i] = $alliance[2];
						$count++;
						if(!isset($this -> ablues[$alliance[0]][$oi]))
							$this -> ablues[$alliance[0]][$oi] = 0;
					}
					elseif($alliance[2] < 0)
					{
						$this -> areds[$alliance[0]][0] = $alliance[1];
						$this -> areds[$alliance[0]][$i] = $alliance[2];
						$count++;
						if(!isset($this -> areds[$alliance[0]][$oi]))
							$this -> areds[$alliance[0]][$oi] = 0;
					}
				}
			}
		}

		if($count > 0)
		{
			$file = '<?php'."\n\n";
			$file .= '$time = '.time().';'."\n\n";
			foreach($this -> cblues as $c => $a)
			{
				$file .= '$cblues['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			foreach($this -> creds as $c => $a)
			{
				$file .= '$creds['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			foreach($this -> ablues as $c => $a)
			{
				$file .= '$ablues['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			foreach($this -> areds as $c => $a)
			{
				$file .= '$areds['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			$file .= '?>';
			$fp = fopen($sfile, 'w');
			fwrite($fp, $file);
			fclose($fp);
		}
	//	var_dump($this -> areds);die;
	}

	function single($user, $echo, $group=FALSE)
	{
		$mongroups[0] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_unknown"]] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_corp"]] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_alliance"]] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_blue"]] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_red"]] = TRUE;
		$mongroups[$this -> modSettings["eveapi_groupass_neut"]] = TRUE;

		$this -> chars = array();

		$txt = $this -> txt;

		if(is_numeric($user))
			$id = $this -> select("SELECT ID_MEMBER, ID_GROUP FROM ".$this -> db_prefix."members WHERE ID_MEMBER = ".$user);
		if(empty($id))
			$id = $this -> select("SELECT ID_MEMBER, ID_GROUP FROM ".$this -> db_prefix."members WHERE member_name = '".$user."'");
		if(!empty($id))
		{
			$group = $id[0][1];
			$id = $id[0][0];
			$apiusers = $this -> select("SELECT userid, api FROM ".$this -> db_prefix."eve_api WHERE ID_MEMBER = ".$id);
			if(!empty($apiusers))
			{
				foreach($apiusers as $apiuser)
				{
					$apikey = $apiuser[1];
					$apiuser = $apiuser[0];

					if(!isset($mongroups[$group]))
					{
						$this -> file .= $txt['eveapi_run_custom']."\n";
						if($echo)
							echo $txt['eveapi_run_custom']."\n<br>";
						$ignore = TRUE;
					}
					$chars = $this -> get_characters($apiuser, $apikey);
					if(!empty($chars))
					{
					//	$charlist = array();
						foreach($chars as $char)
						{
							$this -> chars[] = $char;
							//var_dump($char);
							//$charlist[$char[1]] = array($char[0], ;
							$corp = $char[3];
							//if(isset($this -> corps[$corp]))
							//{
								$alliance = $this -> corps[$corp];
								//echo "alliance!!!! $alliance\n<br>";
							//}
							if($corp == $this -> modSettings["eveapi_corpid"])
							{
								$incorp = TRUE;
							}
							elseif(isset($this -> cblues[$corp]) || isset($this -> ablues[$alliance]))
							{
								$inblues = TRUE;
							}
							if(isset($this -> creds[$corp]) || isset($this -> areds[$alliance]))
							{
								$inreds = TRUE;
							}
							if($alliance == $this -> modSettings["eveapi_allianceid"])
							{
								$inalliance = TRUE;
							}
							$corpinfo = $this -> corp_info($corp);
							if(empty($corpinfo))
								$corpinfo['ticker'] = "Unknown";
							if($this -> modSettings["eveapi_corptag_options"] == 1)
							{
								$this -> query("UPDATE ".$this -> db_prefix."members SET usertitle = '".$corpinfo['ticker']."' WHERE ID_MEMBER = ".$id);
							}
							$this -> query("
								REPLACE INTO ".$this -> db_prefix."eve_characters
									(userid, charid, name, corpid, corp, corp_ticker, allianceid)
								VALUES 
								('" . mysql_real_escape_string($apiuser) . "', '" . mysql_real_escape_string($char[1]) . "', '" . mysql_real_escape_string($char[0]) . "', '" . mysql_real_escape_string($corp) . "', '" . mysql_real_escape_string($corpinfo['name']) . "', '" . mysql_real_escape_string($corpinfo['ticker']) . "', '$alliance')");
						}
						if(!$incorp && !$inblues && !$inreds)
							$inneuts = TRUE;
					//	$charlist = implode(";", $charlist);
					//	if($charlist != $charnames)
					//		$this -> query("UPDATE ".$this -> db_prefix."eve_api SET characters = '".mysql_real_escape_string($charlist)."' WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
						$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'OK', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
					}
					else
					{
						//get error
						$error = $this -> get_error($this -> data);
						$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'API Error', errorid = '".$error[0]."', error = '".$error[1]."', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
						if(($error[0] >= 500 && $error[0] < 600) || ($error[0] >= 900 && $error[0] < 1000)) // Api System is Down
							$ignore = TRUE;
					}
				}
			}
			if($ignore)
				Return;
			$nogroup = $this -> modSettings["eveapi_groupass_unknown"];
			if(!$nogroup)
				$nogroup = 0;
			$corp = $this -> modSettings["eveapi_groupass_corp"];
			if(!$corp)
				$corp = 0;
			$alliance = $this -> modSettings["eveapi_groupass_alliance"];
			if(!$alliance)
				$alliance = 0;
			$blue = $this -> modSettings["eveapi_groupass_blue"];
			if(!$blue)
				$blue = 0;
			$red = $this -> modSettings["eveapi_groupass_red"];
			if(!$red)
				$red = 0;
			$neut = $this -> modSettings["eveapi_groupass_neut"];
			if(!$neut)
				$neut = 0;

			//15 = doom
			if($inreds)
			{
				$e = "";
				if($incorp)
					$e = " ".$txt['eveapi_run_alsocorp'];
				if($inblues)
					$e .= " ".$txt['eveapi_run_alsoblue'];
				if($group == $red)
				{
					$this -> file .= $txt['eveapi_run_ared'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_ared'].$e."\n<br>";
				}
				else
				{
					$this -> file .= $txt['eveapi_run_red'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_red'].$e."\n<br>";
					$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $red WHERE ID_MEMBER = ".$id);
				}
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'red', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($incorp)
			{
				$e = "";
				if($inblues)
					$e = " ".$txt['eveapi_run_alsoblue'];
				if($group == $corp)
				{
					$this -> file .= $txt['eveapi_run_acorp'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_acorp'].$e."\n<br>";
				}
				else
				{
					$this -> file .= $txt['eveapi_run_corp'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_corp'].$e."\n<br>";
					$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $corp WHERE ID_MEMBER = ".$id);
				}
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'corp', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($inalliance)
			{
				$e = "";
				if($inblues)
					$e = " ".$txt['eveapi_run_alsoblue'];
				if($group == $alliance)
				{
					$this -> file .= $txt['eveapi_run_aalliance'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_aalliance'].$e."\n<br>";
				}
				else
				{
					$this -> file .= $txt['eveapi_run_alliance'].$e."\n";
					if($echo)
						echo $txt['eveapi_run_alliance'].$e."\n<br>";
					$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $alliance WHERE ID_MEMBER = ".$id);
				}
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'alliance', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($inblues)
			{
				if($group == $blue)
				{
					$this -> file .= $txt['eveapi_run_ablue']."\n";
					if($echo)
						echo $txt['eveapi_run_ablue']."\n<br>";
				}
				else
				{
					$this -> file .= $txt['eveapi_run_blue']."\n";
					if($echo)
						echo $txt['eveapi_run_blue']."\n<br>";
					$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $blue WHERE ID_MEMBER = ".$id);
				}
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'blue', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($inneuts)
			{
				if($group == $neut)
				{
					$this -> file .= $txt['eveapi_run_aneut']."\n";
					if($echo)
						echo $txt['eveapi_run_aneut']."\n<br>";
				}
				else
				{
					$this -> file .= $txt['eveapi_run_neut']."\n";
					if($echo)
						echo $txt['eveapi_run_neut']."\n<br>";
					$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $neut WHERE ID_MEMBER = ".$id);
				}
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'neut', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($group != $nogroup)
			{
				$this -> file .= $txt['eveapi_run_reg']."\n";
				if($echo)
					echo $txt['eveapi_run_reg']."\n<br>";
				$this -> query("UPDATE ".$this -> db_prefix."members SET ID_GROUP = $nogroup WHERE ID_MEMBER = ".$id);
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'error', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
			elseif($group == $nogroup)
			{
				$this -> file .= $txt['eveapi_run_areg']."\n";
				if($echo)
					echo $txt['eveapi_run_areg']."\n<br>";
				$this -> query("UPDATE ".$this -> db_prefix."eve_api SET status = 'error', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
			}
		}
	}

	function get_characters($userid, $api)
	{
		$chars = $this -> get_site($userid, $api);
		$this -> data = $chars;
		$chars = $this -> xmlparse($chars, "result");
		$chars = $this -> parse($chars);
		if(!empty($chars))
		{
			$charlist = array();
			foreach($chars as $char)
			{
				$charlist[] = $char;
			}
		}
		Return $charlist;
	}

	function get_all_chars($id=FALSE, $getticker=FALSE)
	{
		$ID_MEMBER = $this -> user_info['id'];
		// Did we get the user by name...
		if (isset($_REQUEST['user']))
			$memberResult = loadMemberData($_REQUEST['user'], true, 'profile');
		// ... or by ID_MEMBER?
		elseif (!empty($_REQUEST['u']))
			$memberResult = loadMemberData((int) $_REQUEST['u'], false, 'profile');
		// If it was just ?action=profile, edit your own profile.
		else
			$memberResult = loadMemberData($ID_MEMBER, false, 'profile');
		$memID = $memberResult[0];

		$user = $this -> select("SELECT userid FROM ".$this -> db_prefix."eve_api WHERE ID_MEMBER = ".$memID);
		if(!empty($user))
		{
			foreach($user as $acc)
			{
				$chars = $this -> select("SELECT charid, name, corp_ticker FROM ".$this -> db_prefix."eve_characters WHERE userid = ".$acc[0]);
				if(!empty($chars))
				{
					foreach($chars as $char)
					{
						if($getticker)
						{
						//	$ticker = $this -> corp_info($corp);
							if(!empty($char[2]))
								$char[1] = "[".$char[2]."] ".$char[1];
						}

						if($id)
							$charlist[$char[0]] = $char[1];
						else
							$charlist[$char[1]] = $char[1];
					}
				}
			}
		}
		Return $charlist;
	}

	function all($apiecho)
	{
		if($apiecho)
			echo "checking all...\n<br>";
		$api = $this -> select("SELECT member_name, ID_GROUP FROM ".$this -> db_prefix."members");
		if(!empty($api))
		{
			foreach($api as $user)
			{
				if($apiecho)
					echo $user[0];
				$this -> file .= $user[0];
				$this -> single($user[0], $apiecho, $user[1]);
			}
		}
		$fp = fopen("api.log", 'a');
		fwrite($fp, $this -> file);
		fclose($fp);
	}

	function get_site($id, $api, $charid=FALSE, $type=FALSE)
	{
		if($type == 'standings')
			$url = "http://api.eve-online.com/corp/Standings.xml.aspx";
		elseif($type == 'alliances')
			$url = "http://api.eve-online.com/eve/AllianceList.xml.aspx";
		elseif($type == 'corp')
		{
			$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
			$corpid = $charid;
			unset($charid);
		}
		else
			$url = "http://api.eve-online.com/account/Characters.xml.aspx";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set your login and password for authentication
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pw);

		if(!empty($id))
			$post[] = 'userID='.$id;
		if(!empty($api))
			$post[] = 'apiKey='.$api;
		if(!empty($charid))
			$post[] = 'characterID='.$charid;
		if(!empty($corpid))
			$post[] = 'corporationID='.$corpid;
		if(!empty($post))
		{
			$post = implode('&', $post);
			curl_setopt($ch, CURLOPT_POST      ,1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}

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

	function select($sql, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
	{
		$data = "";
	//	$result = mysql_query($sql);
		$result = $this -> smcFunc['db_query']('', $sql);

		if (!$result)
		{
			echo $sql;
			if($error)
				echo "<BR>".$this -> smcFunc['db_error']."<BR>";
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		if($result_form == MYSQL_ASSOC)
		{
			while ($row = $this -> smcFunc['db_fetch_assoc']($result))
			{
				$data[] = $row;
			}
		}
		else
		{
			while ($row = $this -> smcFunc['db_fetch_row']($result))
			{
				$data[] = $row;
			}
		}

		$this -> smcFunc['db_free_result']($result);
		return $data;
	}

	function query($sql)
	{
		$return = $this -> smcFunc['db_query']('', $sql);

		if (!$return)
		{
			echo $sql;
			echo "<BR>".$this -> smcFunc['db_error']."<BR>";
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

	function get_error($data)
	{
		$data = explode('<error code="', $data, 2);
		$data = explode('">', $data[1], 2);
		$id = $data[0];
		$data = explode('</error>', $data[1], 2);
		$msg = $data[0];
		Return(array($id, $msg));
	}

	function alliance_list($update=TRUE)
	{
		$sfile = $this -> sourcedir."/../cache/eve_corplist.php";
		if(file_exists($sfile))
		{
			require($sfile);
			if(count($corps) > 5 && $time > (time() - (60 * 60 * 24)))
			{
				$this -> corps = $corps;
				Return $time;
			}
			unset($corps);
		}
		if(!$update)
			Return;
		$data = $this -> get_site(FALSE, FALSE, FALSE, 'alliances');
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
			$fp = fopen($sfile, 'w');
			fwrite($fp, $file);
			fclose($fp);
			$this -> corps = $corps;
		}
		Return $time;
	}

	function rowset2($xml)
	{
		$tmp = explode('<rowset name="alliances" key="allianceID" columns="name,shortName,allianceID,executorCorpID,memberCount,startDate">', $xml, 2);
		return $tmp[1];
	}

	function corp_info($corp)
	{
		$data = $this -> get_site(FALSE, FALSE, $corp, 'corp');
		$info['name'] = $this -> xmlparse($data, 'corporationName');
		$info['ticker'] = $this -> xmlparse($data, 'ticker');
		$info['allianceid'] = $this -> xmlparse($data, 'allianceID');
		$info['alliance'] = $this -> xmlparse($data, 'allianceName');
		Return ($info);
	}

	function Settings(&$txt, $scripturl, &$context, $settings, $sc)
	{
	//	$txt = $this -> txt;
		if (isset($_GET['update']))
		{
			$this -> update_api(FALSE);
			$file = str_replace("\n", "<br>", $this -> file);
			$config_vars = array(
			'',
			$file
			);
		}
		else
		{
			//echo "<pre>"; var_dump($this -> modSettings);die;
			$atime = $this -> alliance_list(FALSE);
			if($atime)
				$atime = gmdate("G:i D d M y", $atime).' (GMT)';
			else
				$atime = 'Never';
			if (isset($_GET['save']))
			{
				$charid = $_POST["eveapi_charid"];
				$userid = $_POST["eveapi_userid"];
				$api = $_POST["eveapi_api"];
			}
			else
			{
				$charid = $this -> modSettings["eveapi_charid"];
				$userid = $this -> modSettings["eveapi_userid"];
				$api = $this -> modSettings["eveapi_api"];
			}
			$chars = $this -> get_characters($userid, $api);
			$charlist = array();
			if(!empty($chars))
			{
				foreach($chars as $char)
				{
					//var_dump($char);
					$charlist[$char[1]] = $char[0];
					if($charid == $char[1])
					{
						$corp = $char[3];
						$alliance = $this -> corps[$corp];
					}
				}
			}
			$file = $this -> sourcedir."/../cache/eve_standings.php";
			if(file_exists($file))
				require($file);
			if($time)
				$time = gmdate("G:i D d M y", $time).' (GMT)';
			else
				$time = 'Never';
			$groups = $this -> MemberGroups();
			$config_vars = array(
				'<dt>'.$txt['eveapi_version'].': '.$this -> version.'</dt>',
				'',
					// enable?
					array('check', 'eveapi_enable'),
				'',
					// api info
					array('int', 'eveapi_userid', 10),
					array('text', 'eveapi_api', 64),
					array('select', 'eveapi_charid', $charlist),
				'<dt><a href="'.$scripturl.'?action=admin;area=eveapi;update">'.$txt['eveapi_fullcheck'].'</a></dt>',
				'<dt>'.$txt['eveapi_standings_updated'].': '.$time.'</dt>',
				'<dt>'.$txt['eveapi_standings_contains'].': '.count($cblues).' '.$txt['eveapi_standings_bluec'].', '.count($creds).' '.$txt['eveapi_standings_bluea'].', '.count($ablues).' '.$txt['eveapi_standings_redc'].', '.count($areds).' '.$txt['eveapi_standings_reda'].'</dt>',
				'<dt>'.$txt['eveapi_corpl_updated'].': '.$atime.'</dt>',
				'<dt>'.$txt['eveapi_corpl_contains'].': '.count($this -> corps).'</dt>',
				'',
					array('check', 'eveapi_regreq'),
					array('check', 'eveapi_usecharname'),
					array('check', 'eveapi_avatar_enabled'),
					array('int', 'eveapi_corpid', 10),
					array('int', 'eveapi_allianceid', 10),
					array('check', 'eveapi_useapiabove'),
					array('select', 'eveapi_corptag_options', array(0 => 'Nothing', 1 => 'Custom Title', 2 => 'Part of Name')),
				'',
				'<dt>'.$txt['eveapi_group_settings'].'</dt>',
					array('select', 'eveapi_groupass_red', $groups),
					array('select', 'eveapi_groupass_corp', $groups),
					array('select', 'eveapi_groupass_alliance', $groups),
					array('select', 'eveapi_groupass_blue', $groups),
					array('select', 'eveapi_groupass_neut', $groups),
					array('select', 'eveapi_groupass_unknown', $groups),
				'',
					// Who's online.
			//		array('check', 'who_enabled'),
			);

			// Saving?
			if (isset($_GET['save']))
			{
				if(isset($_POST['eveapi_useapiabove']))
				{
					$_POST['eveapi_corpid'] = $corp;
					$_POST['eveapi_allianceid'] = $alliance;
					unset($_POST['eveapi_useapiabove']);
				}
				saveDBSettings($config_vars);
				redirectexit('action=admin;area=eveapi');

				loadUserSettings();
				writeLog();
			}
		}

		$context['post_url'] = $scripturl . '?action=admin;area=eveapi;save';
		$context['settings_title'] = $txt['eveapi_title'];
		$context['settings_message'] = $txt['eveapi_settings_message'];

		prepareDBSettingContext($config_vars);
	}

	function MemberGroups()
	{
		$list = $this -> select('SELECT id_group, group_name FROM '.$this -> db_prefix.'membergroups WHERE min_posts = -1 ORDER BY group_name');
		if(!empty($list))
		{
			foreach($list as $l)
			{
				$groups[$l[0]] = $l[1];
			}
		}
		unset($groups[1]);
		unset($groups[3]);
		$groups[0] = "no membergroup";
		Return $groups;
	}

	function EveApiAdd($memberID, $reg)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
	//	echo $memberID." kk ".$db_prefix;
	//	var_dump($_POST);
		if(!is_numeric($memberID))
			return;

		//var_dump($_POST);die;
		$userids = $_POST['eveapi_user_id'];
		foreach($userids as $k => $userid)
		{
			if($userid == "")
				Continue;
			$api = $_POST['eveapi_user_api'][$k];
			$user = $this -> select("SELECT userid, api, status, status_change, auto FROM ".$this -> db_prefix."eve_api WHERE ID_MEMBER = ".$memberID." AND userid = ".mysql_real_escape_string($userid));
			if(!empty($user))
			{
				$duserid = $user[0][0];
				$dapi = $user[0][1];
				$auto = $user[0][4];
			//	$chars = $user[0][2];
			//	$charid = $user[0][2];
			}
			else
			{
				$auto = 1;
				$chars  = '';
				$charid = 0;
			}
			if(!$userid || !$api)
				Continue;
			if($duserid != $userid || $dapi != $api)
			{
				$this -> query("
					REPLACE INTO ".$this -> db_prefix."eve_api
						(ID_MEMBER, userid, api, status, status_change, auto)
					VALUES 
					($memberID, '" . mysql_real_escape_string($userid) . "', '" . mysql_real_escape_string($api) . "', 'unchecked', ".time().", $auto)");
			}
		}
		if(isset($_POST['del_api']))
		{
			foreach($_POST['del_api'] as $userid)
			{
				$this -> query("DELETE FROM ".$this -> db_prefix."eve_api WHERE ID_MEMBER = $memberID AND userid = '" . mysql_real_escape_string($userid) . "'");
			}
		}
		unset($_POST['del_api']);
		unset($_POST['eveapi_user_id']);
		unset($_POST['eveapi_user_api']);
		$this -> update_api($memberID);
		if($reg)
		{
			foreach($this -> chars as $char)
			{
				$corp = $char[3];
				$alliance = $this -> corps[$corp];
				if($corp == $this -> modSettings["eveapi_corpid"])
				{
					$main = $char;
					$match = 4;
				}
				$corp = $char[3];
				if($match < 3 && $alliance == $this -> modSettings["eveapi_allianceid"])
				{
					$main = $char;
					$match = 3;
				}
				elseif($match < 3 && (isset($this -> cblues[$corp]) || isset($this -> ablues[$alliance])))
				{
					$main = $char;
					$match = 2;
				}
				elseif($match < 2 && (isset($this -> creds[$corp]) || isset($this -> areds[$alliance])))
				{
					$main = $char;
					$match = 1;
				}
				elseif($match < 1)
				{
					$main = $char;
					$match = 0;
				}
			}
			if($modSettings['eveapi_usecharname'])
			{	
				$this -> query("UPDATE ".$this -> db_prefix."members SET real_name = '".$main[0]."' WHERE ID_MEMBER = ".$memberID);
			}
			if($modSettings['eveapi_avatar_enabled'])
			{
				
			}
		}
	}

	function DisplayAPIinfo(&$context, &$modSettings, $db_prefix, &$txt)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		return;
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
	{ // old settings mod?
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
		$context['settings_message'] = $txt['eveapi_settings_message'];

	//	prepareDBSettingContext($config_vars);
	}

	function UserModifyEveApi($memID, &$eveapiinfo, &$context, &$settings, &$options, $scripturl, &$modSettings, &$txt, $db_prefix)
	{ // is this a valid function? clearly code for my other mod, but is it safe to delete, or is it partly in use and other code is junk
		if(!$this -> modSettings["eveapi_enable"])
			Return;
		if(!empty($_POST))die;

		loadLanguage('AOCharLink');
		//	isAllowedTo('eveapi_edit_any');
		if(!is_numeric($memID))
			die("Invalid User id");
		$user = $this -> select("SELECT userid, api, status, error FROM ".$db_prefix."eve_api WHERE ID_MEMBER = ".$memID);
		if(!empty($user))
		{
			foreach($user as $u)
			{
				$characters = $this -> get_all_chars(FALSE, TRUE);
				
				if($u[1] == "tnet")
					$checkt = "checked";
				else
					$checkb = "checked";
				if($u[3] == "unverified" && strlen($u[0]) < 3)
				{
					$msg = "Please set a Character 1st";
				}
				elseif($u[3] == "unverified")
				{
					$msg = "/tell ".ucfirst($u[1])." !cv $u[2]";
				}
				elseif($u[3] == "verified")
				{
					$msg = "You are Already Verified";
				}
				$eveapiinfo[] = array(
				"userid" => $u[0],
				"api" => $u[1],
			//	"msg" => $msg,
				'charnames' => implode(", ", $characters),
				'status' => $u[2],
				'error' => $u[3]
				);
			}
		}
	}

	function RegistrationFields(&$db_prefix, &$txt)
	{
		if(!$this -> modSettings["eveapi_enable"])
			Return;


//echo '							<table border="0" width="100%" cellpadding="3">';

//		echo '<tr><td>
//										<b>', $txt['eveapi_userid'], ':</b></td>
//										<td><input type="text" name="eveapi_user_id[]" value="'.$api[0].'" size="10" />
//									</td>
//								</tr><tr>
//									<td width="40%">										<b>', $txt['eveapi_api'], ':</b></td>
//										<td><input type="text" name="eveapi_user_api[]" value="'.$api[1].'" size="64" />
//									</td>
//								</tr>';
		echo '<dl class="register_form"><dt>
										<b>', $txt['eveapi_userid'], ':</b></dt>
										<dd><input type="text" name="eveapi_user_id[]" value="'.$api[0].'" size="10" />
									</dd>
								</dl><dl class="register_form">
									<dt>										<b>', $txt['eveapi_api'], ':</b></dt>
										<dd><input type="text" name="eveapi_user_api[]" value="'.$api[1].'" size="64" />
									</dd>
								</dl>';


		// Show the standard "Save Settings" profile button.

	//	echo '
	//						</table>';
	}

	function avatar_option()
	{
		echo '
			<script type="text/javascript">
				function getPortrait(id)
				{
					var maxHeight = ', !empty($modSettings['avatar_max_height_external']) ? $modSettings['avatar_max_height_external'] : 0, ';
					var maxWidth = ', !empty($modSettings['avatar_max_width_external']) ? $modSettings['avatar_max_width_external'] : 0, ';
					var tempImage = new Image();

					tempImage.src = \'http://img.eve.is/serv.asp?s=64&c=\'+id;
					if (maxWidth != 0 && tempImage.width > maxWidth)
					{
						document.getElementById("eavatar").style.height = parseInt((maxWidth * tempImage.height) / tempImage.width) + "px";
						document.getElementById("eavatar").style.width = maxWidth + "px";
					}
					else if (maxHeight != 0 && tempImage.height > maxHeight)
					{
						document.getElementById("eavatar").style.width = parseInt((maxHeight * tempImage.width) / tempImage.height) + "px";
						document.getElementById("eavatar").style.height = maxHeight + "px";
					}
					document.getElementById("eavatar").src = \'http://img.eve.is/serv.asp?s=64&c=\'+id;
				}
			</script>
								<div id="avatar_tea">
									<select name="attachment" value="', $this -> context['member']['avatar']['tea'], '"  onfocus="selectRadioByName(document.forms.creator.avatar_choice, \'tea\');" onchange="getPortrait(this.value);" >';
		$chars = $this -> get_all_chars(TRUE);
	//	echo "\n<pre>"; var_dump($this -> context['member']['avatar']);die;
		if(!empty($chars))
		{
			foreach($chars as $id => $char)
			{
				echo '<option value="'.$id.'">'.$char.'</option>';
			}
		}
		echo '			</select>
<br><img name="eavatar" id="eavatar" src="', !empty($this -> modSettings["eveapi_enable"]) && $this -> context['member']['avatar']['choice'] == 'tea' ? $this -> context['member']['avatar']['tea'] : $this -> modSettings['avatar_url'] . '/blank.gif', '" />
								</div>';

	}

	function avatar_save($memID, &$profile_vars, &$cur_profile)
	{
		// Remove any attached avatar...
		removeAttachments(array('id_member' => $memID));

		$profile_vars['avatar'] = $_POST['attachment'];

	//	if ($profile_vars['avatar'] == 'http://' || $profile_vars['avatar'] == 'http:///')
	//		$profile_vars['avatar'] = '';
		// Trying to make us do something we'll regret?
	//	var_dump($profile_vars['avatar']);die;
		if (!is_numeric($profile_vars['avatar']))
			return 'bad_avatar';

		// Should we check dimensions?
	//	elseif (!empty($modSettings['avatar_max_height_external']) || !empty($modSettings['avatar_max_width_external']))
	//	{
			// Now let's validate the avatar.
	//		$sizes = url_image_size($profile_vars['avatar']);

	//		if (is_array($sizes) && (($sizes[0] > $modSettings['avatar_max_width_external'] && !empty($modSettings['avatar_max_width_external'])) || ($sizes[1] > $modSettings['avatar_max_height_external'] && !empty($modSettings['avatar_max_height_external']))))
	//		{
				// Houston, we have a problem. The avatar is too large!!
	//			if ($modSettings['avatar_action_too_large'] == 'option_refuse')
	//				return 'bad_avatar';
	//			elseif ($modSettings['avatar_action_too_large'] == 'option_download_and_resize')
	//			{
					require_once($this -> sourcedir . '/Subs-Graphics.php');
					if (downloadAvatar('http://img.eve.is/serv.asp?s=64&c='.$profile_vars['avatar'], $memID, 64, 64))
					{
						$profile_vars['avatar'] = '';
						$cur_profile['id_attach'] = $this -> modSettings['new_avatar_data']['id'];
						$cur_profile['filename'] = $this -> modSettings['new_avatar_data']['filename'];
						$cur_profile['attachment_type'] = $this -> modSettings['new_avatar_data']['type'];
					}
	//			}
	//		}
	//	}
	}
}
function editeveapi($memID)
{
	global $eve_api, $eveapiinfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt, $db_prefix;
	$eve_api -> UserModifyEveApi($memID, $eveapiinfo, $context, $settings, $options, $scripturl, $modSettings, $txt, $db_prefix);
}
function ModifyEveApiSettings()
{
	global $eve_api, $sourcedir, $txt, $scripturl, $context, $settings, $sc;
	// Will need the utility functions from here.
	require_once($sourcedir . '/ManageServer.php');

	$context['sub_template'] = 'show_settings';
	$eve_api -> Settings($txt, $scripturl, $context, $settings, $sc);
}

function template_editeveapi()
{
	global $eve_api, $eveapiinfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt, $db_prefix;
	echo '
		<form action="', $scripturl, '?action=profile;area=eveapi;save" method="post" accept-charset="', $context['character_set'], '" name="creator" id="creator">
			<table border="0" width="100%" cellspacing="1" cellpadding="4" align="center" class="bordercolor">
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
	if(!$modSettings["eveapi_enable"])
	{
		echo '<tr><td>'.$txt['eveapi_disabled'].'</td></tr>';
	}
	else
	{
			//if($user[3] == "unverified")
				//echo "<tr><td>Please use this command to Verify the Character<br>".$eveapiinfo['msg']."<br></td></tr>";
$eveapiinfo[] = array();
				foreach($eveapiinfo as $i => $info)
				{
					echo '<tr><td colspan="3"><hr class="hrcolor" width="100%" size="1"/></td></tr>';
		echo '<tr><td>
										<b>', $txt['eveapi_status'], ':</b></td><td>'.$info['status'];
		if($info['status'] == 'API Error')
			echo ' ('.$info['error'].')';
		echo '</td>
			</tr><tr><td>
										<b>', $txt['eveapi_characters'], ':</b></td><td>'.$info['charnames'].'</td>
			</tr>';
		echo '<tr><td>
										<b>', $txt['eveapi_userid'], ':</b></td>
										<td>';
					if($info['userid'] == "")
						echo '<input type="text" name="eveapi_user_id[]" value="'.$info['userid'].'" size="20" />';
					else
					{
						echo '<input type="hidden" name="eveapi_user_id[]" value="'.$info['userid'].'" size="20" />';
						echo $info['userid'].'</td><td> <input type="checkbox" name="del_api[]" value="'.$info['userid'].'" /> Delete</td>';
					}
						echo '			</td>
								</tr><tr>
									<td width="40%">										<b>', $txt['eveapi_api'], ':</b></td>
										<td><input type="text" name="eveapi_user_api[]" value="'.$info['api'].'" size="64" />
									</td>
								</tr>';
				}
		template_profile_save();
	}
	echo '
						</table>
					</td>
				</tr>
			</table>
		</form>';
}

?>