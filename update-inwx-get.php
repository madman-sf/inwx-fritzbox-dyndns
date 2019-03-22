<?php
/*
 * update-inwx.php - Update INWX Nameserver-Record
 * 
 * This script updates the Nameserver-Record at the inwx.de provider.
 *   
 * by Thomas klumpp
 * mod by Florian-t
 * mod by Sven Foerster
 */

header('Content-type: text/plain; charset=utf-8');
if ($debug) {
    ini_set("log_errors", 1);
    ini_set("error_log", "php-error.log");
    error_reporting(E_ALL);
}
require "Domrobot.php";
require "config.inc.php";

// globals
$domrobot = new INWX\Domrobot($addr); 

// GET variables from URL
if (isset($_GET['ip4addr'])) {
	$ip4addr = filter_input(INPUT_GET, 'ip4addr', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    if (!$ip4addr) abortOnError(400, 'Invalid IPv4');
}
if (isset($_GET['ip6addr'])) {
	$ip6addr = filter_input(INPUT_GET, 'ip6addr', FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    if (!$ip6addr) abortOnError(400, 'Invalid IPv6');
}


// get username and password from $_SERVER
if (isset($_GET['user'])) {
    $dynDomainUser = filter_var($_GET['user'], FILTER_SANITIZE_STRING);
} else {
    abortOnError(400, 'No Username provided');
}
if (isset($_GET['password'])) {
    $dynDomainPass = filter_var($_GET['password'], FILTER_SANITIZE_STRING);
} else {
    abortOnError(400, 'No password provided');
}

// Main
try {
	if ($dynDomainUser === $usr && $dynDomainPass === $pass) {
		foreach ($domains as $domain) {
			updateDomain($inwxUser, $inwxPassword, $ip4addr, $ip6addr, $domain);
		}
	} else {
		abortOnError(403, 'wrong username or password.');      
	}   
} catch (Exception $e) {
	error_log($e->getMessage(), 0, 'php-error.log');
}

/**
 * @brief updates the domain name
 * @param String $inwxUser
 * @param String $inwxPassword
 * @param String $ip4addr [optional]
 * @param String $ip6addr [optional]
 * @param String $domain
 */
function updateDomain($inwxUser, $inwxPassword, $ip4addr, $ip6addr, $domain) {
	global $domrobot;
    // login
    $res = connect($inwxUser, $inwxPassword);  	
    // update ipv4 if requested
    if (isset($ip4addr)) {
        $recordId = requestRecordId($res, $domain, 'ipv4', $ip4addr);
    }
    // update ipv6 if requested
    if (isset($ip6addr)) {

        $recordId = requestRecordId($res, $domain, 'ipv6', $ip6addr);
    }
    // done, logout
    $domrobot->logout();  
}

/**
 * Gets the unique Nameserver-Record ID
 *
 * @param array $res Response from login
 * @param String $domain
 * @param String $type which IP type to query, either ipv4 or ipv6
 * @return int ID unique ID of Nameserver-Records
 */
function requestRecordId($res, $domain, $type, $ipAddr) {
	global $domrobot;
	
	//domain splitting
	$domain_exploded = explode(".", $domain);
	$domain_exploded_length = count($domain_exploded);
	$domain = $domain_exploded[$domain_exploded_length - 2] . "." . $domain_exploded[$domain_exploded_length - 1];
	unset($domain_exploded[$domain_exploded_length - 1]);
	unset($domain_exploded[$domain_exploded_length - 2]);
	$name= implode(".", $domain_exploded);

	//do request
	if ($res['code']==1000) {
		$obj = "nameserver";
		$meth = "info";
		$params = array();
		$params['domain'] = $domain;
		
		$res = $domrobot->call($obj,$meth,$params);
		
		if ($type == "ipv4"){
			foreach ($res['resData']['record'] as $record) {
				if ($record['type'] == 'A') {
					$recordId = $record['id'];
					updateRecord($res, $recordId, $ipAddr);
				}
			}
		} else if ($type == "ipv6") {
			foreach ($res['resData']['record'] as $record) {
				if ($record['type'] == 'AAAA') {
					$recordId = $record['id'];
					updateRecord($res, $recordId, $ipAddr);
				}
			}
		} else 
			throw new Exception('unknown IP type');
		
		return;
	} else {
		throw new Exception('connection error occurred');
	}
}

/**
 * Set IP-Address in according Nameserver-Record
 *
 * @param array $res Response from login
 * @param int $recordId unique ID of Nameserver-Records
 * @param String $ipAddr contains IP-Address
 */
function updateRecord($res, $recordId, $ipAddr) {
	global $domrobot;
	
	// do update
	if ($res['code']==1000) {
		$obj = "nameserver";
		$meth = "updateRecord";
		$params = array();
		$params['id'] = $recordId;
		$params['content'] = $ipAddr;
		$res = $domrobot->call($obj,$meth,$params);
	} else {
		throw new Exception('connection error occurred');
	}
}

/**
* Log into inwx API
*/
function connect($user, $password) {	
	global $domrobot;
	$domrobot->setDebug(false);
	$domrobot->setLanguage('en');
	return $domrobot->login($user,$password);
}

/**
* Send Header to indicate some error so FritzBox can detect failure
*/
function abortOnError($httpResponse, $message) {
    // backwards compatibility for php<5.4  
    if (!function_exists('http_response_code')) {
        function http_response_code($response) {
            header('none', false, $response);
        }
    }    

    http_response_code($httpResponse);
    error_log($message, 0, 'php-error.log');
    die();
}
?>
