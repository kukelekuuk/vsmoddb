<?php
	header('Content-Type: text/html; charset=utf-8');
	
	global $config, $con, $view;
	error_reporting(E_ALL & ~E_DEPRECATED);

	include($config["basepath"] . "lib/config.php");

	include($config["basepath"] . "lib/ErrorHandler.php");
	ErrorHandler::setupErrorHandling();
	

	include($config["basepath"] . "lib/timezones.php");
	include($config["basepath"] . "lib/View.php");
	include($config["basepath"] . "lib/img.php");
	include($config["basepath"] . "lib/tags.php");
	include($config["basepath"] . "lib/3rdparty/adodb5/adodb-exceptions.inc.php");
	include($config["basepath"] . "lib/3rdparty/adodb5/adodb.inc.php");
	
	include($config["basepath"] . "lib/asset.php");
	include($config["basepath"] . "lib/assetcontroller.php");
	include($config["basepath"] . "lib/assetlist.php");
	include($config["basepath"] . "lib/asseteditor.php");
	
	
	$rd = opendir($config["basepath"] . "lib/assetimpl");
	while (($file = readdir($rd))) {
		if (endsWith($file, ".php")) {
			include($config["basepath"] . "lib/assetimpl/".$file);
		}
	}
	
	$con = createADOConnection($config);
	$view = new View();
	
	include($config["basepath"] . "lib/auth.php");

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC; 
	
	

	// insert db record
	function insert($tablename, $recordid = null, $con = null) {
		if (!$con) $con = $GLOBALS['con'];

		$con->Execute("insert into `{$tablename}` (created) values (now())");

		return $con->Insert_ID();
	}

	// update db record
	function update($tablename, $recordid, $data, $con = null) {
		if (!$con) $con = $GLOBALS['con'];

		$columnnames = array();
		$values = array();
		foreach($data as $columnname => $value) {
			array_push($columnnames, "`{$columnname}`= ?");
			array_push($values, $data[$columnname]);
		}

		$updatessql = "
			update `{$tablename}` set " . join(", ", $columnnames) . " where {$tablename}id = ?";

		return $con->Execute($updatessql, array_merge(
			$values,
			array($recordid)
		));
	}

	// delete db record
	function delete($tablename, $recordid) {
		global $con;

		$con->Execute("delete from `{$tablename}` where `{$tablename}id` = ?", array($recordid));
	}
	


	function print_p($var) {
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}


	function endsWith($string, $part) {
		return preg_match("/(".preg_quote($part).")$/", $string);
	}
	
	function isNumber($val) {
		return intval($val)."" == $val;
	}

	function isUrl($url) {
		return strlen(filter_var($url, FILTER_VALIDATE_URL));
	}
	
	function sanitizeHtml($text) {
		global $config;
		include_once($config["basepath"] . "lib/3rdparty/htmLawed.php");
		
		return htmLawed($text, array('tidy' => 0, 'safe' => 1, 'elements'=>'* -script -object -applet -canvas -iframe -video -audio -embed'));
	}

	
	function createADOConnection($config, $persistent = true) {
		$con = ADONewConnection("mysqli");
		
		$result = $con->NConnect($config["databasehost"], $config["databaseuser"], $config["databasepassword"], $config["database"]);
		
		if (! $result) {
			throw new Exception("Error connecting to database. " . $con->_errorMsg);
			die();
		}
		
		return $con;
	}
	
	function getURLPath() {
		global $con, $language;

		$scripturl = $_SERVER['REQUEST_URI'];
		if(strstr($scripturl, "?")) {
			$scripturl = substr($scripturl, 0, strpos($scripturl, "?"));
		}
		$urlcode = substr($scripturl, 1);


		return $urlcode;
	}
	
	
	function genToken() {
		return base64_encode(openssl_random_pseudo_bytes(32));
	}	
	
	function createPasswordHash($password) {
		// http://php.net/manual/en/function.password-hash.php
		return password_hash($password, PASSWORD_DEFAULT);
	}

	function verifyPasswordHash($password, $hash) {
		return password_verify($password, $hash);
	}
	
	
	function logAssetChanges($changes, $assetid) {
		global $con, $user;
		
		if (!empty($changes)) {
			$changelogdb = $con->getRow("select * from changelog order by created desc limit 1");
			$changelogid = 0;
			if ($changelogdb && $changelogdb["assetid"] == $assetid && $changelogdb["userid"] == $user["userid"]) {
				$changesdb = explode("\r\n", $changelogdb["text"]);
				$changelogid = $changelogdb["changelogid"];
				
				$changes = array_unique(array_merge($changes, $changesdb));
			}
			
			if (!$changelogid) {
				$changelogid = insert("changelog");
			}
			
			update("changelog", $changelogid, array("assetid" => $assetid, "userid" => $user["userid"], "text" => implode("\r\n", $changes)));
		}
	}
	
	function logError($str) {
		logLine($str, "logs/error.txt");
	}


	function logLine($str, $debugfile) {
		if (!is_writable($debugfile) || !file_exists($debugfile)) {
			return;
		}
		
		$fp = fopen($debugfile, 'a');
		fwrite($fp, date('d.m.Y H:i:s: ') . $str . "\n");
		fclose($fp);
	}

	
	
	function fullDate($sqldate) {
		return date("M jS Y, H:i:s", strtotime($sqldate));
	}

	function timelessDate($sqldate) {
		return date("M jS Y", strtotime($sqldate));
	}

	function fancyDate($sqldate) {
		if (empty($sqldate)) return "-";
		$timestamp = strtotime($sqldate);
		$localtimestamp = getLocalTimeStamp($timestamp);
		
		$seconds = time() - $timestamp;
		$strdate = date("M jS Y, H:i:s", $localtimestamp);
		
		if ($seconds >= 0 && $seconds < 7*24*3600) {
			$minutes = intval($seconds / 60);
			$hours = intval($seconds / 3600);
			$days = intval($seconds / 3600 / 24);
			
			if ($days < 1) {
				if ($seconds <= 60) {
					return '<span title="'.$strdate.'">' . $seconds . " seconds ago</span>";
				}
				if ($hours < 1) {
					if ($minutes == 1) return '<span title="'.$strdate.'">1 minute ago</span>';
					return '<span title="'.$strdate.'">' . $minutes . ' minutes ago</span>';
				}
				
				if ($hours == 1) return '<span title="'.$strdate.'">1 hour ago</span>';
				return '<span title="'.$strdate.'">' . $hours . " hours ago</span>";
				
			} else {
				if ($days == 1) return '<span title="'.$strdate.'">1 day ago</span>';
				return '<span title="'.$strdate.'">' . $days . " days ago</span>";
			}
		}
		
		if (date("Y", $localtimestamp) != date("Y")) {
			return '<span title="'.$strdate.'">' . date("M jS Y", $localtimestamp) . '</span>';
		} else {
			return '<span title="'.$strdate.'">' . date("M jS", $localtimestamp) . '</span>';
		}
	}
	
	
	function getLocalTimeStamp($timestamp) {
		global $user, $timezones;
		
		$local_tz = new DateTimeZone(date_default_timezone_get());
		$localtime = new DateTime('now', $local_tz);

		//NY is 3 hours ahead, so it is 2am, 02:00
		$userzone = date_default_timezone_get();

		if ($user && !empty($timezones[$user["timezone"]])) {
			$userzone = $timezones[$user["timezone"]];
		}
		
		$user_tz = new DateTimeZone($userzone);
		$usertime = new DateTime('now', $user_tz);

		$local_offset = $localtime->getOffset() / 3600;
		$user_offset = $usertime->getOffset() / 3600;

		$hourdiff = $user_offset - $local_offset;
		
		return $timestamp + 3600 * $hourdiff;
	}


	function autoFormat($html) {
		// http:///..... => Create a link from it
		$html = linkify($html, 1);
		// [spoiler] 
		$html = preg_replace("/\[spoiler\]\s*(.*)\s*\[\/spoiler\]/Us", "<p><a href=\"#\" class=\"spoiler\">Spoiler</a></p><div class=\"spoiler\">\\1</div>" , $html);
		
		return $html;
	}


	function linkify($value, $showimg = 1, $protocols = array('http', 'mail', 'https'), array $attributes = array('target' => '_blank'))
	{       
		// Link attributes
		$attr = '';
		foreach ($attributes as $key => $val) {
			$attr = ' ' . $key . '="' . htmlentities($val) . '"';
		}
		
		$links = array();
		
		// Extract existing links and tags
		$value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value);
		
		// Extract text links for each protocol
		foreach ((array)$protocols as $protocol) {
			switch ($protocol) {
				case 'http':
				case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', 
					function ($match) use ($protocol, &$links, $attr, $showimg) { 
						if ($match[1]){
							$protocol = $match[1]; $link = $match[2] ?: $match[3]; 
							// Youtube
							if($showimg == 1){                                  
								if(strpos($link, 'youtube.com')>0 || strpos($link, 'youtu.be')>0){                                  
									$link = '<iframe width="100%" height="315" src="https://www.youtube.com/embed/'.end(explode('=', $link)).'?rel=0&showinfo=0&color=orange&iv_load_policy=3" frameborder="0" allowfullscreen></iframe>';
									return '<' . array_push($links, $link) . '></a>';
								}
								if(strpos($link, '.png')>0 || strpos($link, '.jpg')>0 || strpos($link, '.jpeg')>0 || strpos($link, '.gif')>0 || strpos($link, '.bmp')>0){
									return '<' . array_push($links, "<a $attr href=\"$protocol://$link\" class=\"htmllink\"><img src=\"$protocol://$link\" class=\"htmlimg\">") . '></a>';
								}
							}                           
							return '<' . array_push($links, "<a $attr href=\"$protocol://$link\" class=\"htmllink\">$link</a>") . '>';                          
						}                           
				}, $value); break;
				case 'mail':    $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\" class=\"htmllink\">{$match[1]}</a>") . '>'; }, $value); break;
				case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\" class=\"htmllink\">{$match[0]}</a>") . '>'; }, $value); break;
				default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\" class=\"htmllink\">{$match[1]}</a>") . '>'; }, $value); break;
			}
		}
		
		// Insert all link
		return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value);
	}
		
		
		
	// Erstellt POST Request und übermittelt Parameter die aus $data ausgelesen werden
	function sendPostData($path, $data, $remoteurl = null) {
		global $config;
		
		if ($remoteurl == null) {
			$remoteurl = "https://" . $config["authserver"] . "/" . $path;
		} else {
			$remoteurl = $remoteurl . "/" . $path;
		}
		
		$httpopts = array(
		  "http" => array(
			"method"  => "POST",
			"header"  => "Content-type: application/x-www-form-urlencoded" . "\r\n",
			"content" => http_build_query($data)
		  )
		);

		$context = stream_context_create($httpopts);
		$result = file_get_contents($remoteurl, false, $context);
		
		if (!empty($GLOBALS["authDebug"])) {
			echo "request sent. Result is";
			print_p($result);
		}

		return $result;
	}




	function getModInfo($filepath) {
		$returncode = null;
		if (substr(PHP_OS, 0, 3) === 'WIN') {
			$idver = exec("util\\modpeek.exe -i -f ".escapeshellarg($filepath), $unused, $returncode);
		} else {
			$idver = exec("mono util/modpeek.exe -i -f ".escapeshellarg($filepath), $unused, $returncode);
		}
		
		if ($returncode != 0) {
			return array("modparse" => "error", "parsemsg" => "Unable to find mod id and version, which must be present in any mod (.cs, .dll, or .zip). If you are certain you added it, please contact Tyron");
		}
		
		$parts = explode(":", $idver);
		if (count($parts) != 2) {
			return array("modparse" => "error", "parsemsg" => "Unable to determine mod id and version, which must be present in any mod (.cs, .dll, or .zip). If you are certain you added it, please contact Tyron");
		}
		
		return array("modparse" => "ok", "modid" => $parts[0], "modversion" => $parts[1]);
	}
	
	function updateGameVersionsCached($modid) {
		global $con;
		$modid = intval($modid);
		
		$tagids = $con->getCol("select distinct tag.tagid from `release` join assettag on (`release`.assetid = assettag.assetid) join `tag` on (assettag.tagid = tag.tagid) where modid=?", array($modid));
		$inserts = array();
		foreach ($tagids as $tagid) $inserts[] = "({$tagid}, {$modid})";
		
		$con->Execute("delete from modversioncached where modid=?", array($modid));
		
		if (count($tagids)>0) $con->Execute("insert into modversioncached values " . implode(",", $inserts));
	}
