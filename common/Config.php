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
	const ERR_FILE_NOT_READABLE = 50;
	const ERR_VARIABLES_MISSING = 51;
	const ERR_NOT_INSTALLING = 60;

	protected static $database;
	protected static $installed_modules = [];
	protected static $module_settings = [];
	protected static $bInit = null;

	private static function init(){
		if (!isset(self::$bInit)){
			if (!is_readable(self::CONFIG_FILE_PATH))
				throw new Exception(_("Config file not found or not readable"), self::ERR_FILE_NOT_READABLE);
			$data = parse_ini_file(self::CONFIG_FILE_PATH, true);

			if (isset($data['database']))
				self::$database = ( object ) $data['database'];
			else
				throw new OutOfRangeException(_("Database settings are missing from the config file"), self::ERR_VARIABLES_MISSING);

			if (isset($data['installed_modules']))
			{
				self::$installed_modules = $data['installed_modules'];
				self::$module_settings[$module] = array_filter($data, function($v, $k, $x){
					echo $v;
					echo $k;
					echo $x;
				});
			}

			self::$bInit = 'y';
		}
	}

	public static function dbInfo($db_variable)
	{
		self::init();
		$possible_values = ['host', 'username', 'password', 'name'];
		if (in_array($db_variable, $possible_values))
			return self::$database->$db_variable;
	}

	public static function getSettingsFor($module_name) {
		self::init();
		if (in_array($module_name, self::$installed_modules))
			return self::$module_settings[$module_name];
	}

	public static function loadTemporaryDBSettings($database_settings) {
		if (!INSTALLATION_IN_PROGRESS)
			throw new Exception("This method can only be used during installation.", self::ERR_NOT_INSTALLING);

		self::$database = $database_settings;
		self::$bInit = 'y';
	}
}
