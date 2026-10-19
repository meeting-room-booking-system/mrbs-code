<?php
namespace MRBS\Auth;
use \MRBS\User;

// start TUBHH modification
/*
 * Authentication scheme for the Patrons Account Information API (PAIA)
 *
 * To use this authentication scheme set the following in config.inc.php:
 *
 * $auth["type"]           = "paia";
 *
 * // Set your server without trailing slash
 * $auth["paia"]["host"]   = "https://paia.gbv.de/DE-830"; 
 * 
 * // Set any format for the display Name with #NAME and #BARCODE; falls back to 
 * // Barcode if empty. 
 * // DO NOT CHANGE when productive with reservations (login and entries are 
 * // connected by this!)
 * $auth["paia"]["format"] = "#NAME (#BARCODE)"; 
 *
 * // (Deny user groups access; for example allow reservation only for faculty members and deny it for external users)
 * $auth["paia"]["restrict"]= array(29, 30, 31, 32, 33, 39, 40, 42, 43, 44, 48, 49, 60, 80); 
 * 
 * // As the entered login name
 * $auth["admin"][]        = "08300173390"; 
 * 
 * (only works with default session type ($auth["session"] = "php"))
 *
 */
class AuthPaia extends Auth {
    /* authValidateUser($user, $pass)
     * 
     * Gets access token if the specified username/password pair are valid
     * Also sets UserData als PHP sesssion data
     * 
     * @param string    $user  - The user name (usually a 11-12 digit long library card id)
     * @param string    $pass  - The password
     * 
     * Returns:
     *   false    - The pair are invalid or do not exist
     *   string   - The validated username
     */ 
    public function validateUser($user, $pass) {
        global $auth;
        
        // Check if user is banned; this check is apart from PAIA (and also used by the checkin addon) ; added 2021-11-08
        if (file_exists('addons/checkin/ban_check.inc.php')) {
            include 'addons/checkin/ban_check.inc.php';
            if (is_user_banned($user) === true) return false;
        }

        // Encode special characters like #
        $pass = rawurlencode($pass);

        // Login & token (suppress warning on wrong url/user/password)
        $patron         = @file_get_contents($auth["paia"]["host"].'/auth/login?username='.$user.'&password='.$pass.'&grant_type=password&scope=read_patron');
        $http_status    = explode(' ', $http_response_header[0])[1]; // https://www.php.net/manual/en/reserved.variables.httpresponseheader.php

        // Error in request?
        // @todo: needs some feedback other than unknown user
        if (!$patron || ($http_status < 200 && $http_status >= 300)) {
            //echo("No access. HTTP Status: $http_status");
            return false;
        }

        // PAIA token is valid for 3600 seconds
        $access_token   = json_decode($patron, true)['access_token'];

        if ($access_token) {
            return $this->getUserData($access_token, $user);
        } else {
            return false;
        }

    }


    /* Query user data with valid access token
     * 
     * PAIA accesss token in GBV hosting are by default valid for 3600 seconds.
     *
     *
     * @note / @todo: Keep track of the token validity (time) yourself. 
     *                (But only one query after login should ever be needed usually)
     * 
     * @param string    $access_token  - as received by authValidateUser()
     * @param string    $user  - The user name (usually a 11-12 digit long library card id)
     * 
     * Returns:
     *   string   - The username fomated as set in config ($auth["paia"]["format"] = "#NAME (#BARCODE)";) or only with barcode as default
     *   array    - $_SESSION["data"] is populated with name, barcode, email, status and usertype, until (membership end)
     */
    public function getUserData($access_token, $user) {
        global $auth;

        // Get user data
        $patron_data_json = file_get_contents($auth["paia"]["host"].'/core/'.$user.'?access_token='.$access_token);
        $patron_data      = json_decode($patron_data_json, true);

        // Usertype (might be useful to restrict access by patron status)
        $user_type = explode(':', $patron_data['type'][0])[2];
        
        // Deny access for some user types
        // @todo: needs some feedback other than unknown user
        if (isset($auth["paia"]["restrict"])) {
            if (in_array($user_type, $auth["paia"]["restrict"])) return false;
        }
        
        // Membership valid in days (just an example)
        $today  = new \DateTime();
        $expiry = \DateTime::createFromFormat('Y-m-d', $patron_data['expires']);
        $interval = $today->diff($expiry);
        //echo $today->format('d.m.Y').'<br>'. $expiry->format('d.m.Y').'<br>'.$interval->format('%R%a days').'<br>';

        // Status ($patron_data['status'])
        // 0 = active, 
        // 1 = inactive (OUS-Status 8 (Ausweisverlust), 9 "Siehe interne Bemerkung"),  
        // 2 = inactive because account expired, 
        // 3 = inactive because of outstanding fees, 
        // 4 = inactive because account expired and outstanding fees

        //$_SESSION["data"]['name']       = $patron_data['name']; // Try to save as little personal data as possible by default
        $_SESSION["data"]['barcode']    = $user;
        $_SESSION["data"]['email']      = $patron_data['email'];
        $_SESSION["data"]['status']     = $patron_data['status'];
        $_SESSION["data"]['usertype']   = $user_type;
        $_SESSION["data"]['until']      = $expiry;
        
        // Format display name as given in config (or fall back to barcode)
        if (isset($auth["paia"]["format"])) {
            $display_name = $auth["paia"]["format"];
        } else {
            $display_name = '#BARCODE';
        }

        $display_name = str_replace('#NAME', trim($patron_data['name']), $display_name);
        $display_name = str_replace('#BARCODE', trim($user), $display_name);
        
        $_SESSION["data"]['name'] = $display_name;
        
        //return $user;
        return $display_name;
    }


    /* 
     * User Data as of 2020-08-24. 
     *
     * Always just returns the provided user name (@see https://sourceforge.net/p/mrbs/bugs/481/)
     * 
     * $username - The user name (barcode)
     *
     * Returns:
     *   User object
     */
     /*
    public function getUser($username) {
        $user = new User($username);
        $user->display_name = $username;
        $user->email = $_SESSION["data"]['email'];
        $user->level = $this->getUserLevel($user);

        return $user;
    }
*/

    /* authGetUserLevel($user)
     * 
     * Determines the user's access level
     * 
     @param string    $user  - The user name (usually a 11-12 digit long library card id)
     *
     * Returns:
     *   The user's access level
     */
    public function getUserLevel($user) {
        global $auth;
        
        // User not logged in, user level '0'
        if(!isset($user)) return 0;

        // Check whether the user is an admin
        foreach ($auth['admin'] as $admin) {
            if(strcasecmp($_SESSION["data"]['barcode'], $admin) === 0) {
                return 2;
            }
        }

        // Everybody else is access level '1'
        return 1;
    }  
    
}
// end TUBHH modification
