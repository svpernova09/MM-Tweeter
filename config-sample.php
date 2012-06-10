<?php
/*
MM-Tweeter
http://wiki.midsouthmakers.org/a/MM-Tweeter
*/
global $dbhost;
global $dbuser;
global $dbpass;
global $dbname;
global $tbl_secrets;
global $throttle;
$dbms = 'mysql4';
$dbhost = 'localhost';
$dbname = '';
$dbuser = '';
$dbpass = '';
$tbl_pending = 'pending';
$tbl_history = 'history';
$tbl_secrets = 'secrets';
$throttle = '4'; //Max Tweets at one time
$attempts = '3'; //Max attempts to tweet before deletion from pending - do NOT set > 9
global $attempts;
$replace_this = array('%3E%22%3E%3Cscript%3Ealert%28123%29%3C%2Fscript%3E%3C%22', '>"><script>alert(123)</script><"', '>\"><script>alert(123)</script><\"', '=>"><script>alert()</script><"', '=>"><script>alert(123);</script><"', '=>"><script>alert(123)</script><"', '<script>alert(123)</script>', '<script>', '</script>', '<script', '<script ', 'script>', 'alert()', 'alert();', 'alert(', '>\"><iframe src=/>', '<iframe', '<iframe src=', '<iframe ', 'iframe>', '</iframe>', '</iframe>', 'iframe', '>\<\"', '> \ " > <  / >', '> \ " >', '<  / >', '">', '" >', '<"', '< "', '/>', '/ >', 'cat EOF', 'curl -L', 'search=Search', 'bsql');
if(isset($_POST)){
    $PostVars = $_POST;
   foreach ( $PostVars as $key => $value ) {
         $_POST[$key] = str_ireplace($replace_this, "", $value);
         $_POST[$key] = str_ireplace($replace_this, "", $_POST[$key]);
         if(isset($GLOBALS[$key])){
            $GLOBALS[$key] = $_POST[$key];
         }
   }
// echo "<pre>";
// print_r($_POST);
}
if(isset($_GET)){
   $GetVars = $_GET;
   foreach ( $GetVars as $key => $value ) {
         $_GET[$key] = str_ireplace($replace_this, "", $value);
         $_GET[$key] = str_ireplace($replace_this, "", $_GET[$key]);
         if(isset($GLOBALS[$key])){
            $GLOBALS[$key] = $_GET[$key];
         }
   }
   //echo "<pre>";
   //print_r($_GET);
}
if(isset($_REQUEST)){
   $ReqVars = $_REQUEST;
   foreach ( $ReqVars as $key => $value ) {
 
         $_REQUEST[$key] = str_ireplace($replace_this, "", $value);
         $_REQUEST[$key] = str_ireplace($replace_this, "", $_REQUEST[$key]);
         if(isset($GLOBALS[$key])){
            $GLOBALS[$key] = $_REQUEST[$key];
         }
   }
   //echo "<pre>";
   //print_r($_GET);
}
//echo "<br><br><PRE>";
//print_r($GLOBALS);

?>