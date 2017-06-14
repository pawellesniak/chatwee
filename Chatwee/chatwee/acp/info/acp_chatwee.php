<?php
/**
* @copyright (c) 2017 Chatwee Team
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*/
namespace Chatwee\chatwee\acp;
/**
 * @ignore
 */
if (!defined("IN_PHPBB")) {
    exit;
}

/**
 */
class acp_chatwee_info
{
    public function module()
    {
        return array(
            "filename" => "acp_chatwee",
            "title" => "ACP_CHATWEE_MOD",
            "version" => "2.0.0",
            "modes" => array(
                "configuration" => array("title" => "CHATWEE_CONFIG",        "auth" => "acl_chatwee_manage", "cat" => array("ACP_CHATWEE_MOD")),
            ),
        );
    }

    public function install()
    {
    }

    public function uninstall()
    {
    }
}
