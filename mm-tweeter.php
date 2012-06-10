<?php
/**
* post_tweet.php
* Example of posting a tweet with OAuth
* Latest copy of this code: 
* http://140dev.com/twitter-api-programming-tutorials/hello-twitter-oauth-php/
* @author Adam Green <140dev@gmail.com>
* @license GNU Public License
*/

//$tweet_text = 'Testing from the web.';
//print "Posting...\n$tweet_text\n";
//$result = post_tweet($tweet_text);
//print "Response code: " . $result . "\n";

function post_tweet($tweet_text) {
	global $our_consumer_key;
	global $our_consumer_secret;
	global $our_user_token;
	global $our_user_secret;
	global $debug;
  // Use Matt Harris' OAuth library to make the connection
  // This lives at: https://github.com/themattharris/tmhOAuth
  require_once('tmhoauth/tmhOAuth.php');

  // Set the authorization values
  // In keeping with the OAuth tradition of maximum confusion, 
  // the names of some of these values are different from the Twitter Dev interface
  // user_token is called Access Token on the Dev site
  // user_secret is called Access Token Secret on the Dev site
  // The values here have asterisks to hide the true contents 
  // You need to use the actual values from Twitter

  $connection = new tmhOAuth(array(
    'consumer_key' => $our_consumer_key,
    'consumer_secret' => $our_consumer_secret,
    'user_token' => $our_user_token,
    'user_secret' => $our_user_secret,
  )); 
  print_r($connection);
  // Make the API call
  $connection->request('POST', 
    $connection->url('1/statuses/update'), 
    array('status' => $tweet_text));
  
  return $connection->response['code'];
}
function updatepending($pending_tweet,$response_code){
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_pending;
	global $attempts;
	//$response_code wasn't 200, this means something went wrong
	//Update the tweet in the pending table with the response code & the number of attempts
	// Delete after $attempts global
	$updatependingConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $updatependingConn) or die ("Unable To Select Database $dbname"); 
	if ($attempts >= $pending_tweet['attempts']){
		//haven't reached $attempts limit. increment $attempts
		if($debug){ echo("Attempts: $attempts. pending_tweet[attempts]: $pending_tweet[attempts]<BR>"); }
		if($debug){ echo("Haven't reached attempts limit, increment attempts<BR>"); }
		$pending_tweet['attempts'] = $pending_tweet['attempts'] + 1;
	} else {
		//$attempts limit reached, we should add to history and remove from pending
		if($debug){ echo("Attempts: $attempts. pending_tweet[attempts]: $pending_tweet[attempts]<BR>"); }
		if($debug){ echo("Attempts limit reached, we should add to history and remove from pending<BR>"); }
		addhistory($pending_tweet,$response_code);
		removepending($pending_tweet);
	}
	$updatepending_sql = "UPDATE $tbl_pending SET attempts = '$pending_tweet[attempts]', last_error_code = '$response_code' WHERE id = '$pending_tweet[id]' LIMIT 1";
	if($debug){ echo("updatepending_sql: $updatepending_sql<BR>"); }
	$updatepending_result = mysql_query($updatepending_sql,$updatependingConn);
	if($updatepending_result) { 
		echo("pending_tweet[id]: $pending_tweet[id] updated<br />");
	} else {
		mysql_error();
	}
	
	mysql_close();
}
function checkpost_date($post_date){
    if (date('Y-m-d H:i:s', strtotime($post_date)) == $post_date) {
        return 1;
    } else {
        return 0;
    }
}
function checksecret($source, $secret){
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_secrets;
	$checksecretConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $checksecretConn) or die ("Unable To Select Database $dbname"); 
	$checksecret_sql = "SELECT * from $tbl_secrets WHERE source = '$source' AND secret = '$secret'";
	if($debug){ echo("checksecret_sql: $checksecret_sql<BR>"); }
	$checksecret_result = mysql_query ($checksecret_sql,$checksecretConn);
	$checksecret_num_rows = mysql_num_rows($checksecret_result);
	if($debug){ echo("checksecret_num_rows: $checksecret_num_rows<BR>"); }
	if($checksecret_num_rows == '1'){
		if($debug){ echo("Secret Matches, Proceed<BR>"); }
		return 1;
	}
	if($debug){ echo("Secret Does Not Match, Halt<BR>"); }
	mysql_close();
	return 0;
}
function addpending($source,$secret,$post_date, $content){
	//new incoming tweet
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_pending;
	$added = date('Y-m-d H:i:s');
	//$content = addslashes($content);
	$content = mysql_real_escape_string($content);
	$source = mysql_real_escape_string($source);
	$post_date = mysql_real_escape_string($post_date);
	$secret = mysql_real_escape_string($secret);
	$date = new DateTime($post_date);
	$date->add(new DateInterval('PT5M'));
	$post_date = $date->format('Y-m-d H:i:s') . "\n";
	$addpendingConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $addpendingConn) or die ("Unable To Select Database $dbname"); 
	$addpending_sql = "INSERT INTO $tbl_pending (content, post_date, added, source) VALUES ('$content', '$post_date', '$added', '$source')";
	if ($debug){ echo("addpending_sql: $addpending_sql<BR>"); }
	if (mysql_query($addpending_sql)) {
		if ($debug){ echo("Added to $tbl_pending<BR>"); }
		return 1;
	} else {
		if ($debug){ print "<p>There was an error:<b>" . mysql_error() . "</b>. The query was:<BR>$addpending_sql"; }
	}
	mysql_close();
	return 0;
	
}
function addhistory($thistweet,$response_code){
	//Once a pending tweet has been tweeted, add it to the history
	//incoming $thistweek should be an array of the tweet values
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_history;
	$thistweet['content'] = addslashes($thistweet['content']);
	$post_date = date('Y-m-d H:i:s');
	$addhistoryConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $addhistoryConn) or die ("Unable To Select Database $dbname"); 
	$addhistory_sql = "INSERT INTO $tbl_history (content, post_date, added, source, response_code) values ('$thistweet[content]', '$post_date', '$thistweet[added]', '$thistweet[source]', '$response_code')";
	if ($debug){ echo("addhistory_sql: $addhistory_sql<BR>"); }
	if (mysql_query($addhistory_sql)) {
		if ($debug){ echo("Added $thistweet[id] to $tbl_history<BR>"); }
	} else {
		if ($debug){ print "<p>There was an error:<b>" . mysql_error() . "</b>. The query was:<BR>$addhistory_sql"; }
	}
	mysql_close();
}
function removepending($thistweet){
	//Remove from pending once they've been tweeted
	//incoming $thistweek should be an array of the tweet values
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_pending;
	if ($debug){ echo("<pre>"); print_r($thistweet); echo("</pre>"); }
	$removependingConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $removependingConn) or die ("Unable To Select Database $dbname"); 
	$removepending_sql = "DELETE FROM $tbl_pending WHERE id='$thistweet[id]' LIMIT 1";
	if ($debug){ echo("removepending_sql: $removepending_sql<BR>"); }
	$removepending_result = mysql_query ($removepending_sql);
	if (mysql_affected_rows() == 1){
		if ($debug){ echo("This Tweet was removed from $tbl_pending<BR>"); }
		if ($debug){ echo("<pre>"); print_r($thistweet); echo("</pre>"); }
		return 1;
	} else {
		if ($debug){ print "Error was <b>" . mysql_error() . "</b><BR>The Query was: $removepending_sql"; }
	}
	mysql_close();
	return 0;
}
function checkpending($source,$secret){
	global $dbhost;
	global $dbuser;
	global $dbpass;
	global $dbname;
	global $debug;
	global $tbl_pending;
	global $throttle;
	$currenttime = date('Y-m-d H:i:s');
	//are there any pending tweets to post?	
	$checkpendingConn = mysql_connect("$dbhost","$dbuser","$dbpass", true) or die ("Unable to connect to the database");
	@mysql_select_db("$dbname", $checkpendingConn) or die ("Unable To Select Database $dbname"); 
	$checkpending_sql = "SELECT * from $tbl_pending WHERE 1 LIMIT $throttle";
	if($debug){ echo("checkpending_sql: $checkpending_sql<BR>"); }
	$checkpending_result = mysql_query ($checkpending_sql,$checkpendingConn);
	$checkpending_num_rows = mysql_num_rows($checkpending_result);
	if($debug){ echo("There are $checkpending_num_rows Pending. We should post at most: $throttle.<BR>"); }
	$pending_tweets = array();
	while($checkpending_row = mysql_fetch_array($checkpending_result)){
		$pending_tweets[$checkpending_row['id']] = array("id" => "$checkpending_row[id]",
														"content" => "$checkpending_row[content]",
														 "post_date" => "$checkpending_row[post_date]",
														  "source" => "$checkpending_row[source]",
														   "secret" => "$checkpending_row[secret]",
														   "added" => "$checkpending_row[added]",
														   "attempts" => "$checkpending_row[attempts]",);
	}
	if ($debug){ echo("<pre>"); print_r($pending_tweets); echo("</pre>"); }
	foreach ($pending_tweets as $pending_tweet) {
		if ($debug){ echo("<pre>"); print_r($pending_tweet); echo("</pre>"); }
		if(strtotime($pending_tweet['post_date']) < strtotime($currenttime)){
			if($debug){ echo("CurrentTime: $currenttime<BR>Post Date: $pending_tweet[post_date]<BR>"); }
			$response_code = post_tweet($pending_tweet['content']);
			if($debug){ echo("Response Code: $response_code<BR>"); }
			if ($response_code == "200"){
				//tweet successful
				if($debug){ echo("Successful Tweet:<br />"); }
				if ($debug){ echo("<pre>"); print_r($pending_tweet); echo("</pre>"); }
				addhistory($pending_tweet, $response_code);
				removepending($pending_tweet);
				
			} else {
				//tweet wasn't successful 
				updatepending($pending_tweet,$response_code);	
			}
		} else{
			if($debug){ echo("CurrentTime: $currenttime<BR>Post Date: $pending_tweet[post_date]<BR>"); }
			if($debug){ echo("Not time to tweet yet.<BR>"); }
		}
	}
	mysql_close($checkpendingConn);
	return 0;
}
?>