<?php
/*
 * *************************************************************************************************************************
 * * CORAL Usage Statistics Reporting Module v. 1.0
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
	protected static $settings;
	protected static $bInit = null;

	private static function init(){
		if (!isset(self::$bInit)){
			if (!is_readable(self::CONFIG_FILE_PATH))
				throw new Exception(_("Config file not found or not readable"), self::ERR_FILE_NOT_READABLE);
			$data = parse_ini_file(self::CONFIG_FILE_PATH, true);

			if (isset($data['database']) && isset($data['settings'])) {
				self::$database = ( object ) $data['database'];
				self::$settings = ( object ) $data['settings'];
			}
			else {
				throw new OutOfRangeException(_("Certain settings are missing from the config file"), self::ERR_VARIABLES_MISSING);
			}

			self::$bInit = 'y';
		}
	}

	public static function dbInfo($detail)
	{
		self::init();
		switch ($detail) {
			case 'host':
				return self::$database->host;
				break;
			case 'username':
				return self::$database->username;
				break;
			case 'password':
				return self::$database->password;
				break;
			case 'name':
				return self::$database->name;
				break;
			default:
				var_export(self::$database, true);
				break;
		}
	}

	public static function getSettingsFor($module) {
		self::init();
		switch ($module) {
			case 'usage':
				# code...
				break;

			default:
				return self::$settings["common"];
				break;
		}
	}

	public static function loadTemporaryDBSettings($database_settings) {
		if (!INSTALLATION_IN_PROGRESS)
			throw new Exception("This method can only be used during installation.", self::ERR_NOT_INSTALLING);

		self::$database = $database_settings;
		self::$bInit = 'y';
	}
}
