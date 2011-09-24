<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
 * This is the main configuration file for configuring the basic plugins the application
 * needs to run properly : an Authentication plugin, a Configuration plugin, and a Logger plugin.
 */

defined('AJXP_EXEC') or die( 'Access not allowed');
/********************************************
 * CUSTOM VARIABLES HOOK
 ********************************************/
/**
 * This is a sample "hard" hook, directly included. See directly the HookDemo class
 * for more explanation.
 */
//require_once AJXP_INSTALL_PATH."/plugins/hook.demo/class.HookDemo.php";
//AJXP_Controller::registerIncludeHook("vars.filter", array("HookDemo", "filterVars"));

/*********************************************************/
/* PLUGINS DEFINITIONS
/* Drivers will define how the application will work. For 
/* each type of operation, there are multiple implementation
/* possible. Check the content of the plugins folder.
/* CONF = users and repositories definition, 
/* AUTH = users authentification mechanism,
/* LOG = logs of the application.
/*
/* By default, the three are all based on files.
/*
/* ACTIVE_PLUGINS adds other type of plugins to the application.
/* If you are developping your own plugin, do not forget to declare
/* it here.
/*********************************************************/
$PLUGINS = array(
	"CONF_DRIVER" => array(
		"NAME"		=> "serial",
		"OPTIONS"	=> array(
			"REPOSITORIES_FILEPATH"	=> "AJXP_DATA_PATH/plugins/conf.serial/repo.ser",
			"ROLES_FILEPATH"		=> "AJXP_DATA_PATH/plugins/auth.serial/roles.ser",
			"USERS_DIRPATH"			=> "AJXP_DATA_PATH/plugins/auth.serial",
			/*"CUSTOM_DATA"			=> array(
					"email"	=> "Email", 
					"country" => "Country"
				)*/
			)
	),
    // EXAMPLE OF SQL CONF DRIVER
    /*
	"CONF_DRIVER" => array(
		"NAME"		=> "sql",
		"OPTIONS"	=> array(
			"SQL_DRIVER"	=> array(
                "driver" => "mysql",
                "host"   => "localhost",
                "database"   => "ajxp",
                "user"   => "root",
                "password"   => "",
            ),
			"CUSTOM_DATA"			=> array(
					"email"	=> "Email",
					"country" => "Country"
				)
			)
	),
    */
	"AUTH_DRIVER" => array(
		"NAME"		=> "serial",
		"OPTIONS"	=> array(
			"LOGIN_REDIRECT"		=> false,
			"USERS_FILEPATH"		=> "AJXP_DATA_PATH/plugins/auth.serial/users.ser",
			"AUTOCREATE_AJXPUSER" 	=> false, 
			"TRANSMIT_CLEAR_PASS"	=> false )
	),
    "LOG_DRIVER" => array(
         "NAME" => "text",
         "OPTIONS" => array(
             "LOG_PATH" => "AJXP_INSTALL_PATH/data/logs/",
             "LOG_FILE_NAME" => 'log_' . date('m-d-y') . '.txt',
             "LOG_CHMOD" => 0770
         )
    ),
	/*
	// ALTERNATE AUTH_DRIVER CONFIG SAMPLE
	"AUTH_DRIVER" => array(
		"NAME"		=> "remote",
		"OPTIONS"	=> array(
			"SLAVE_MODE"  => true,
			"USERS_FILEPATH" => "AJXP_INSTALL_PATH/data/plugins/auth.serial/users.ser",
			"MASTER_AUTH_FUNCTION" => "joomla_remote_auth",
			"MASTER_HOST"		=> "localhost",
			"MASTER_URI"		=> "/joomla/",
			"LOGIN_URL" => "/joomla/",  // The URL to redirect (or call) upon login (typically if one of your user type: http://yourserver/path/to/ajxp, he will get redirected to this url to login into your frontend
			"LOGOUT_URL" => "/joomla/",  // The URL to redirect upon login out (see above)
			"SECRET" => "myprivatesecret",// the same as the one you put in the WP plugin option.
			"TRANSMIT_CLEAR_PASS"   => true // Don't touch this. It's unsafe (and useless here) to transmit clear password.
		)
	),
	*/
);