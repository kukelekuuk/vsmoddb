<?php
if (empty($user)) {
	header("Location: /login");
	exit();
}
if (!$user['roleid']) {
	$view->display("403");
	exit();
}

if (!empty($_POST["fileid"])) {
	$fileid = $_POST["fileid"];
	$file = $con->getRow("select * from file where fileid=?", array($fileid));
	
	if ($file) {
		$assetid = $file["assetid"];
		$thumbfilename = preg_replace("/(?U)(.*)(\.\w+)$/","\\1_thumb\\2", $file["filename"]);

		if ($assetid) {
		
			$userid = $con->getOne("select createdbyuserid from asset where assetid=?", array($assetid));
			if ($userid != $user['userid'] && $user['rolecode'] != 'admin') {
				exit(json_encode(array("status" => "error", "errormessage" => 'No privilege to delete files from this asset. You may need to login again'))); 
			}
		
			$dir = "files/asset/{$assetid}/";
			@unlink($dir . $file["filename"]);
		
		} else {
			if ($file['userid'] != $user['userid']  && $user['rolecode'] != 'admin') {
                               exit(json_encode(array("status" => "error", "errormessage" => 'No privilege to delete files from this asset. You may need to login again')));
			}

			$dir = "tmp/{$user['userid']}/";
			@unlink($dir . $file['filename']);
		}

		if (file_exists($dir . $thumbfilename)) {
			@unlink($dir . $thumbfilename);
		}
		
		$con->Execute("delete from file where fileid=?", array($fileid));
		
		logAssetChanges(array("Deleted file '{$file['filename']}'"), $assetid);
		
		exit(json_encode(array("status" => "ok")));
	}
}

exit(json_encode(array("status" => "error")));
