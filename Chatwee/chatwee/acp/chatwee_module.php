<?php
/**
* @copyright (c) 2017 Chatwee Team
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*/
namespace Chatwee\chatwee\acp;

/*
* @ignore
*/
if (!defined("IN_PHPBB")) {
    exit;
}
/**
 */
class chatwee_module
{
    public $u_action;

    public function main($id, $mode)
    {
        global $user, $template, $request, $config;

        $user->add_lang("acp/common");
        $this->tpl_name = "acp_chatwee";
        $this->page_title = $user->lang("ACP_CHATWEE_MOD");

        $form_key = "acp_chatwee";
        add_form_key($form_key);

        if ($request->is_set_post("submit")) {
            if (!check_form_key($form_key)) {
                trigger_error("FORM_INVALID");
            }

            $chatwee_enable = $request->variable("chatwee_enable", 0);
            $config->set("chatwee_enable", $chatwee_enable);

            $chatwee_code = $request->variable("chatwee_code", "");
            $config->set("chatwee_code", $chatwee_code);

            $single_sign_on_enable = $request->variable("single_sign_on_enable", 0);
            $config->set("single_sign_on_enable", $single_sign_on_enable);

            $chatwee_version = $request->variable("chatwee_version", 2);
            $config->set("chatwee_version", $chatwee_version);

            $single_sign_on_chat_id = $request->variable("single_sign_on_chat_id", "");
            $config->set("single_sign_on_chat_id", $single_sign_on_chat_id);

            $single_sign_on_key_api = $request->variable("single_sign_on_key_api", "");
            $config->set("single_sign_on_key_api", $single_sign_on_key_api);

            $chatwee_global_moderators_as_admin = $request->variable("chatwee_global_moderators_as_admin", 0);
            $config->set("chatwee_global_moderators_as_admin", $chatwee_global_moderators_as_admin);

            trigger_error($user->lang("CONFIG_UPDATED") . adm_back_link($this->u_action));
        }

        $template->assign_vars(array(
            "S_CHATWEE_ENABLE" => isset($config["chatwee_enable"]) ? $config["chatwee_enable"] : false,
            "S_CHATWEE_CODE" => isset($config["chatwee_code"]) ? $config["chatwee_code"] : false,
            "S_SINGLE_SIGN_ON_ENABLE" => isset($config["single_sign_on_enable"]) ? $config["single_sign_on_enable"] : false,
            "S_CHATWEE_VERSION" => isset($config["chatwee_version"]) ? $config["chatwee_version"] : false,
            "S_SINGLE_SIGN_ON_CHAT_ID" => isset($config["single_sign_on_chat_id"]) ? $config["single_sign_on_chat_id"] : false,
            "S_SINGLE_SIGN_ON_KEY_API" => isset($config["single_sign_on_key_api"]) ? $config["single_sign_on_key_api"] : false,
            "S_GLOBAL_MODERATORS_AS_ADMIN" => isset($config["chatwee_global_moderators_as_admin"]) ? $config["chatwee_global_moderators_as_admin"] : false,

            "U_ACTION" => $this->u_action,
        ));
    }
}
