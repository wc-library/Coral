<?php
/*
 * *************************************************************************************************************************
 * * CORAL Usage Statistics Reporting Module v. 1.9
 * *
 * * Copyright (c) 2010 University of Notre Dame
 * *
 * * This file is part of CORAL.
 * *
 * * CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * *
 * * CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License along with CORAL. If not, see <http://www.gnu.org/licenses/>.
 * *
 * *************************************************************************************************************************
 */

class Config {
	const CONFIG_FILE_PATH = 'common/configuration.ini';
	const ERR_FILE_NOT_READABLE = 10050;
	const ERR_VARIABLES_MISSING = 10051;
	const ERR_NOT_INSTALLING = 10052;
	const ERR_NO_SUCH_MODULE = 10053;

	protected static $database;
	protected static $module_settings = [];
	protected static $bInit = null;

	private static function init(){
		if (!isset(self::$bInit)){
			if (!is_readable(self::CONFIG_FILE_PATH))
				throw new Exception(_("Config file not found or not readable"), self::ERR_FILE_NOT_READABLE);
			$data = parse_ini_file(self::CONFIG_FILE_PATH, true);
			self::$module_settings = $data;
			self::$bInit = 'y';
		}
	}

	public static function dbInfo($db_variable)
	{
		self::init();
		if (empty(self::$module_settings["database"]))
			throw new OutOfRangeException(_("Database settings are missing from the config file"), self::ERR_VARIABLES_MISSING);

		$possible_values = ['host', 'username', 'password', 'name'];
		if (in_array($db_variable, $possible_values))
		{
			if (!empty(self::$module_settings["database"][$db_variable]))
				return self::$module_settings["database"][$db_variable];
			else
				throw new OutOfRangeException(_("Database setting empty in config"), self::ERR_VARIABLES_MISSING);
		}
		else
		{
			throw new OutOfRangeException(_("Invalid database setting requested"), self::ERR_VARIABLES_MISSING);
		}
	}

	public static function getSettingsFor($module_name) {
		self::init();
		if (!empty(self::$module_settings[$module_name]))
		{
			return self::$module_settings[$module_name];
		}
		else
		{
			throw new OutOfBoundsException("No settings exist for '$module_name'.", self::ERR_VARIABLES_MISSING);
		}
	}

	public static function loadTemporaryDBSettings($database_settings) {
		if (!isset(self::$module_settings["database"]))
			self::$module_settings["database"] = [];
		self::$module_settings["database"] = array_merge(self::$module_settings["database"], $database_settings);
		self::$bInit = 'y';
	}

	public static function getInstallationVersion()
	{
		self::init();
		if (isset(self::$module_settings["installation_details"]) && isset(self::$module_settings["installation_details"]["version"]))
		{
			return self::$module_settings["installation_details"]["version"];
		}
		else
		{
			return false;
		}
	}

	public static function getInstalledModules()
	{
		self::init();
		return array_keys(array_filter(self::$module_settings, function($item){
			return in_array("installed", array_keys($item)) ? $item["installed"] == "Y" : false;
		}));
	}
	public static function getEnabledModules()
	{
		self::init();
		return array_keys(array_filter(self::$module_settings, function($item){
			return in_array("enabled", array_keys($item)) ? $item["enabled"] == "Y" : false;
		}));
	}
}
