<?php

/**
 * @copyright (c) 2017 Chatwee Team
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */
namespace Chatwee\chatwee\event;

/*
 * @ignore
 */
if (!defined("IN_PHPBB")) {
    exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Exception;

class ChatweeV1_Configuration
{
    private static $_chatId = null;

    private static $_clientKey = null;

    public static function setChatId($chatId) {
        self::$_chatId = $chatId;
    }

    public static function setClientKey($clientKey) {
        self::$_clientKey = $clientKey;
    }

    public static function getChatId() {
        return self::$_chatId;
    }

    public static function getClientKey() {
        return self::$_clientKey;
    }

    public static function isConfigurationSet() {
        return self::$_chatId !== null && self::$_clientKey !== null;
    }
}

class ChatweeV1_HttpClient
{
    const API_URL = "http://chatwee-api.com/api/";

    private $response;

    private $responseStatus;

    private $responseObject;

    public function get($method, $params) {

        $params["chatId"] = ChatweeV1_Configuration::getChatId();
        $params["clientKey"] = ChatweeV1_Configuration::getClientKey();

        $serializedParams = self::_serializeParams($params);

        $url = self::API_URL . $method . "?" . $serializedParams;

        self::_call("GET", $url);
    }

    private function _serializeParams($params) {
        if(!is_array($params) || count($params) == 0) {
            return "";
        }

        $result = "";

        foreach($params as $key => $value) {
            $result .= ($key . "=" . urlencode($value) . "&");
        }

        $result = substr_replace($result, "", -1);
        return $result;
    }

    public function _call($method, $url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, Array (
            "Accept: application/xml",
            "Content-Type: application/xml",
            "User-Agent: Chatwee PHP Library "
        ));

        $this->response = curl_exec($curl);

        $this->responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $this->responseObject = $this->response ? json_decode($this->response) : null;

        if($this->responseStatus != 200) {
            $responseError = $this->responseObject ? $this->responseObject->errorMessage : "Chatwee PHP Library unknown error: " . $this->responseStatus;
            $responseError .= " (" . $url . ")";
            throw new Exception($responseError);
        }
    }

    public function getResponse() {
        return $this->response;
    }

    public function getResponseObject() {
        return $this->responseObject;
    }

    public function getResponseStatus() {
        return $this->responseStatus;
    }
}

class ChatweeV1_Session
{

    public static function getSessionId() {
        global $request;      
        return isSet($_COOKIE["chch-SI"]) ? $request->variable("chch-SI", "", false, \phpbb\request\request_interface::COOKIE) : null;
    }

    public static function setSessionId($sessionId) {
        global $request;
        
        $hostChunks = explode(".", $request->server("HTTP_HOST", ""));

        $hostChunks = array_slice($hostChunks, -2);

        $cookieDomain = "." . implode(".", $hostChunks);

        setcookie("chch-SI", $sessionId, time() + 2592000, "/", $cookieDomain);
    }

    public static function clearSessionId() {
        global $request;
        
        $hostChunks = explode(".", $request->server("HTTP_HOST", ""));

        $hostChunks = array_slice($hostChunks, -2);

        $domain = "." . implode(".", $hostChunks);

        setcookie("chch-SI", "", time() - 1, "/", $domain);

        setcookie("chch-PSI", "", time() - 1, "/", $domain);
    
    }

    public static function getPreviousSessionId() {
        global $request;      
        return isSet($_COOKIE["chch-PSI"]) ? $request->variable("chch-PSI", "", false, \phpbb\request\request_interface::COOKIE) : null;
    }
}

class ChatweeV1_User {

    public static function login($parameters) {
        if(self::isLogged() === true) {
            self::logout();
        }

        if(isSet($parameters["login"]) === false) {
            return false;
        }

        $requestParameters = Array(
            "login" => $parameters["login"],
            "isMobile" => isSet($parameters["isMobile"]) === true ? ($parameters["isMobile"] === true ? 1 : 0) : (ChatweeV1_Utils::isMobileDevice() === true ? 1 : 0),
            "ipAddress" => isSet($parameters["ipAddress"]) === true ? $parameters["ipAddress"] : ChatweeV1_Utils::getUserIp(),
            "isAdmin" => isSet($parameters["isAdmin"]) === true ? ($parameters["isAdmin"] === true ? 1 : 0) : 0,
            "avatar" => isSet($parameters["avatar"]) === true ? htmlentities($parameters["avatar"]) : "",
            "previousSessionId" => isSet($parameters["previousSessionId"]) === true ? $parameters["previousSessionId"] : ChatweeV1_Session::getPreviousSessionId(),
        );

        $httpClient = new ChatweeV1_HttpClient();
        $httpClient->get("remotelogin", $requestParameters);

        $response = $httpClient->getResponseObject();
        $sessionId = $response->sessionId;

        ChatweeV1_Session::setSessionId($sessionId);

        return $sessionId;
    }

    public static function logout() {
        if(self::isLogged() === false) {
            return false;
        }
    
        $requestParameters = Array(
            "sessionId" => ChatweeV1_Session::getSessionId()
        );

        $httpClient = new ChatweeV1_HttpClient();
        $httpClient->get("remotelogout", $requestParameters);

        $response = $httpClient->getResponseObject();

        ChatweeV1_Session::clearSessionId();
    }

    public static function isLogged() {
        return ChatweeV1_Session::getSessionId() !== null;
    }
}

class ChatweeV1_Utils {

    public static function getUserIp() {
        global $request;

        $httpClientIp = $request->server("HTTP_CLIENT_IP", "");
        $httpXForwardedFor = $request->server("HTTP_X_FORWARDED_FOR", "");

        if (!empty($httpClientIp)) {
            $ip = $httpClientIp;
        } elseif (!empty($httpXForwardedFor)) {
            $ip = $httpXForwardedFor;
        } else {
            $ip = $request->server("REMOTE_ADDR", "");
        }

        return $ip;
    }

    public static function isMobileDevice() {
        global $request;
        $user_agent = strtolower ( $request->server("HTTP_USER_AGENT", "") );

        if (preg_match("/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent)) {
            return true;
        } else if (preg_match("/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent)) {
            return true;
        }

        return false;
    }
}

class ChatweeV2_Configuration
{
    private static $_chatId = null;

    private static $_clientKey = null;

    public static function setChatId($chatId) {
        self::$_chatId = $chatId;
    }

    public static function setClientKey($clientKey) {
        self::$_clientKey = $clientKey;
    }

    public static function getChatId() {
        return self::$_chatId;
    }

    public static function getClientKey() {
        return self::$_clientKey;
    }

    public static function isConfigurationSet() {
        return self::$_chatId !== null && self::$_clientKey !== null;
    }
}

class ChatweeV2_HttpClient
{
    const API_URL = "http://chatwee-api.com/v2/";

    private $response;

    private $responseStatus;

    private $responseObject;

    public function get($method, $parameters) {
        if(ChatweeV2_Configuration::isConfigurationSet() === false) {
            throw new Exception("The client credentials are not set");
        }

        $parameters["chatId"] = ChatweeV2_Configuration::getChatId();
        $parameters["clientKey"] = ChatweeV2_Configuration::getClientKey();

        $serializedParameters = self::_serializeParameters($parameters);
        $url = self::API_URL . $method . "?" . $serializedParameters;

        self::call("GET", $url);
    }

    private function call($method, $url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, Array (
            "Accept: application/xml",
            "Content-Type: application/xml",
            "User-Agent: ChatweeV2 PHP SDK 1.01"
        ));

        $this->response = curl_exec($curl);
        $this->responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $this->responseObject = $this->response ? json_decode($this->response) : null;
        if($this->responseStatus != 200) {
            $responseError = $this->responseObject ? $this->responseObject->errorMessage : "ChatweeV2 PHP SDK unknown error: " . $this->responseStatus;
            $responseError .= " (" . $url . ")";
            throw new Exception($responseError);
        }
    }

    private function _serializeParameters($parameters) {
        if(!is_array($parameters) || count($parameters) == 0) {
            return "";
        }

        $result = "";
        foreach($parameters as $key => $value) {
            $result .= ($key . "=" . ($value ? urlencode(htmlentities($value)) : $value) . "&");
        }

        $result = substr_replace($result, "", -1);
        return $result;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getResponseObject() {
        return $this->responseObject;
    }

    public function getResponseStatus() {
        return $this->responseStatus;
    }
}

class ChatweeV2_Session
{
    private static function getCookieKey() {
        if(ChatweeV2_Configuration::isConfigurationSet() === false) {
            throw new Exception("The client credentials are not set");
        }
        return "chatwee-SID-" . ChatweeV2_Configuration::getChatId();
    }

    public static function getSessionId() {
        global $request;
        $cookieKey = self::getCookieKey();
        return isSet($_COOKIE[$cookieKey]) ? $request->variable($cookieKey, "", false, \phpbb\request\request_interface::COOKIE) : null;
    }

    public static function setSessionId($sessionId) {
        global $request;

        $hostChunks = explode(".", $request->server("HTTP_HOST", ""));

        $hostChunks = array_slice($hostChunks, -2);

        $cookieDomain = "." . implode(".", $hostChunks);

        setcookie(self::getCookieKey(), $sessionId, time() + 2592000, "/", $cookieDomain);
    }

    public static function clearSessionId() {
        global $request;

        $hostChunks = explode(".", $request->server("HTTP_HOST", ""));

        $hostChunks = array_slice($hostChunks, -2);

        $domain = "." . implode(".", $hostChunks);

        setcookie(self::getCookieKey(), "", time() - 1, "/", $domain);
    }

    public static function isSessionSet() {
        return ChatweeV2_Session::getSessionId() !== null;
    }
}

class ChatweeV2_SsoManager {

    public static function registerUser($parameters) {
        if(isSet($parameters["login"]) === false) {
            throw new Exception("login parameter is required");
        }

        $userId = ChatweeV2_SsoUser::register(Array(
            "login" => $parameters["login"],
            "isAdmin" => isSet($parameters["isAdmin"]) === true ? $parameters["isAdmin"] : false,
            "avatar" => isSet($parameters["avatar"]) === true ? $parameters["avatar"] : ""
        ));

        return $userId;
    }

    public static function loginUser($parameters) {
        if(isSet($parameters["userId"]) === false) {
            throw new Exception("userId parameter is required");
        }
        if(self::isLogged() === true) {
            self::logoutUser();
        }

        $sessionId = ChatweeV2_SsoUser::login(Array(
            "userId" => $parameters["userId"]
        ));

        ChatweeV2_Session::setSessionId($sessionId);

        return $sessionId;
    }

    public static function logoutUser() {
        if(self::isLogged() === false) {
            return false;
        }
        $sessionId = ChatweeV2_Session::getSessionId();

        ChatweeV2_SsoUser::removeSession(Array(
            "sessionId" => $sessionId
        ));

        ChatweeV2_Session::clearSessionId();
    }

    public static function editUser($parameters) {
        if(isSet($parameters["login"]) === false) {
            throw new Exception("login parameter is required");
        }
        if(isSet($parameters["userId"]) === false) {
            throw new Exception("userId parameter is required");
        }

        $editParameters = Array(
            "userId" => $parameters["userId"],
            "login" => $parameters["login"],
            "avatar" => isSet($parameters["avatar"]) === true ? $parameters["avatar"] : ""
        );

        if(isSet($parameters["isAdmin"]) === true) {
            $editParameters["isAdmin"] = $parameters["isAdmin"];
        }

        ChatweeV2_SsoUser::edit($editParameters);
    }

    private static function isLogged() {
        return ChatweeV2_Session::isSessionSet();
    }
}

class ChatweeV2_SsoUser {

    public static function register($parameters) {
        $requestParameters = Array(
            "login" => $parameters["login"],
            "isAdmin" => $parameters["isAdmin"] === true ? 1 : 0,
            "avatar" => $parameters["avatar"]
        );

        $httpClient = new ChatweeV2_HttpClient();
        $httpClient->get("sso-user/register", $requestParameters);

        $userId = $httpClient->getResponseObject();

        return $userId;
    }

    public static function login($parameters) {
        $requestParameters = Array(
            "userId" => $parameters["userId"]
        );

        $httpClient = new ChatweeV2_HttpClient();
        $httpClient->get("sso-user/login", $requestParameters);

        $sessionId = $httpClient->getResponseObject();

        return $sessionId;
    }

    public static function removeSession($parameters) {
        $requestParameters = Array(
            "sessionId" => $parameters["sessionId"]
        );

        $httpClient = new ChatweeV2_HttpClient();
        $httpClient->get("sso-user/remove-session", $requestParameters);
    }

    public static function logout($parameters) {
        $requestParameters = Array(
            "userId" => $parameters["userId"]
        );

        $httpClient = new ChatweeV2_HttpClient();
        $httpClient->get("sso-user/logout", $requestParameters);
    }

    public static function edit($parameters) {
        $requestParameters = Array(
            "userId" => $parameters["userId"],
            "login" => $parameters["login"],
            "avatar" => $parameters["avatar"]
        );

        if(isSet($parameters["isAdmin"]) === true) {
            $requestParameters["isAdmin"] = $parameters["isAdmin"] === true ? 1 : 0;
        }

        $httpClient = new ChatweeV2_HttpClient();
        $httpClient->get("sso-user/edit", $requestParameters);
    }
}

class ChatweeV2_Utils {

    public static function getUserIp() {
        global $request;

        $httpClientIp = $request->server("HTTP_CLIENT_IP", "");
        $httpXForwardedFor = $request->server("HTTP_X_FORWARDED_FOR", "");

        if (!empty($httpClientIp)) {
            $ip = $httpClientIp;
        } elseif (!empty($httpXForwardedFor)) {
            $ip = $httpXForwardedFor;
        } else {
            $ip = $request->server("REMOTE_ADDR", "");
        }

        return $ip;
    }

    public static function isMobileDevice() {
        global $request;

        $user_agent = strtolower ($request->server("HTTP_USER_AGENT", ""));

        if (preg_match("/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent)) {
            return true;
        } else if (preg_match("/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent)) {
            return true;
        }
        return false;
    }
}

class listener implements EventSubscriberInterface
{
    /**
     * @var \phpbb\config\config
     */
    protected $config;
    /**
     * @var \phpbb\template\template
     */
    protected $template;
    /**
     * @var \phpbb\auth\auth
     */
    protected $auth;
    /**
     * @var \phpbb\db\driver\driver_interface
     */
    protected $db;
    /**
     * @var \phpbb\user
     */
    protected $user;
    /**
     * @var \phpbb\request\request_interface
     */
    protected $request;

    /**
     * @param \phpbb\config\config              $config
     * @param \phpbb\request\request_interface  $request
     * @param \phpbb\template\template          $template
     * @param \phpbb\auth\auth                  $auth     Auth object
     * @param \phpbb\db\driver\driver_interface $db       Database object
     * @param \phpbb\user                       $user     User object
     */
    public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\user $user)
    {
        $this->config = $config;
		$this->request = $request;
        $this->template = $template;
        $this->auth = $auth;
        $this->db = $db;
        $this->user = $user;
    }

    public static function getSubscribedEvents()
    {
        return array(
            "core.page_footer" => "assign_to_template",
            "core.session_kill_after" => "user_logout",
            "core.login_box_redirect" => "user_login",
            "core.avatar_driver_upload_delete_before" => "delete_avatar",
            "core.avatar_driver_upload_move_file_before" => "acp_edit_avatar",
            "core.update_username" => "edit_name",
            "core.ucp_profile_avatar_sql" => "ucp_edit_avatar",
            "core.group_add_user_after" => "update_user_group",
            "core.group_delete_user_after" => "update_user_group"       
        );
    }

    public function user_logout() {

        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];

        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            
            if ($sso_enable == 1) {
                switch ($chatwee_version) {
                    case 1:
                        $this->v1_chatwee_sign_out();
                        break;
                    case 2:
                        $this->v2_chatwee_sign_out();
                        break;
                    default:
                        break;
                }
            }
        }
    }
    
    public function user_login() {
        
        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];

        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            $user = $this->user->data;
            
            if ($sso_enable == 1) {
                switch ($chatwee_version) {
                    case 1:
                        $this->v1_chatwee_sign_in($user);
                        break;
                    case 2:
                        $this->v2_chatwee_sign_in($user);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function assign_to_template($event) {

        if (strlen($this->config["chatwee_code"]) && $this->config["chatwee_enable"]) {
            $this->template->assign_vars(array(
                'CHATWEE_CODE'          => htmlspecialchars_decode($this->config["chatwee_code"]),
            ));
        }
    }

    public function fetch_user_avatar($user) {
        
        $user_id = $user["user_id"];

        $sql_array = array(
            "user_id" => $user_id
        );
        $sql = "SELECT user_avatar 
                FROM " . USERS_TABLE . " 
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $row["user_avatar"] ? $avatar = $row["user_avatar"] : $avatar = false;
        $avatar ? $avatar = generate_board_url() . "/download/file.php?avatar=" . $avatar : "";
       
        return $avatar;
    }

    public function set_chatwee_credentials($chatwee_version) {
        
        $chatId = $this->config["single_sign_on_chat_id"];
        $clientKey = $this->config["single_sign_on_key_api"];
        
        switch ($chatwee_version) {
            case 1:
                ChatweeV1_Configuration::setChatId($chatId);
                ChatweeV1_Configuration::setClientKey($clientKey);
                break;
            case 2:
                ChatweeV2_Configuration::setChatId($chatId);
                ChatweeV2_Configuration::setClientKey($clientKey);
                break;
            default:
                break;
        }
    }

    public function v1_chatwee_sign_in($user) {       
        
        $this->set_chatwee_credentials(1);
        
        $parameters = Array(
            "login" => $user["username"],
            "avatar" =>  $this->fetch_user_avatar($user), 
            "isAdmin" => $this->isChatweeAdmin(),
        );
        
        try {
            ChatweeV1_User::login($parameters);
        } catch(Exception $exception) {
            $this->insert_chatwee_error_log($exception->getMessage());
        } 
    }

    public function v1_chatwee_sign_out() {
        
        $this->set_chatwee_credentials(1);
        
        try {
            ChatweeV1_User::logout();
        } catch(Exception $exception) {
            $this->insert_chatwee_error_log($exception->getMessage());
        }
    }

    public function isChatweeAdmin($user = null) {
        
        if (!$user) {
            $user = $this->user->data;
        }
        
        $this->auth->acl($user);
        
        if ($this->auth->acl_get("a_")) {
            return true;
        } elseif ($this->config["chatwee_global_moderators_as_admin"] && ($this->auth->acl_get("m_"))) {
            return true;
        }
        
        return false;
    }

    public function fetch_user_chatwee_id($user) {
        
        global $table_prefix;
        $table = $table_prefix . "chatwee_users";
        $user_id = $user["user_id"];

        $sql_array = array(
            "user_id" => $user_id
        );
        $sql = "SELECT chatwee_id 
                FROM $table  
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $row["chatwee_id"] ? $chatwee_id = $row["chatwee_id"] : $chatwee_id = false;
        
        return $chatwee_id;
    }

    public function insert_user_chatwee_id($user, $chatwee_id) {
        
        global $table_prefix;
        $table = $table_prefix . "chatwee_users";
        $user_id = $user["user_id"];

        $sql_arr = array(
            "user_id"    => $user_id,
            "chatwee_id" => $chatwee_id,
        );

        $sql = "INSERT INTO " . $table . " " . $this->db->sql_build_array("INSERT", $sql_arr);
        $this->db->sql_query($sql);
    }

    public function update_set_as_chat_admin($user, $value) {
        
        global $table_prefix;
        $table = $table_prefix . "chatwee_users";
        $value === true ? $value = 1 : $value = 0;
        $user_id = $user["user_id"];
        
        $sql_arr = array(
            "set_as_chat_admin"    => $value,
        );

        $sql = "UPDATE " . $table . " SET " . $this->db->sql_build_array("UPDATE", $sql_arr) . " WHERE user_id = " . $user_id;
        $this->db->sql_query($sql);
    }

    public function check_if_set_as_chat_admin($user) {
        
        global $table_prefix;
        $table = $table_prefix . "chatwee_users";
        $user_id = $user["user_id"];

        $sql_array = array(
            "user_id" => $user_id
        );
        $sql = "SELECT set_as_chat_admin 
                FROM $table  
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $row["set_as_chat_admin"] == 1 ? $result = true : $result = false;
        
        return $result;
    }

    public function v2_chatwee_sign_in($user) {
        
        $chatwee_id = $this->fetch_user_chatwee_id($user);
        
        if (!$chatwee_id) {
            $chatwee_id = $this->obtain_chatwee_id($user);
        }
        
        $this->set_chatwee_credentials(2);

        if ($this->isChatweeAdmin() === true && $this->check_if_set_as_chat_admin($user) === false) {
            try {
                ChatweeV2_SsoUser::edit(Array(
                    "userId" => $this->fetch_user_chatwee_id($user),
                    "login"  => $user["username"],
                    "avatar" => $this->fetch_user_avatar($user),
                    "isAdmin" => true
                ));
                $this->update_set_as_chat_admin($user, true);
            } catch(Exception $exception) {
                $this->insert_chatwee_error_log($exception->getMessage());
            }  
        } elseif ($this->isChatweeAdmin() === false && $this->check_if_set_as_chat_admin($user) === true) {
            try {
                ChatweeV2_SsoUser::edit(Array(
                    "userId" => $this->fetch_user_chatwee_id($user),
                    "login"  => $user["username"],
                    "avatar" => $this->fetch_user_avatar($user),
                    "isAdmin" => false
                ));
                $this->update_set_as_chat_admin($user, false);
            } catch(Exception $exception) {
                $this->insert_chatwee_error_log($exception->getMessage());
            }  
        }  
        
        try {
            $sessionId = ChatweeV2_SsoManager::loginUser(Array(
                "userId" => $chatwee_id
            )); 
        } catch(Exception $exception) {
            $this->insert_chatwee_error_log($exception->getMessage());
        }       
    }

    public function obtain_chatwee_id($user) {

        $this->set_chatwee_credentials(2);
        try {
            $userId = ChatweeV2_SsoManager::registerUser(Array(
                "login" => $user["username"],
                "avatar" => $this->fetch_user_avatar($user),
                "isAdmin" => $this->isChatweeAdmin()
            ));
            
            $this->insert_user_chatwee_id($user, $userId);
            $this->update_set_as_chat_admin($user, $this->isChatweeAdmin());
        } catch(Exception $exception) {
            $this->insert_chatwee_error_log($exception->getMessage());
        }
        return $userId;
    }

    public function v2_chatwee_sign_out() {
        
        $this->set_chatwee_credentials(2);
        
        try {
            ChatweeV2_SsoManager::logoutUser();
        } catch(Exception $exception) {
            $this->insert_chatwee_error_log($exception->getMessage());
        }
    }

    public function edit_name($event) {
        
        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];

        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            
            if ($sso_enable == 1 && $chatwee_version == 2) {
                
                $user = $this->fetch_user_data_from_user_name($event["new_name"]);

                if (!$user) {
                    return false;
                } 
                
                $this->set_chatwee_credentials(2);
                try {
                    ChatweeV2_SsoUser::edit(Array(
                        "userId" => $this->fetch_user_chatwee_id_from_user_name($event["new_name"]),
                        "login"  => $event["new_name"],
                        "avatar" => $this->fetch_user_avatar($user),
                        "isAdmin" => $this->isChatweeAdmin($user)
                    ));
                    $this->update_set_as_chat_admin($user, $this->isChatweeAdmin($user));
                } catch(Exception $exception) {
                    $this->insert_chatwee_error_log($exception->getMessage());
                }  
            }
        } 
    }

    public function fetch_user_chatwee_id_from_user_name($username) {
        
        global $table_prefix;
        $table = $table_prefix . "chatwee_users";

        $sql_array = array(
            "username" => $username
        );

        $sql = "SELECT chatwee_id 
                FROM " . USERS_TABLE . "                  
                as e INNER JOIN $table as c ON e.user_id=c.user_id
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row["chatwee_id"];
    }

    public function fetch_user_data_from_user_name($username) {

        $sql_array = array(
            "username" => $username
        );
        $sql = "SELECT *
                FROM " . USERS_TABLE . " 
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row;
    }

    public function fetch_user_data_from_user_id($user_id) {

        $sql_array = array(
            "user_id" => $user_id
        );
        $sql = "SELECT *
                FROM " . USERS_TABLE . " 
                WHERE " . $this->db->sql_build_array("SELECT", $sql_array);

        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row;
    }

    public function delete_avatar($event) {
        
        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];
        
        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            
            if ($sso_enable == 1 && $chatwee_version == 2) {
                
                $user_id = $event["row"]["id"];
                $user = $this->fetch_user_data_from_user_id($user_id);   

                $this->set_chatwee_credentials(2);
                try {
                    ChatweeV2_SsoUser::edit(Array(
                        "userId" => $this->fetch_user_chatwee_id($user),
                        "login"  => $user["username"],
                        "avatar" => "",
                        "isAdmin" => $this->isChatweeAdmin($user)
                    ));
                    $this->update_set_as_chat_admin($user, $this->isChatweeAdmin($user));
                } catch(Exception $exception) {
                    $this->insert_chatwee_error_log($exception->getMessage());
                }  
            }
        } 
    }

    public function ucp_edit_avatar($event) {
   
        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];
        
        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            
            if ($sso_enable == 1 && $chatwee_version == 2) {
                
                $user = $this->user->data;
                $avatar = generate_board_url() . "/download/file.php?avatar=" . $event["result"]["user_avatar"];        

                $this->set_chatwee_credentials(2);
                try {
                    ChatweeV2_SsoUser::edit(Array(
                        "userId" => $this->fetch_user_chatwee_id($user),
                        "login"  => $user["username"],
                        "avatar" => $avatar,
                        "isAdmin" => $this->isChatweeAdmin($user)
                    ));
                    $this->update_set_as_chat_admin($user, $this->isChatweeAdmin($user));
                } catch(Exception $exception) {
                    $this->insert_chatwee_error_log($exception->getMessage());
                }  
            }
        } 
    }

    public function acp_edit_avatar($event) {
        
        $config = $this->config;
        $chatwee_enable = $config["chatwee_enable"];
        
        if ($chatwee_enable == 1) {
            
            $sso_enable = $config["single_sign_on_enable"];
            $chatwee_version = $config["chatwee_version"];
            
            if ($sso_enable == 1 && $chatwee_version == 2) {
                
                $user_id = $event["row"]["id"];
                $user = $this->fetch_user_data_from_user_id($user_id);
                $extension = $event["filedata"]["extension"];
                $user_avatar = $user["user_id"] . "_" . time() . "." . $extension;
                $avatar = generate_board_url() . "/download/file.php?avatar=" . $user_avatar;        

                $this->set_chatwee_credentials(2);
                try {
                    ChatweeV2_SsoUser::edit(Array(
                        "userId" => $this->fetch_user_chatwee_id($user),
                        "login"  => $user["username"],
                        "avatar" => $avatar,
                        "isAdmin" => $this->isChatweeAdmin($user)
                    ));
                    $this->update_set_as_chat_admin($user, $this->isChatweeAdmin($user));
                } catch(Exception $exception) {
                    $this->insert_chatwee_error_log($exception->getMessage());
                }  
            }
        } 
    }

    public function update_user_group($event) {

        if ($event["group_id"] == 5 || $event["group_id"] == 4) {
            
            $config = $this->config;
            $chatwee_enable = $config["chatwee_enable"];
            
            if ($chatwee_enable == 1) {
                
                $sso_enable = $config["single_sign_on_enable"];
                $chatwee_version = $config["chatwee_version"];
                $global_moderators_as_admins = $config["chatwee_global_moderators_as_admin"];
                
                if ($sso_enable == 1 && $chatwee_version == 2) {
                    
                    $user_id_array = $event["user_id_ary"];
                    $user_id = $user_id_array[0];
                    $user = $this->fetch_user_data_from_user_id($user_id);        

                    $this->set_chatwee_credentials(2);
                    try {
                        ChatweeV2_SsoUser::edit(Array(
                            "userId" => $this->fetch_user_chatwee_id($user),
                            "login"  => $user["username"],
                            "avatar" => $this->fetch_user_avatar($user),
                            "isAdmin" => $this->isChatweeAdmin($user)
                        ));
                        $this->update_set_as_chat_admin($user, $this->isChatweeAdmin($user));
                    } catch(Exception $exception) {
                        $this->insert_chatwee_error_log($exception->getMessage());
                    }  
                }
            } 
        }
    } 

    public function insert_chatwee_error_log($error_message) {
        global $table_prefix;
        $table = $table_prefix . "chatwee_logs";

        $sql_arr = array(
            "error_id"    => null,
            "error_message" => date('l jS F Y h:i:s A') . ": " . $error_message,
        );

        $sql = "INSERT INTO " . $table . " " . $this->db->sql_build_array("INSERT", $sql_arr);
        $this->db->sql_query($sql);
    }

}
