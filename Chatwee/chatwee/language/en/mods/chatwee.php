<?php
/**
* chatwee.php [English].
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
* UMIL
*/
$lang = array_merge($lang, array(

    "INSTALL_CHATWEE_MOD" => "Install PHPBB Chat Extension by Chatwee",
    "INSTALL_CHATWEE_MOD_CONFIRM" => "Are you ready to install PHPBB Chat Extension by Chatwee?",
    "INSTALL_CHATWEE_MOD_WELCOME" => "Version 2.0.0",
    "INSTALL_CHATWEE_MOD_WELCOME_NOTE" => " Chatwee is a social chat software that doubles the engagement of online communities on your PHPBB3.<br/>
											<ul>
												<li>embed the Chatwee Live Chat Widget to your board</li>
												<li>enable Single Sign-On </li>
												<li>ability to assign moderator function to PHPBB3 admins and global moderators</li>
												
											</ul>",

    "CHATWEE_MOD" => "PHPBB Chat Extension by Chatwee",
    "CHATWEE_MOD_EXPLAIN" => "Install PHPBB Chat Extension by Chatwee with UMIL auto method.",
    "TITLE_EXPLAIN" =>"If you don't have a Chatwee account yet, please <a href='https://client.chatwee.com/register-form/v2' target='_blank'>sign up</a> absolutely for free.",
    "UNINSTALL_CHATWEE_MOD" => "Uninstall PHPBB Chat Extension by Chatwee",
    "UNINSTALL_CHATWEE_MOD_CONFIRM" => "Are you ready to uninstall PHPBB Chat Extension by Chatwee? All settings and data saved by this mod will be removed!",
    "SINGLE_SIGN_ON_SETTINGS" => "Single Sign-on settings",
    "UPDATE_CHATWEE_MOD" => "Update PHPBB Chat Extension by Chatwee",
    "UPDATE_CHATWEE_MOD_CONFIRM" => "Are you ready to update PHPBB Chat Extension by Chatwee?",

    "OTHER_SETTINGS" => "Other settings",
));
