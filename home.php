<?php

if (!empty($user)) {
	$sql = "
		select 
			asset.*, 
			`mod`.*,
			status.code as statuscode
		from 
			asset 
			join `mod` on asset.assetid = `mod`.assetid
			left join status on asset.statusid = status.statusid
		where
			asset.createdbyuserid = ?
		order by asset.created desc
	";
	
	$ownmods = $con->getAll($sql, array($user['userid']));
	
	foreach($ownmods as &$row) {
		unset($row['text']);
		$row["tags"] = array();
		$row['from'] = $user['name'];
		
		$tagscached = trim($row["tagscached"]);
		if (empty($tagscached)) continue;
		
		$tagdata = explode("\r\n", $tagscached);
		$tags=array();
		
		foreach($tagdata as $tagrow) {
			$parts = explode(",", $tagrow);
			$tags[] = array('name' => $parts[0], 'color' => $parts[1], 'tagid' => $parts[2]);
		}
		
		$row['tags'] = $tags;
	}
	
	unset($row);
	
	$view->assign("mods", $ownmods);
}


$latestentries = $con->getAll("
	select 
		asset.*,
		`mod`.*,
		user.name as `from`,
		status.code as statuscode,
		status.name as statusname
	from 
		asset
		join `mod` on asset.assetid = `mod`.assetid
		join user on (asset.createdbyuserid = user.userid)
		join status on (asset.statusid = status.statusid)
	where
		asset.statusid=2
	order by
		asset.created desc
	limit 10
");

$view->assign("latestentries", $latestentries);

$latestcomments = $con->getAll("
	select 
		comment.*,
		asset.name as assetname,
		assettype.name as assettypename,
		assettype.code as assettypecode,
		user.name as username
	from 
		comment
		join user on (comment.userid = user.userid)
		join asset on (comment.assetid = asset.assetid)
		join assettype on (asset.assettypeid = assettype.assettypeid)
	order by
		comment.lastmodified desc
	limit 20
");

$view->assign("latestcomments", $latestcomments, null, true);

$view->display("home.tpl");
