<?php

// $Id$

/* getAuth($realm)
 * 
 * Request that the username/password be given for the specified realm
 * 
 * $realm - Which username/password do we want.
 * 
 * Nothing
 */
function authGet($realm)
{
	Header("WWW-Authenticate: Basic realm=\"$realm\"");
	Header("HTTP/1.0 401 Unauthorized");
}

/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */
function authValidateUser($user, $pass)
{
        global $auth;
	
	// Check if we do not have a username/password
	if(!isset($user) || !isset($pass))
	{

/*
 * CJ: We should always have Username and Password available.
 *     So generate an "error - message".
 */
          $CJ_Login_text = "Debug ! authValidateUser No names ? $user $pass";
          $CJ_call_url = "Bogus";
          CJ_Create_Login_Screen($CJ_Login_text,$CJ_call_url);

/*
 * CJ: Not needed !      authGet($auth[realm]);
 */
		return 0;
	}
	
	
	// Generate the command line
	$cmd = $auth[prog] . ' ' . $auth[params];
	$cmd = preg_replace('/#USERNAME#/',$user,$cmd);
	$cmd = preg_replace('/#PASSWORD#/',$pass,$cmd);

	// Run the program
	exec($cmd, $output, $ret);
	
	// If it succeeded, return success
	if($ret == 0)
		return 1;
	
	// If we failed prompt for username/password
	authGet($realm);
	
	// return failure
	return 0;
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user, $lev1_admin)
{
	// User not logged in, user level '0'
	if(!isset($user))
		return 0;
	
	// Check if the user is can modify
	for($i = 0; $lev1_admin[$i]; $i++)
	{
		if(strcasecmp($user, $lev1_admin[$i]) == 0)
			return 2;
	}
	
	// Everybody else is access level '1'
	return 1;
}

/*
 * CJ: 
 *
 * The implicit $PHP_AUTH_USER does not work for a CGI php ! Only modules
 * for apache. So the idear is to set a Cookie - but:
 *
 * The first time we are called we don't have a cookie - so generate
 * a form to inquire about the username and password.
 *
 * The second time we are called we don't have the cookie - but we do
 * have a username and a password - so generate a cookie with the
 * password and username - AND return the password and username -
 * BUT ask the external $auth[prog] first !!!
 *
 * The subsequent times we are called when the cookie is set - just
 * return password and username.
 *  
 */

function getUserName()
{
  $CJ_switch = "user";
  $CJ_auth_username = CJ_Cookie_Auth($CJ_switch);
  return $CJ_auth_username;
}

function getUserPassword()
{
  $CJ_switch = "pass";
  $CJ_auth_password = CJ_Cookie_Auth($CJ_switch);
  return $CJ_auth_password;
}

function CJ_Cookie_Auth($CJ_switch)
{
       global $auth;
       global $HTTP_ENV_VARS,$HTTP_SERVER_VARS;
       global $HTTP_POST_VARS,$HTTP_COOKIE_VARS;
       global $CJ_cookie_set_count;

       $CJ_call_url = "http://www.mrl.ucsb.edu" . $HTTP_ENV_VARS["PATH_INFO"];
       $CJ_call_url = $CJ_call_url . '?' . $HTTP_SERVER_VARS["argv"][0];
       $CJ_auth_cookie = $HTTP_COOKIE_VARS["CJ_mrbs_auth_cookie"];
       $CJ_auth_username = $HTTP_POST_VARS["CJ_auth_username"];
       $CJ_auth_password = $HTTP_POST_VARS["CJ_auth_password"];

       // We don't have a cookie - but we do have username and password.
       // Do authorization check and generate a cookie if needed.
       if (($CJ_auth_cookie == "") && (!$CJ_auth_username == "") &&
           (!$CJ_auth_password == "")) {

         $cmd = $auth[prog] . ' ' . $auth[params];
         $cmd = preg_replace('/#USERNAME#/',$CJ_auth_username,$cmd);
         $cmd = preg_replace('/#PASSWORD#/',$CJ_auth_password,$cmd);
         exec($cmd, $output, $ret);

         if ($ret == 0) {

           // That's ugly: Relying on uninitialized global variable.
           if ($CJ_cookie_set_count == 0) {
             $CJ_auth_cookie = "$CJ_auth_username CJ_SEP $CJ_auth_password";

             setcookie ($auth[CJ_cookie_name],
                        $CJ_auth_cookie,
                        time() + $auth[CJ_cookie_life_time],
                        $auth[CJ_cookie_directory],
                        $auth[CJ_cookie_domain] = ".mrl.ucsb.edu");

             $CJ_cookie_set_count = 1;
           }

           if ($CJ_switch == "pass") {
             return $CJ_auth_password;
           } else {
             return $CJ_auth_username;
           }

         } else {

           $CJ_Login_text = $auth[CJ_tryagain_message];
           CJ_Create_Login_Screen($CJ_Login_text,$CJ_call_url);

           exit;

         }
       }

       // Cookie is not set - and we don't have username and password
       // generate a login form.
       if ($CJ_auth_cookie == "") {

         $CJ_Login_text = $auth[CJ_logon_message];
         CJ_Create_Login_Screen($CJ_Login_text,$CJ_call_url);

         exit;
       }

       // Cookie is set - return password or username from cookie.
       if (!$CJ_auth_cookie == "") {

         $CJ_strings = preg_split("/ CJ_SEP /","$CJ_auth_cookie");

         if ($CJ_switch == "pass") {
           return $CJ_strings[1];
         } else {
           return $CJ_strings[0];
         }
       }
}

function CJ_Create_Login_Screen ($CJ_Login_text,$CJ_call_url)
{
  global $lang;

/*
 * Added
 * TD.CJ {border:0px; font-size: 16pt;} 
 * to mrbs.css
 */

  ?>
     <HTML>
     <HEAD>
     <TITLE><?echo $lang[mrbs]?></TITLE>
     <?include "style.inc"?>

     </HEAD>
     <BODY>

     <BR>
     <FORM NAME="Login" METHOD="POST"
           ACTION="<? echo $CJ_call_url ?>">
     <TABLE>
       <TR><TD CLASS=CJ COLSPAN =3><? echo $CJ_Login_text?></TD></TR>
       <TR><TD CLASS=CJ>Username:</TD><TD CLASS=CJ>Password:</TD></TR>
       <TR><TD><INPUT TYPE=TEXT NAME="CJ_auth_username"></TD>
           <TD><INPUT TYPE=PASSWORD NAME="CJ_auth_password"></TD></TR>
       <TR><TD><INPUT TYPE="SUBMIT" NAME="Login" VALUE="Login"></TD>
           <TD></TD></TR>
     </TABLE>
     </FORM>

     </BODY>
     </HTML>

  <?php

  exit;                                                                
}

?>
