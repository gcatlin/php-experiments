<?php

// $_SESSION['USER'] is accessible to request object (which is accessible to resource object)
// session is not necessary until sign in is required, after that, every request should refresh session
// every page has sign in and my account divs, an external js file determines which is displayed
// delete cookies when destroying session?
// javascript displays the login form on the same page (like digg), otherwise the login link goes to a login page
// use javascript to determine if cookies are enabled? what if javascript is disabled?
// store sessions using memcache (has its own garbage collection)
// encrypt sessions?
// store enough info about user in session that a database query is not necessary
// determine if cookies are enabled once per session
// use JS to SHA-1 the password and remove plaintext version
// api. uses HTTP Digest Auth by default, *. uses cookies by default
// do not use PHPSESSID, use SID instead
// regenerate session id whenever logging in
// regenerate session id every X requests
// use HTTP Digest Auth if cookies are disabled
// store/reuse a serialized request when POSTing to a protected resource
// Location/302 responses should contain a short hypertext note with a hyperlink to the new URI
// ignore session ids in GET request
// avoid page expiration warnings
// count and throttle login attempts for additional security
// 	count failed login attempts, limit login attempts to X times per Y minutes

/*
http://searchsecurity.techtarget.com/generic/0,295582,sid14_gci1210022,00.html
http://en.wikipedia.org/wiki/Session_fixation
http://shiflett.org/articles/how-to-avoid-page-has-expired-warnings
http://pajhome.org.uk/crypt/md5/auth.html
http://www.berenddeboer.net/rest/authentication.html
http://phplens.com/lens/adodb/docs-session.htm
*/

// what if cookies are disabled?
// what if someone's session times out? rerequest username and password the next time it's needed
// openid support

// determine if cookies are enabled first
// 	if enabled and authenticating to/from an unprotected page, a cookie will/should be sent
// 	check for WWW-Authenticate: header
// if cookies are disabled:
// 	fall back to HTTP Digest Auth?
// 	show a message? "You must enable cookies"

// remember requests just in case session timed out, then re-request

error_reporting(E_ALL | E_STRICT);
//ini_set('session.cache_limiter', 'private');
// ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', '1');
// ini_set('session.entropy_file', '/dev/urandom');
// ini_set('session.entropy_length', '32');
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '1');
ini_set('session.gc_maxlifetime', '1440'); //24 minutes
ini_set('session.hash_function', '1');
ini_set('session.hash_bits_per_character', '6');
ini_set('session.save_handler', 'files');
ini_set('session.save_path', '/tmp');
ini_set('session.name', 'SID');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('url_rewriter.tags', '0');

class CookieAuth
{
	public function run()
	{
		// check for cookie first?
		// if not set then what?
		
		session_start();
		
		// Allow user to logout
		if (isset($_POST['LOGOUT'])) {
			$this->recreateSession();
			$_SESSION['SERVER_GENERATED'] = true;
			header("Location: /gcatlin/sessions/"); // this should really be done by the Response
			exit;
		}

		// Allow user to login
		if (isset($_POST['LOGIN'])) {
			// is the session valid?
			// are cookies enabled?
			
			if ($this->authenticate($_POST['USERNAME'], $_POST['PASSWORD'])) {
				session_regenerate_id(true);
				header("Location: {$_SERVER['REQUEST_URI']}"); // this should really be done by the Response
				exit;
			}
			else {
				header("Location: /gcatlin/sessions/login.php"); // this should really be done by the Response
				exit;
			}
		}

		// Accept only server generated SIDs (discourages session fixation)
		// session cookie provided by GET, POST, or COOKIE
		if (isset($_REQUEST[session_name()]) && !isset($_SESSION['SERVER_GENERATED'])) {
			$this->recreateSession();
		}
		$_SESSION['SERVER_GENERATED'] = true;
	}
	
	protected function authenticate($username, $password)
	{
		// this needs a design pattern
		// this requires database access (or memcache, or whatever else)
		
		// if ($username == 'bob' && $password == 'bob') {
			$this->setUser(100);
			session_regenerate_id(true);
			return true;
		// }
		return false;
	}
	
	protected function recreateSession()
	{
		session_destroy();
		session_start();
		session_regenerate_id();
	}
	
	protected function setUser($user)
	{
		$_SESSION['USER'] = $user;
	}

}

		// User Agent verification (discourages session hijacking)
		//if (!isset($_SESSION['HTTP_USER_AGENT_MD5'])) {
		//	session_regenerate_id(true);
		//	$_SESSION['HTTP_USER_AGENT_MD5'] = md5($_SERVER['HTTP_USER_AGENT']);
		//}
		//elseif ($_SESSION['HTTP_USER_AGENT_MD5'] !== md5($_SERVER['HTTP_USER_AGENT']) ) {
		//  // suspicious, invalidate session
		//	session_destroy();
		//	// redirect to login page only if a protected resource was requested
		//}
		//$_SESSION['HTTP_USER_AGENT_MD5'] = md5($_SERVER['HTTP_USER_AGENT']);

$auth = new CookieAuth();
$auth->run();

function dump($label, $var) { echo "<pre>{$label}: "; ob_start(); var_dump($var); echo str_replace("=>\n  ", " => ", ob_get_clean())."</pre>\n"; }

?>
