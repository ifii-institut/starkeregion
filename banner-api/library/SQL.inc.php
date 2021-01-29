<?php
//require_once("Config.inc.php");

class SQL {
	public function sql_connect($hostname, $dbname, $username, $password){
		$conn_knd = new mysqli($hostname, $username, $password, $dbname);
		//Prüfen, ob Verbindung Erfolgreich
		if ($conn_knd->connect_error) {
			echo "Connection failed: " . $conn_knd->connect_error. "<br>";
			exit(1);
		} else {
			1;#echo "Verbindung zu Kunden-DB erfolgreich<br>";
		};
		return $conn_knd;
	}

	public function sql_fetch($dbh, $sql){
		$followingdata = "";
		try {
			$ret_val = $dbh->query($sql);
			$followingdata = $ret_val->fetch_array(MYSQLI_ASSOC);
			return $followingdata;
		} catch (Exception $e) {
			echo "sql_fetch() Fehler";
			return "";
		};
	}
	public function sql_fetchAll($dbh, $sql){
		$followingdata = "";
		try {
			$ret_val 		= $dbh->query($sql);
			$followingdata 	= $ret_val->fetch_all(MYSQLI_ASSOC);
			return $followingdata;
		} catch (Exception $e) {
			echo "sql_fetchAll Fehler";
			return "";
		};
	}
	public function sql_query($dbh, $sql){
		try {
			$dbh->query($sql);
		} catch (Exception $e) {
			echo "sql_query Fehler";
			return False;
		};
		return True;
	}
}
?>
