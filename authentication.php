<?php
/**
 * authentication.php
 *
 * @package default
 */


/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AjaXplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://www.ajaxplorer.info/>.
 *
 * Description : Interface between AjaXplorer and external software. Handle with care!
 * Take care when using this file. It can't be included anywhere, as it's doing global scope pollution.
 *    Typically, this is used as glue code from your CMS frontend and AJXP code.
 *    This example file switches sessions (close CMS session, open AJXP session, modify AJXP's
 *    session value so the users actions are performed as if they were done locally by AJXP, and then
 *    reopen your CMS session).
 *    This is typically used by Wordpress as the plugin mechanism is hook based.
 *
 *    The idea is: this script is require()'d by the CMS script.
 */

//global $AJXP_GLUE_GLOBALS;
if (!isset($AJXP_GLUE_GLOBALS)) {
    $AJXP_GLUE_GLOBALS = array();
}
if (!isset($CURRENTPATH)) {
    $CURRENTPATH=realpath(dirname(__FILE__));
    $FRAMEWORK_PATH = realpath($CURRENTPATH."/../../");
}

include_once $FRAMEWORK_PATH."/base.conf.php";

if (!class_exists("SessionSwitcher")) {
    require_once "$CURRENTPATH/sessionSwitcher.php";
}


$pServ = AJXP_PluginsService::getInstance();

ConfService::init();

$confPlugin = ConfService::getInstance()->confPluginSoftLoad($pServ);

$pServ->loadPluginsRegistry("$FRAMEWORK_PATH/plugins", $confPlugin);

require_once "$FRAMEWORK_PATH/plugins/conf.".$confPlugin->getName()."/class.AJXP_SqlUser.php";
ConfService::start();

$plugInAction = $AJXP_GLUE_GLOBALS["plugInAction"];

$secret = $AJXP_GLUE_GLOBALS["secret"];

$confPlugs = ConfService::getConf("PLUGINS");
$authPlug = ConfService::getAuthDriverImpl();
if ($authPlug->getOption("SECRET") == "") {
    if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
        die("This file must be included and cannot be called directly");
    }

} else if ($secret != $authPlug->getOption("SECRET")) {
    $plugInAction = "WRONG_SECRET";
}
switch ($plugInAction) {
case 'login':
    $login = $AJXP_GLUE_GLOBALS["login"];
    $authdriver = ConfService::getAuthDriverImpl();
    $parametes = array();
    if (is_array($login)) {

        $newSession = new SessionSwitcher("AjaXplorer");

        if (AuthService::userExists($login["name"])) {
            //AuthService::deleteUser($login["name"]);
            AuthService::logUser($login["name"], $login["password"], true);
            $userObject = AuthService::getLoggedUser();
            $parameters = $userObject->personalRole->getDataArray()["PARAMETERS"]["AJXP_REPO_SCOPE_ALL"]["core.conf"];
            AuthService::disconnect();
            AuthService::deleteRole("AJXP_USR_/".$login["name"]);
        } else {
            AuthService::createUser($login["name"], $login["password"], false);
        }
        $result = AuthService::logUser($login["name"], $login["password"], true);
        if ($result == 1) {

            //Read out the Admint Entitlement option.
            $authdriver = ConfService::getAuthDriverImpl();
            $adminent = AJXP_VarsFilter::filter($authdriver->getOption("ADMIN_ENTITLEMENT"));
            $userObject = AuthService::getLoggedUser();

            //Remove all roles and rights from the user before reinit.
            $roles = $userObject->getRoles();
            unset($userObject->rights);
            unset($userObject->roles);
            try {
                $userObject->removeLock();
            } catch (Exception $e) {
            }
            $userObject->setAdmin(false);
            $userObject->setProfile("standard");
            $rObject = AuthService::getRole("ROOT_ROLE", true);
            $userObject->addRole($rObject);
            //Set up the roles from the entitlement.
            if (isset($login["roles"]) and is_array($login["roles"]) and count($login["roles"])>0) {
                foreach ($login["roles"] as $roleid) {
                    $rObject = AuthService::getRole($roleid, true);
                    $userObject->addRole($rObject);
                }
            }
            //Give admin rights to the to entitled users.
            if (isset($userObject->roles[$adminent])) {
                $userObject->setAdmin(true);
                $userObject->setGroupPath("/");
            }else $userObject->setAdmin(false);

            if ($userObject->isAdmin()) {
                AuthService::updateAdminRights($userObject);
            }else {
                AuthService::updateDefaultRights($userObject);
            }
            //Save the user to the DB.
            $userObject->personalRole->setParameterValue("core.conf","USER_DISPLAY_NAME",$login["fullname"]);
         	$userObject->personalRole->setParameterValue("core.conf","email",$login["email"]);
         	$userObject->personalRole->setParameterValue("core.conf","lang", $parameters["lang"]);
         	$userObject->personalRole->setParameterValue("core.conf","country", $parameters["country"]);
         	$userObject->personalRole->setParameterValue("core.conf","DEFAULT_START_REPOSITORY", $parameters["DEFAULT_START_REPOSITORY"]);
            $userObject->save("superuser");
            AuthService::updateUser($userObject);
            //Relogin with the updated user.
            AuthService::disconnect();
            $result = AuthService::logUser($login["name"], $login["password"], true);
        }

    }
    break;
case 'logout':
    $newSession = new SessionSwitcher("AjaXplorer");
    global $_SESSION;
    $_SESSION = array();
    $result = TRUE;
    $authdriver = ConfService::getAuthDriverImpl();
    global $LOGOUT_REDIRECT;
    $LOGOUT_REDIRECT = AJXP_VarsFilter::filter($authdriver->getOption("LOGOUT_URL"));
    break;
case 'back':
    $newSession = new SessionSwitcher("AjaXplorer");
    global $_SESSION;
    $_SESSION = array();
    $result = TRUE;
    break;
case 'addUser':
    $user = $AJXP_GLUE_GLOBALS["user"];
    if (is_array($user)) {
        $isAdmin = (isset($user["right"]) && $user["right"] == "admin");
        AuthService::createUser($user["name"], $user["password"], $isAdmin);
        $result = TRUE;
    }
    break;
case 'delUser':
    $userName = $AJXP_GLUE_GLOBALS["userName"];
    if (strlen($userName)) {
        AuthService::deleteUser($userName);
        $result = TRUE;
    }
    break;
case 'updateUser':
    $user = $AJXP_GLUE_GLOBALS["user"];
    if (is_array($user)) {
        if (AuthService::updatePassword($user["name"], $user["password"])) {
            $isAdmin =  (isset($user["right"]) && $user["right"] == "admin");
            $confDriver = ConfService::getConfStorageImpl();
            $user = $confDriver->createUserObject($user["name"]);
            $user->setAdmin($isAdmin);
            $user->save();
            $result = TRUE;
        }
        else $result = FALSE;
    }
    break;
case 'installDB':
    $user = $AJXP_GLUE_GLOBALS["user"]; $reset = $AJXP_GLUE_GLOBALS["reset"];
    $result = TRUE;
    break;
default:
    $result = FALSE;
}

$AJXP_GLUE_GLOBALS["result"] = $result;

?>
