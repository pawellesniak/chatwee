<?php
/**
* info_acp_chatwee.php [English].
*
* @copyright (c) 2017 Chatwee Team
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*/

/**
 * DO NOT CHANGE.
 */
if (!defined("IN_PHPBB")) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ « » “ ” …
//


/*
* mode: main
*/
$lang = array_merge($lang, array(
    "ACP_CHATWEE_MOD" => "phpBB Chat Extension by Chatwee",
));

/*
* mode: configuration
*/
$lang = array_merge($lang, array(
    "CHATWEE_CONFIG" => "Configuration",
    "CHATWEE_PLUGIN_SAVED" => "phpBB Chat Extension by Chatwee settings saved",
    "LOG_CHATWEE_UPDATED" => "phpBB Chat Extension by Chatwee settings updated",
    "ACP_CHATWEE_MOD_SETTINGS" => "Main settings",

    // General settings 
    "CHATWEE_GENERAL_SETTINGS" => "General settings",
    "CHATWEE_ENABLE" => "Enable Chatwee",
    "CHATWEE_ENABLE_EXPLAIN" => "Check this box if you want to display Chatwee on your board.",
    "CHATWEE_CODE" => "Chatwee installation code",
    "CHATWEE_CODE_EXPLAIN" => "Copy the Chatwee installation code from your <a href='https://client.chatwee.com/v2/dashboard' target='_blank'>Dashboard</a> and paste it into the box. If you don't have a Chatwee account yet, please <a href='https://client.chatwee.com/register-form/v2' target='_blank'>sign up</a> absolutely for free.",

    // Single Sign-on settings
    "CHATWEE_VERSION" => "Chatwee version",
    "CHATWEE_VERSION_EXPLAIN" => "Select which version of Chatwee you would like to use.",
    "SINGLE_SIGN_ON_SETTINGS" => "SSO settings",
    "SINGLE_SIGN_ON_ENABLE" => "Enable SSO",
    "SINGLE_SIGN_ON_ENABLE_EXPLAIN" => "Check this box if you want your users to log in via Single Sign-on.",
    "SINGLE_SIGN_ON_ALL_SUBDOMAINS" => "Login for all subdomains",
    "SINGLE_SIGN_ON_CHAT_ID" => "Chat ID",
    "SINGLE_SIGN_ON_CHAT_ID_EXPLAIN" => "Enter your Chat ID available in the <a href='https://client.chatwee.com/v2/customize#integration' target='_blank'>Integration</a> tab.",
    "SINGLE_SIGN_ON_KEY_API" => "API Key",
    "SINGLE_SIGN_ON_KEY_API_EXPLAIN" => "Enter your Client API Key available in the <a href='https://client.chatwee.com/v2/customize#integration' target='_blank'>Integration</a> tab.",
    "GLOBAL_MODERATORS_AS_ADMIN" => "Global Moderators as Chatwee Admins",
    "GLOBAL_MODERATORS_AS_ADMIN_EXPLAIN" => "Check this box if you want to assign admin powers to Global Moderators.",
));
