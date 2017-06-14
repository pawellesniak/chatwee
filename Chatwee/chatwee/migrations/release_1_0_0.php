<?php
/* Package phpBB Extension - Acme Demo
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Chatwee\chatwee\migrations;

/*
* @ignore
*/
if (!defined("IN_PHPBB")) {
    exit;
}

class release_1_0_0 extends \phpbb\db\migration\migration
{
    public function update_data()
    {
        return array(

            array("config.add", array("chatwee_enable", 1)),
            array("config.add", array("chatwee_code", "")),
            array("config.add", array("single_sign_on_enable", 0)),
            array("config.add", array("chatwee_version", 2)),
            array("config.add", array("single_sign_on_chat_id", "")),
            array("config.add", array("single_sign_on_key_api", "")),
            array("config.add", array("chatwee_global_moderators_as_admin", 0)),

            array("module.add", array(
                "acp",
                "ACP_CAT_DOT_MODS",
                "ACP_CHATWEE_MOD",
            )),

            array("module.add", array(
                "acp",
                "ACP_CHATWEE_MOD",
                array(
                    "module_basename" => "\Chatwee\chatwee\acp\chatwee_module",
                    "modes" => array("settings"),
                ),
            )),
        );
    }

    public function update_schema()
    {
        return array(
            "add_tables" => array(
                $this->table_prefix . "chatwee_users" => array(
                    "COLUMNS"       => array(
                        "user_id"               => array("UINT", 0),
                        "chatwee_id"            => array("VCHAR:80", ""),
                        "set_as_chat_admin"     => array("UINT", 0),
                    ),
                    "PRIMARY_KEY"   => "user_id",
                ),

                $this->table_prefix . "chatwee_logs" => array(
                    "COLUMNS"       => array(
                        "error_id"               => array('UINT', NULL, 'auto_increment'),
                        "error_message"          => array('MTEXT', ''),
                    ),
                    "PRIMARY_KEY"   => "error_id",
                ),                
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            "drop_tables" => array(
                $this->table_prefix . "chatwee_users",
                $this->table_prefix . "chatwee_logs",
            ),
        );
    }

}
