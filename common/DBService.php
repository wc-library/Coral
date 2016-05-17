<?php
/*
 * *************************************************************************************************************************
 * * CORAL Common Module v. 1.9
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

require_once "common/Object.php";
require_once "common/Config.php";
class DBService extends Object {
	const ERR_COULD_NOT_SELECT_DATABASE = 40;

	protected static $db = null;
	protected $error;

	public function __construct($dbname = null){
		mysqli_report(MYSQLI_REPORT_STRICT);
		if (!self::$db && !(self::$db = new mysqli(Config::dbInfo("host"), Config::dbInfo("username"), Config::dbInfo("password"))))
		{
			throw new RuntimeException("There was a problem with the database: " . self::$db->error);
		}
		else
		{
			// Allow db not to be selected if installation in progress
			if ($dbname !== false && INSTALLATION_IN_PROGRESS)
				self::selectDB($dbname ? $dbname : Config::dbInfo("name"));
		}
	}

	public function getError() {
		return self::$db->error;
	}

	public static function getDatabase(){
		return self::$db;
	}
	public static function selectDB($databaseName){
		if (!self::$db->select_db($databaseName)){
			throw new RuntimeException("Could not select database '$databaseName': " . self::$db->error, DBService::ERR_COULD_NOT_SELECT_DATABASE);
		}
	}

	public function processQuery($sql, $type = NULL) {
		if (strlen(trim("$sql"))===0) {
			throw new RuntimeException("Empty DB Query");
		}

		$query_start = microtime(true);
		$result = self::$db->query($sql);
		$query_end = microtime(true);
		//TODO: log out query time (cf. licensing module but use Common Utility)

		if (!$result)
		{
			throw new RuntimeException("There was a problem with the database: " . self::$db->error);
		}
		else if ($result instanceof mysqli_result)
		{
			return new DBResult($result);
		}
		else if ($result)
		{
			return self::$db->insert_id;
		}
		throw new LogicException("Congratulations, I thought it was impossible to get here. Please fix this code.");
	}

	public static function escapeString($str){
		return self::$db->real_escape_string($str);
	}
}
