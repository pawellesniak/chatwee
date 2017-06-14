<?php
/**
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*/
namespace Chatwee\chatwee\acp;

/*
 * @ignore
 */
if (!defined("IN_PHPBB")) {
    exit;
}

class chatwee_info
{
    public function module()
    {
        return array(
            "filename" => "\Chatwee\chatwee\acp\chatwee_module",
            "title" => "ACP_CHATWEE_MOD",
            "modes" => array(
                "settings" => array("title" => "ACP_CHATWEE_MOD_SETTINGS",        "auth" => "ext_Chatwee/chatwee && acl_a_board", "cat" => array("ACP_CHATWEE_MOD")),
            ),
        );
    }
}
