<?php
global $debug;
$debug = 1;
if($debug){
	ini_set ('display_errors', 1);
	error_reporting (E_ALL & ~E_NOTICE);
	echo("Debug is on<BR>");
} else {
	ini_set ('display_errors', 0);
}

require_once ('config.php');
require_once ('mm-tweeter.php');

if(isset($_POST['secret'])){
	$source = trim($_POST['source']);
	$content = trim($_POST['content']);
	$post_date = trim($_POST['post_date']);
	$secret = trim($_POST['secret']);
	$added = '';
	checkpending($source,$secret);
}
if(isset($_GET['secret'])){
	$source = trim($_GET['source']);
	$content = trim($_GET['content']);
	$post_date = trim($_GET['post_date']);
	$secret = trim($_GET['secret']);
	$added = '';
	checkpending($source,$secret);
} else{
	echo("<p>You Shouldn't be here. If you think you should, you did something wrong to get here</p>");
	checkpending(demo,dummy);
}
//$validsource = checksecret('self','c0:36:48:b7:a5:76:ae:3d:ee:9c:da:fd:8c:2c:42:0d');
$validsource = checksecret($source,$secret);
$validpost_date = checkpost_date($post_date);
if ($validsource || $validpost_date){
	if($debug){ echo("Source is valid and post_date is valid<BR />");}
	if ($content == ''){
		if($debug){ echo("Content is empty / blank<BR />");}
	} else {
		if($debug){ echo("Content isn't empty. Add to pending.<BR />");}
		$added_pending = addpending($source,$secret,$post_date, $content);	
	}
}
?>