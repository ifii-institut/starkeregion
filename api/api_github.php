<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
#ini_set("error_log", "C:\\xampp\\htdocs\\php-error.log");	// Logge Fehlermeldungen in eine lokale Datei
#error_reporting(E_WARNING); // Gib alle Warnungen aus
#error_reporting(E_ALL);
#error_log(message, 3, "errorlog.txt");

/*
Autor: 		Sebastian Enger
Email: 		s.enger@drei-i-m.de
Zweck: 		StarkeRegion Plattform: API-Schnittstelle zur Ausgabe aller Anzeigen und Installation neuer Einträge in die Haupt-Datenbank
Version: 	1.0.17b
Datum: 		17.11.2020 / 35e2ab0725537a12f4454bc1d8aedc7d
*/

# Normale Suche
#http://localhost/api/api_github.php?suche=Brandenburg&api_email=s.enger@company.de&api_key=56d9320489ffa79124bb8991fdbca1c7aa8722a0d14e94425229bd63543743b6157f3253c33fac8b18765a37464ce3492978a92e50865089999fd1b051b0a833

# Suche nach Preisen
#http://localhost/api/api_github.php?preis_von=10&preis_bis=20&api_email=s.enger@company.de&api_key=56d9320489ffa79124bb8991fdbca1c7aa8722a0d14e94425229bd63543743b6157f3253c33fac8b18765a37464ce3492978a92e50865089999fd1b051b0a833

# Ausgabe von 5 Einträgen
#http://localhost/api/api_github.php?api_email=s.enger@company.de&api_key=56d9320489ffa79124bb8991fdbca1c7aa8722a0d14e94425229bd63543743b6157f3253c33fac8b18765a37464ce3492978a92e50865089999fd1b051b0a833

# Suche nach Postleitzahl
#http://localhost/api/api_github.php?postleitzahl=14770&umkreis_km=20&api_email=s.enger@company.de&api_key=56d9320489ffa79124bb8991fdbca1c7aa8722a0d14e94425229bd63543743b6157f3253c33fac8b18765a37464ce3492978a92e50865089999fd1b051b0a833

require_once("library/PasswordHash.inc.php");	// Genutzte Quelle: https://www.openwall.com/phpass/ v0.5
require_once("library/SQL.inc.php");
require_once("library/Security.inc.php");

$sql_obj			= new SQL();
$sec_obj  			= new Security();
$hash_obj 			= new PasswordHash(8, TRUE); // Passwörter für WordPress Kompatibilität

# Konfiguration der Hauptdatenbank
$hpt_server      	= "";
$hpt_user        	= "";
$hpt_passwort    	= "";
$hpt_datenbank   	= "";
//$hpt_prefix		= "";
$hpt_usermeta 		= "";
$hpt_connection		= $sql_obj->sql_connect($hpt_server, $hpt_datenbank, $hpt_user, $hpt_passwort);
$hpt_geokey				= ""; 	// Konfiguration des Google Maps Geolocation Keys (entfernen bei Veröffentlichung auf github)

$post_body 				= json_decode(file_get_contents('php://input'), true); // Hole den Inhalt aus einem einkommenden POST-Request

// Nimm die Parameter via GET Request an
if (isset($_REQUEST['suche'])){
	$suche 				= $sec_obj->sanitizeRequestSimple($_REQUEST['suche']); // entferne schädlichen Code aus dem einkommenden Request
};
if (isset($_REQUEST['preis_von'])){
	$preis_von 		= $sec_obj->sanitizeRequestSimple($_REQUEST['preis_von']);
};
if (isset($_REQUEST['preis_bis'])){
	$preis_bis 		= $sec_obj->sanitizeRequestSimple($_REQUEST['preis_bis']);
};
if (isset($_REQUEST['postleitzahl'])){
	$postleitzahl	= $sec_obj->sanitizeRequestSimple($_REQUEST['postleitzahl']);
	$umkreis_km		= $sec_obj->sanitizeRequestSimple($_REQUEST['umkreis_km']);
};

$auth_flag 			= False;
if (isset($_REQUEST['api_email']) and isset($_REQUEST['api_key'])){
	$api_email		= $_REQUEST['api_email'];
	$api_key		= $_REQUEST['api_key'];
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
};

$array_items 		= array();
if (isset($suche) and !empty($suche)){ /* Suchgebegriff Suche */
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
	if ($auth_flag !== True){
		$b = array();
		$b['Benutzerkennung'] 	= 'fehlgeschlagen';
		header('Content-Type: application/json');
		echo json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit(0);
	}; // if ($auth_flag !== True){

	$array_items 	= filter_search($suche);
	$a 						= show_all_content_by_id($array_items);
} elseif ( (isset($preis_von) and !empty($preis_von)) or (isset($preis_bis) and !empty($preis_bis)) ){ /* Preis von bis Suche ausgeben */
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
	if ($auth_flag !== True){
		$b = array();
		$b['Benutzerkennung'] 	= 'fehlgeschlagen';
		header('Content-Type: application/json');
		echo json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit(0);
	}; // if ($auth_flag !== True){

	$array_items 	= filter_preis($preis_von, $preis_bis);
	$a 						= show_all_content_by_id($array_items);
} elseif (isset($postleitzahl) and !empty($postleitzahl) and is_numeric($postleitzahl)){ /* Umkreissuche basierend auf Postleitzahl */
	// Achtung: die Funktion  getResultsForRadiusZipCode() hat die DB fest verdrahtet: wp_cp_ad_geocodes
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
	if ($auth_flag !== True){
		$b 					= array();
		$b['Benutzerkennung'] 	= 'fehlgeschlagen';
		header('Content-Type: application/json');
		echo json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit(0);
	}; // if ($auth_flag !== True){

	$array_items 	= getResultsForRadiusZipCode($postleitzahl, $umkreis_km);
	$a 				= show_all_content_by_id($array_items);
} elseif (isset($post_body) and !empty($post_body)){ /* Eintrag zufügen zur Datenbank */
	foreach ($post_body as $key => $val){
		switch($key){
			case "api_email":
				$api_email = $val;
				break;
			case "api_key":
				$api_key = $val;
				break;
			default:
			1;
		};
	}; // foreach ($post_body as $key => $val){
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
	$a 						= array();

	if ($auth_flag === True){
		$a['Eintrag'] 					= 'erfolgreich zugefügt';
		processInsertRequest($post_body);
	} else {
		$a['Benutzerkennung'] 	= 'fehlgeschlagen';
	};
} else { /* Alle Ergebnisse ausgeben */
	$auth_flag		= isValidAuthentification($hpt_connection, $api_key, $api_email);
	if ($auth_flag !== True){
		$b = array();
		$b['Benutzerkennung'] 	= 'fehlgeschlagen';
		header('Content-Type: application/json');
		echo json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit(0);
	}; // if ($auth_flag !== True){

	$array_items 	= get_all_posts();
	$a 						= show_all_content_by_id($array_items);
}; // if (isset($suche) and !empty($suche)){

// Ausgabe der Ergebnisse im JSON Format (ordentlich formuliert, als Unicode Option)
header('Content-Type: application/json');
echo json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit(0);

// Daten aus einem POST Request auslesen und dann zu der DB der StarkeRegion-Plattform hinzufügen ("Eintrag hinzufügen")
function processInsertRequest($post_body){
	global $hpt_connection;
	global $sql_obj;
	global $hpt_bildpfad;

	// Arrays mit Werten initialisieren
	$array_kategorie 	= array(
		# name => id
		"Aushilfe" 								=> 6,
		"Ausstattung" 							=> 7,
		"Azubi" 								=> 8,
		"Fachkraft" 							=> 9,
		"Fahrzeuge" 							=> 10,
		"Gewerberäume" 							=> 11,
		"Lager" 								=> 12,
		"Maschinen" 							=> 13,
		"Produktionsanlagen" 					=> 14,
		"Produktionsräume" 						=> 15,
		"Saisonarbeiter" 						=> 16,
		"Sitzungs- und Veranstaltungsräume" 	=> 19,
		"Transport und Zulademöglichkeit" 		=> 17,
		"Werkzeug" 								=> 18
	);

	$array_art 			= array(
		# name => id
		"Vermietung" 							=> 'cp_vhb',
		"Schenken" 								=> 'cp_vhb',
		"Zeitspende" 							=> 'cp_vhb',
		"Überlassung" 							=> 'cp_vhb',
		"Spenden" 								=> 'cp_vhb'
	);

	$array_giltfuer 	= array(
		# name => id
		"Unternehmen" 							=> 'cp_dieses_angebot_gilt_fr',
		"Bildungseinrichtungen" 				=> 'cp_dieses_angebot_gilt_fr',
		"Kommunale und öffentliche Einrichtungen" => 'cp_dieses_angebot_gilt_fr',
		"Vereine und Verbände" 					=> 'cp_dieses_angebot_gilt_fr',
		"Gemeinnützige Einrichtungen" 			=> 'cp_dieses_angebot_gilt_fr'
	);

	$array_einsatzort 	= array(
		# name => id
		"Beim Kunden" 							=> 'cp_einsatzort',
		"Am Standort" 							=> 'cp_einsatzort',
		"Auf Anfrage" 							=> 'cp_einsatzort',
	);

	$array_fahrzeuge 	= array(
		# name => id
		"Anhänger" 								=> 'cp_fahrzeuge',
		"Gabelstapler" 							=> 'cp_fahrzeuge',
		"PKW" 									=> 'cp_fahrzeuge',
		"Bagger" 								=> 'cp_fahrzeuge',
		"Kran" 									=> 'cp_fahrzeuge',
		"Transporter" 							=> 'cp_fahrzeuge',
		"Baufahrzeuge" 							=> 'cp_fahrzeuge',
		"LKW" 									=> 'cp_fahrzeuge',
		"Sonstiges" 							=> 'cp_fahrzeuge'
	);

	$array_maschinen_werkzeuge 	= array(
	  "Ameisen" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Arbeitsbühnen" 							=> 'cp_maschinen_werkzeug_ausstattung',
	  "Aufzug- und Hebetechnik" 				=> 'cp_maschinen_werkzeug_ausstattung',
	  "Bau" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Bauaufzüge" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Büroausstattung" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Energieerzeugung" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "EUR" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Gastronomiebedarf" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Gerüste und Leitern" 					=> 'cp_maschinen_werkzeug_ausstattung',
	  "Haus und Garten" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Hebebühnen" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Heizung und Sanitärtechnik" 				=> 'cp_maschinen_werkzeug_ausstattung',
	  "IBC" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Installation" 							=> 'cp_maschinen_werkzeug_ausstattung',
	  "Labortechnik" 							=> 'cp_maschinen_werkzeug_ausstattung',
	  "Lagern und Heben" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Messen und Qualitätssicherung" 			=> 'cp_maschinen_werkzeug_ausstattung',
	  "Paletten" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Produktionsmaschinen" 					=> 'cp_maschinen_werkzeug_ausstattung',
	  "Rampen" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Recycling & Entsorgung" 					=> 'cp_maschinen_werkzeug_ausstattung',
	  "Regale" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Reinigung" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Reinigungsmaschinen" 					=> 'cp_maschinen_werkzeug_ausstattung',
	  "Schreibtisch" 							=> 'cp_maschinen_werkzeug_ausstattung',
	  "Schränke" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Stühle" 									=> 'cp_maschinen_werkzeug_ausstattung',
	  "Transport Handling" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Trennwände" 								=> 'cp_maschinen_werkzeug_ausstattung',
	  "Verkehrstechnik" 						=> 'cp_maschinen_werkzeug_ausstattung',
	  "Verpackungsmaschinen" 					=> 'cp_maschinen_werkzeug_ausstattung',
	  "Werkstatteinrichtung" 					=> 'cp_maschinen_werkzeug_ausstattung'
	);

	$array_divers 		= array(
		# name => id
		"außen" 								=> 'cp_merkmale_diverses',
		"innen" 								=> 'cp_merkmale_diverses',
		"beheizt" 								=> 'cp_merkmale_diverses',
		"gekühlt" 								=> 'cp_merkmale_diverses',
	);

	$array_branchen 	= array(
		"Bauen und Planen" 						=> "cp_branchen",
		"Büro- und Verwaltung" 					=> "cp_branchen",
		"Chemie Kunststoff und Pharma" 			=> "cp_branchen",
		"Dienstleistung sonstige" 				=> "cp_branchen",
		"Energie" 								=> "cp_branchen",
		"Forst- und Gartenbau" 					=> "cp_branchen",
		"Gesundheit Medizin und Pflege" 		=> "cp_branchen",
		"Handel" 								=> "cp_branchen",
		"Handwerk" 								=> "cp_branchen",
		"Haushaltsnahe Dienstleistungen" 		=> "cp_branchen",
		"Immobilien" 							=> "cp_branchen",
		"Presse und Medien" 					=> "cp_branchen",
		"Produktion diverse" 					=> "cp_branchen",
		"Tourismus- und Gastronomie" 			=> "cp_branchen",
		"Vereine und Verbände" 					=> "cp_branchen",
		"Versicherung und Finanzen" 			=> "cp_branchen",
		"Sonstige" 								=> "cp_branchen"
	);

	// Ergebnis-Variablen leer inititalisieren
	$mein_kategorie 						= "";
	$mein_title								= "";
	$mein_preis								= "";
	$mein_preis_allg 						= "";
	$mein_art 								= "";
	$arr_mein_gilt_fuer						= array();
	$mein_beschreibung						= "";
	$mein_angebotab							= "";
	$arr_mein_einsatzort					= array();
	$mein_str_nr							= "";
	$mein_plz								= "";
	$mein_stadt								= "";
	$mein_opt_gewicht						= "";
	$mein_opt_menge							= "";
	$mein_opt_mass							= "";
	$mein_opt_alter							= "";
	$mein_opt_kwh							= "";
	$mein_opt_zustand 						= "";
	$arr_mein_fahrzeug						= array();
	$arr_mein_maschinen						= array();
	$arr_mein_diverse						= array();
	$arr_mein_branchen						= array();
	$mein_bild 								= "";
	$mein_bild_beschreibung				 	= "";
	$mein_author_login						= "";
	$mein_author_fullname					= "";
	$mein_author_email						= "";
	$mein_author_password					= "";

	// Ergebnis-Variablen mit finalen Werten befüllen lassen
	foreach ($post_body as $key => $val){
		switch($key){
			case "kategorie":
				$mein_kategorie = getArrayItemByName($val, $array_kategorie);
				break;
			case "title":
				$mein_title = $val;
				break;
			case "preis":
				$mein_preis = $val;
				break;
			case "preis_allg":
				$mein_preis_allg = $val;
				break;
			case "art":
				$mein_art = $val;
				break;
			case "kat_fahrzeug":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_giltfuer);
					$arr_mein_gilt_fuer[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "beschreibung":
				$mein_beschreibung = $val;
				break;
			case "angebot_ab":
				if (validateDate($val) === TRUE){
					$mein_angebotab = $val;
				}; // if (validateDate($val) === TRUE){
				break;
			case "einsatzort":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_einsatzort);
					$arr_mein_einsatzort[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "str_nr":
				$mein_str_nr = $val;
				break;
			case "plz":
				$mein_plz = $val;
				break;
			case "stadt":
				$mein_stadt = $val;
				break;
			case "opt_gewicht":
				$mein_opt_gewicht = $val;
				break;
			case "opt_menge":
				$mein_opt_menge = $val;
				break;
			case "opt_mass":
				$mein_opt_mass = $val;
				break;
			case "opt_alter":
				$mein_opt_alter = $val;
				break;
			case "opt_kwh":
				$mein_opt_kwh = $val;
				break;
			case "opt_zustand":
				$mein_opt_zustand = $val;
				break;
			case "kat_fahrzeug":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_fahrzeuge);
					$arr_mein_fahrzeug[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "kat_maschinen":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_maschinen_werkzeuge);
					$arr_mein_maschinen[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "kat_diverse":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_divers);
					$arr_mein_diverse[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "kat_branchen":
				$my_val = explode(",", $val);
				foreach ($my_val as $ele){
					$ele = trim($ele);
					$meine = getArrayItemByName($ele, $array_branchen);
					#echo "$meine -> $ele<br>";
					$arr_mein_branchen[$ele] = $meine;
				}; // foreach ($my_val as $ele){
				break;
			case "bild_url":
				$mein_bild = $val;
				break;
			case "bild_beschreibung":
				$mein_bild_beschreibung = $val;
				break;
			case "author_login":
				$mein_author_login = $val;
				break;
			case "author_fullname":
				$mein_author_fullname = $val;
				break;
			case "author_email":
				$mein_author_email = $val;
				break;
			case "author_password":
				$mein_author_password = $val;
				break;
			default:
				1;
		}; // switch($key){
	}; // foreach ($post_body as $key => $val){

	$maxID 									= getCountUpID("ID", "wp_posts");
	$date 									= new DateTime();
	$insertDate 							= date_format($date, 'Y-m-d H:i:s');
	$post_name 								= str_replace(" ", "-", strtolower(trim($mein_title))); 	# " " > "-" && trim && lower

	$myDate 								= date('Y-m-d');
	$cp_sys_expire_date						= date('Y-m-d H:i:s', strtotime($myDate. ' + 90 days'));	# 90 Tage Laufzeit der Anzeige
	$maxID_User								= 1234567;

	// Arrays mit Werten initialisieren, die anschließend in die Datenbank (=DB) gespeichert werden
	$wp_posts = array('ID' 					=> $maxID,
	  'post_author' 						=> $maxID_User,	// muss angepasst werden
	  'post_date' 							=> $insertDate,
	  'post_date_gmt' 						=> $insertDate,
	  'post_content' 						=> $mein_beschreibung,
	  'post_title' 							=> $mein_title,
	  'post_excerpt' 						=> $mein_beschreibung,
	  'post_status' 						=> 'publish',
	  'comment_status' 						=> 'closed',
	  'ping_status' 						=> 'closed',
	  'post_password' 						=> '',
	  'post_name' 							=> $post_name,
	  'to_ping' 							=> '',
	  'pinged' 								=> '',
	  'post_modified' 						=> $insertDate,
	  'post_modified_gmt' 					=> $insertDate,
	  'post_content_filtered' 				=> $mein_beschreibung,
	  'post_parent' 						=> '0',
	  'guid' 								=> "https://api.xxx.com/?post_type=ad_listing&#038;p=$maxID",
	  'menu_order' 							=> '0',
	  'post_type' 							=> 'ad_listing',
	  'post_mime_type' 						=> '',
	  'comment_count' 						=> '0'
	);

	// Arrays mit Werten initialisieren, die anschließend in die Datenbank (=DB) gespeichert werden
	$wp_postmeta = array(
		"cp_street"							=> $mein_str_nr,
		"cp_city"							=> $mein_stadt,
		"cp_zipcode"						=> $mein_plz,
		"cp_price"							=> $mein_preis,
		"cp_preis_netto_fr_gemeinntzige_ei" => $mein_preis_allg,
		"cp_menge" 							=> $mein_opt_menge,
		"cp_gewicht" 						=> $mein_opt_gewicht,
		"cp_abmessung_und_mae" 				=> $mein_opt_mass,
		"cp_alter" 							=> $mein_opt_alter,
		"cp_kwh" 							=> $mein_opt_kwh,
		"cp_zustand" 						=> $mein_opt_zustand,
		"cp_sys_expire_date"				=> $cp_sys_expire_date,
		"cp_sys_ad_duration"				=> "90", // 90 Tage Laufzeit
		"cp_sys_total_ad_cost"				=> "0",
		"cp_sys_userIP"						=> "::1",
		"cp_sys_ad_conf_id"					=> uniqid(),
	);

	// Daten in die DB speichern "wp_posts"
	$wp_posts2			= prepareArrayForSQLInsert($wp_posts);
	$columns 			= implode(", ",array_keys($wp_posts2));
	$values  			= implode(", ", $wp_posts2);
	$sql_query 			= "INSERT INTO `wp_posts`($columns) VALUES ($values)";
	$results 			= $sql_obj->sql_query($hpt_connection, $sql_query);

	// Daten in die DB speichern "wp_geocodes"
	$myGeo				= getGeocodes("$mein_str_nr, $mein_plz, $mein_stadt");
	$myGeo['post_id'] 	= $maxID;
	$wp_geocodes		= prepareArrayForSQLInsert($myGeo);
	$columns2 			= implode(", ",array_keys($wp_geocodes));
	$values2  			= implode(", ", $wp_geocodes);
	$sql_query2 		= "INSERT INTO `wp_cp_ad_geocodes`($columns2) VALUES ($values2)";
	$results2 			= $sql_obj->sql_query($hpt_connection, $sql_query2);

	// Daten in die DB speichern "wp_postmeta"
	addContentToSQLpostmeta($wp_postmeta, $maxID, $special=False);
	addContentToSQLpostmeta($arr_mein_gilt_fuer, $maxID, $special=True);
	addContentToSQLpostmeta($arr_mein_einsatzort, $maxID, $special=True);
	addContentToSQLpostmeta($arr_mein_fahrzeug, $maxID, $special=True);
	addContentToSQLpostmeta($arr_mein_maschinen, $maxID, $special=True);
	addContentToSQLpostmeta($arr_mein_diverse, $maxID, $special=True);
	addContentToSQLpostmeta($arr_mein_branchen, $maxID, $special=True);

	return True;
}; //function processInsertRequest


// Benutzer Authentifizierung gegenüber unserer API Datenbank Tabelle
function isValidAuthentification($hpt_connection, $api_key, $api_email){
	global $sql_obj;
	global $hpt_connection;

	#$api_key 		= filter_var($api_key, FILTER_SANITIZE_STRING);
	$api_email 		= filter_var($api_email, FILTER_SANITIZE_EMAIL);
	$val_1 			= preg_match('/[a-zA-Z0-9]/', $api_key);

	if ($val_1 != 1){
		return False;
	};
	if (empty($api_email) or !isset($api_email)){
		return False;
	};

	$sql_query 	= "SELECT `customer_email` FROM `authentication` WHERE `customer_email` = '$api_email' AND `apikey` = '$api_key' LIMIT 1;";
	//echo $sql_query;
	try {
		$row 			= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);
	} catch (Exception $e) {
		return False;
	};

	foreach ($row as $key1){
		foreach ($key1 as $key => $val){
			if ($key=="customer_email" and $val == $api_email)
			//echo "key=$key and val=$val";
			//echo True; //
			return True;
		}; // foreach ($key1 as $key){
	} // foreach ($row as $key){
	return False;
}; // function isValidAuthentification($api_key, $api_email){


// Inhalt eines Arrays separat in SQL Tabelle 'postmeta' zufügen
function addContentToSQLpostmeta($array, $myPostID, $special){
	# Genutzte Quelle: https://stackoverflow.com/questions/2848505/how-does-wordpress-link-posts-to-categories-in-its-database
	global $sql_obj;
	global $hpt_connection;

	foreach ($array as $key => $val){
		$meta_id	= getCountUpID("meta_id", "wp_postmeta"); // hole die aktuell höhste meta_id aus der Tabelle "wp_postmeta" und zähle 1 hinzu -> somit bekommen wir die neue meta_id unter der wir den Eintrag speichern können
		#$myKey 		= "'".$key."'";
		$v 				= $val;
		$k				= $key;

		if ($special === True){		// Reverse, wenn Special Flag gesetzt ist
			$val 		= "'".$k."'";; 	// Quoting für SQL
			$key 		= "'".$v."'";	  // Quoting für SQL
		} else {
			$key		= "'".$k."'";	  // Quoting für SQL
			$val		= "'".$v."'";	  // Quoting für SQL
		};

		if (strlen($k)>0 and strlen($v)>0){ # isset und empty funktionieren nicht
			$sql_query3 = "INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ($meta_id,$myPostID,$key,$val)";
			$results 	= $sql_obj->sql_query($hpt_connection, $sql_query3);
		};
	}; // foreach ($array as $key => $val){
	return True;
}; // function addArraytoSQL


// Geocodes zu einer Adresse ausrechnen
function getGeocodes($address){
	global $hpt_geokey;
	/*
	Beschreibung des Algorithmus:
		a.1. übergebe eine Adresse in Deutschland, in der Form "Musterstraße 123, 45678 Beispielstadt" oder:
		a.2. übergebe eine Postleitzahl in Deutschland
		b. übergebe diese Parameter fest codiert auf das Land "Deutschland" an Google Maps Geocoding API und hole die Geokoordinaten lat und lng davon ab
		c. übergebe die ausgeforschten Geokoordinaten an ein Array, welches die Funktion zurück gibt.
	*/
	$my_array			= array();

	$t_address		= $address;
	$address 			= str_replace(" ", "+", $address);
	$region 			= "Deutschland";
	$myQuery 			= "";

	if (is_numeric($t_address)){ // Adresse ist rein nummerisch: also nur Postleitzahl
		$myQuery 		= "https://maps.google.com/maps/api/geocode/json?region=$region&key=$hpt_geokey&components=country:DE|postal_code:$address";
	} else { 					// normale Adresse
		$myQuery		= "https://maps.google.com/maps/api/geocode/json?address=$address&region=$region&key=$hpt_geokey";
	};

	$json1 				= file_get_contents($myQuery); # getWebsite($myQuery);
	$json 				= json_decode($json1);

	$my_array['lat'] 	= $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
	$my_array['lng'] 	= $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};

	return $my_array;
}; // function getGeocodes


// Array Value vorbereiten zum zufügen ins SQL
function prepareArrayForSQLInsert($wp_posts){
	$wp_posts2 = array();
	foreach ($wp_posts as $key => $val){
		$wp_posts2[$key] = "'".$val."'"; // führe ein Quoting des zum SQL hinzuzufügenden Wertes durch
	};
	return $wp_posts2;
}; // function prepareArrayForSQLInsert


// Hole die höhste ID in einer WP DB und zähle einen Eintrag hinzu -> für neuen Eintrag
function getCountUpID($hpt_id, $hpt_table){
	global $sql_obj;
	global $hpt_connection;
	$ret_val	= array();
	$sql_query 	= "SELECT MAX(".$hpt_id.") as `max` FROM ".$hpt_table." LIMIT 1;";
	$row		= $sql_obj->sql_fetch($hpt_connection, $sql_query);
	if (isset($row['max']) and !empty($row['max'])){
		return intval($row['max']) + intval(1);
	};
	return 1; // hier würde es keinen Eintrag geben, unsere post_ids sollen jedoch mit 1 beginnen
}; // function getCountUpID


// Array Inhalt anhand seines Values ausgeben
function getArrayItemByName($name, $array){
	if (isset($array[$name]) and !empty($array[$name])){
		return trim($array[$name]);
	} else {
		return "";
	};
}; // function getArrayItemByName


// prüfe, ob gültiges Datum eingegeben wurde
function validateDate($date, $format = 'd.m.Y'){
    // Genutzte Quelle: https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
	$d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}; // function validateDate


function filter_preis($preis_von, $preis_bis){
	global $sql_obj;
	global $hpt_connection;
	$ret_val		= array();

	// php wandelt strings z.b. beim Runden gerne mal fehlerhaft um, darum der Workaround
	$pos 			= strpos($preis_von, '.');
	$pos2 			= strpos($preis_von, ',');
	$pos_a 			= strpos($preis_bis, '.');
	$pos_a2 		= strpos($preis_bis, ',');

	//Hinweis: da hier "abgerundet" wird, müsste theoretisch bei einem Wert von "$tmp">0 dann zu dem finalen "$preis_von", "$preis_bis" die Zahl 1 zuaddiert werden ("aufgerundet")
	if ($pos !== false){ 	// 44.99
		list($p_von, $tmp) = explode('.', $preis_von);
		$preis_von = $p_von;
	};
	if ($pos2 !== false){ 	// 44,99
		list($p_von, $tmp) = explode(',', $preis_von);
		$preis_von = $p_von;
	};
	if ($pos_a !== false){ 	// 44.99
		list($p_bis, $tmp) = explode('.', $preis_bis);
		$preis_bis = $p_bis;
		if (is_numeric($tmp)){
			$preis_bis++;	// aufrunden
		};
	};
	if ($pos_a2 !== false){ // 44,99
		list($p_bis, $tmp) = explode(',', $preis_bis);
		$preis_bis = $p_bis;
		if (is_numeric($tmp)){
			$preis_bis++;	// aufrunden
		};
	};

	if (!is_numeric($preis_von)){
		$preis_bis = 0;
	};
	if (!is_numeric($preis_bis)){
		$preis_bis = 10000000;
	};

	$sql_query 		= "SELECT `post_id`, `meta_value` FROM `wp_postmeta` WHERE `meta_key` = 'cp_price'";
	$row			= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);
	foreach ($row as $key => $val){
		$price 		= $val['meta_value'];
		$post_id 	= $val['post_id'];

		// Achtung: wir geben nur Preise aus, die aus nummerisch sind, z.B. "16" -> ein Ergebnis wie "12 Euro" wird nicht ausgegeben
		if (is_numeric($price) and $price >= $preis_von and $price <= $preis_bis){
			array_push($ret_val, $post_id);
		};
	}; // foreach

	return $ret_val;
}; // function filter_preis


// Zeige alle Ergebnisse mittels ihrer post_id an
function show_all_content_by_id($array_items){
	global $hpt_connection;
	$res_all 			= array();
	foreach ($array_items as $id){
		$result_single	= show_one_entry($hpt_connection, $id);
		array_push($res_all, $result_single);
	};
	return $res_all;
} // function show_all_content_by_id


// zeige alle post_ids an, die einen Artikel beinhalten
function get_all_posts(){
	global $sql_obj;
	global $hpt_connection;
	$ret_val	= array();
	$sql_query 	= "SELECT `ID` FROM `wp_posts` WHERE `post_status` = \"publish\" AND `guid` LIKE '%post_type=ad_listing%' LIMIT 5";
	$row		= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);
	foreach ($row as $key1){
		foreach ($key1 as $key){
			if (is_numeric($key)){
				array_push($ret_val, $key);
			};
		};
	};
	return $ret_val;
} // function get_all_posts


// Zeige genau einen Artikel an, vermittelt durch seine post_id
function show_one_entry($hpt_connection, $id){
	global $sql_obj;
	global $hpt_connection;
	global $hpt_bildpfad_web;

	$array_wppost_results 		= array();
	$array_geo_results 			= array();
	$array_postmeta_results 	= array();

	$sql_query 					= "SELECT * FROM `wp_posts` WHERE `ID` = '$id'";
	$row						= $sql_obj->sql_fetch($hpt_connection, $sql_query);	# sql_fetch() hier weil wir nur 1 Post abholen
	/// $row == null bedeutet, dass auch der restliche Codeblock nicht mehr korrekt ausgeführt werden kann -> gib dann leere Daten aus
	if (is_null($row) or count($row) <= 0){
		$result_array1 			= array(
			"wp_posts" 			=> $array_wppost_results,
			"wp_postmeta" 		=> $array_postmeta_results,
			"wp_cp_ad_geocodes" => $array_geo_results
		);
		return $result_array1;
	};

	if (isset($row) and !is_null($row)){
		$array 					= array("wp_posts" => $row);
		array_push($array_wppost_results, $array);
	};

	$sql_bilder 				= "SELECT * FROM `wp_posts` WHERE (`ID` = '".$row['ID']."' OR `post_parent` = '".$row['ID']."') AND `post_type` = 'attachment'";
	$row2						= $sql_obj->sql_fetchAll($hpt_connection, $sql_bilder);

	if (isset($row2) and !is_null($row2)){
		$array2 				= array("wp_posts_bilder" => $row2);
		array_push($array_wppost_results, $array2);
	};

	//pretty_print($row2);

	$row42 	= array(); // leer initialisieren
	$myIds 		= "";
	$sql_meta2 	= "";
	if (count($row2) > 1){
		$myIds .= "(";
		foreach ($row2 as $k1 => $v1){
			foreach ($v1 as $k => $v){
				if ($k=="ID"){
					$myIds .= trim($v).",";
				};
			};
		};
		$myIds = substr($myIds, 0, -1);
		$myIds .= ")";
		// wir wollen hier mehere Einträge gegen das SQL übergeben und abprüfen
		#SELECT * FROM TABLE WHERE ID IN (id1, id2, ..., idn)
		$sql_meta2 		= "SELECT * FROM `wp_postmeta` WHERE `post_id` IN $myIds";
		//echo $sql_meta2;
	} elseif (count($row2) == 1) {
		// wir wollen hier nur einen Eintrag gegen das SQL übergeben und abprüfen
		$sql_meta2 		= "SELECT * FROM `wp_postmeta` WHERE `post_id` = '".$row2[0]['ID']."'";
		//echo $sql_meta2;
	} else {
		$sql_meta2 		= "SELECT 1"; // Dummy Request
	}
	// if (count($row2) > 1){
	//echo $sql_meta2;
	//echo "<br>";
	$row42 				= $sql_obj->sql_fetchAll($hpt_connection, $sql_meta2);
	//};

	foreach ($row42 as $k1 => $v1){
		foreach ($v1 as $k => $v){
			// prüfe mit einem definierten Regex, ob wir ein Bild als meta_value bekommen haben
			$val_1 		= preg_match('/(\d{4})\/(\d{2})\/(\w{1,})\.(\w{2,})/', $v); // "2020\/10\/5f903e716d137.png"
			$val_2 		= preg_match('/(\d{4})\/(\d{2})\/(.)\.(\w{2,})/', $v); // "2020\/10\/5f903e716d137.png"
			if ($k=="meta_value" and ($val_1 == 1 or $val_2 == 1) and strlen($v) < 35){
				$v_new 	= $hpt_bildpfad_web . "/" . $v;	// Wandele den Bildpfad in eine URL um, die man abfragen bzw. abholen kann
				$row42[$k1][$k] = $v_new;				// schreibe die neuen Bild URLS an die jeweilige Stelle im Ergebnis Array
			};
		};
	};

	if (isset($row42) and !is_null($row42)){
		$array22 			= array("wp_postmeta_bilder" => $row42);
		array_push($array_postmeta_results, $array22);
	};

	$sql_geocode  			= "SELECT * FROM `wp_cp_ad_geocodes` WHERE `post_id` = '".$row['ID']."' LIMIT 1;";
	$row3 					= $sql_obj->sql_fetch($hpt_connection, $sql_geocode); # sql_fetch() nur nutzen, wenn nur 1 Eintrag geholt werden soll

	if (isset($row3) and !is_null($row3)){
		array_push($array_geo_results, $row3);
	};

	$sql_meta 				= "SELECT * FROM `wp_postmeta` WHERE `post_id` = '".$row['ID']."'";
	$row4 					= $sql_obj->sql_fetchAll($hpt_connection, $sql_meta);

	if (isset($row4) and !is_null($row4)){
		array_push($array_postmeta_results, $row4);
	};

	$result_array = array(
		"wp_posts" 			=> $array_wppost_results,
		"wp_postmeta" 		=> $array_postmeta_results,
		"wp_cp_ad_geocodes" => $array_geo_results
	);
	return $result_array;
}; // function show_one_entry


// Ergebnisse schön darstellen - für den DEBUG oder Entwicklungsmodus
function pretty_print($data){
	echo "<b>pretty_print/DEBUG:</b><br>";
	echo "<pre>";
	var_dump($data);
	echo "</pre>";
} // function pretty_print(


// Suchfunktion nach: Suchgebegriff oder Kategorie oder Standort
function filter_search($search_query){
	global $sql_obj;
	global $hpt_connection;
	global $hpt_datenbank;
	$ret_val	= array();

	$sql_query 	= "SELECT `ID` FROM `$hpt_datenbank`.`wp_posts` WHERE (CONVERT(`ID` USING utf8) LIKE '%$search_query%' OR 		CONVERT(`post_author` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_date` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_date_gmt` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_content` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_title` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_excerpt` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_status` USING utf8) LIKE '%$search_query%' OR CONVERT(`comment_status` USING utf8) LIKE '%$search_query%' OR CONVERT(`ping_status` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_password` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_name` USING utf8) LIKE '%$search_query%' OR CONVERT(`to_ping` USING utf8) LIKE '%$search_query%' OR CONVERT(`pinged` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_modified` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_modified_gmt` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_content_filtered` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_parent` USING utf8) LIKE '%$search_query%' OR CONVERT(`guid` USING utf8) LIKE '%$search_query%' OR CONVERT(`menu_order` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_type` USING utf8) LIKE '%$search_query%' OR CONVERT(`post_mime_type` USING utf8) LIKE '%$search_query%' OR CONVERT(`comment_count` USING utf8) LIKE '%$search_query%') AND `post_status` = 'publish' AND `guid` LIKE '%post_type=ad_listing%' LIMIT 5";

	$results 	= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);
	foreach ($results as $key1){
		foreach ($key1 as $key){
			if (is_numeric($key)){
				array_push($ret_val, $key);
			};
		};
	}
	return $ret_val;
} // function filter_search


// Funktion wandelt eine Postleitzahl und einen Umkreis in km in passende Ergebnisse aus der StarkeRegion-Plattform um
function getResultsForRadiusZipCode($mein_plz, $umkreis_km){
	global $sql_obj;
	global $hpt_connection;
	global $hpt_datenbank;
	$array_items 	= array();

	$a				= getGeocodes($mein_plz);
	$lat 			= $a['lat'];
	$lng 			= $a['lng'];

	if (!is_numeric($umkreis_km)){		// Fallback, falls kein Umkreis angegeben wurde
		$umkreis_km = 5;
	};

	// SQL Query, um zu einer Postleitzahl (deren Lat/Long Geocodes), die passende Einträge innerhalb dieser Distanz abzuholen
	$sql_query 		= "SELECT post_id, ( 3959 * acos( cos( radians($lat) ) * cos( radians( lat ) )
	* cos( radians( lng ) - radians($lng) ) + sin( radians($lat) ) * sin(radians(lat)) ) ) AS distance
	FROM wp_cp_ad_geocodes
	HAVING distance < $umkreis_km
	ORDER BY distance LIMIT 5";
	$row		= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);

	foreach ($row as $key1 => $val1){
		foreach ($val1 as $key => $val){
			if ($key == "post_id"){
				array_push($array_items, $val);	// gib nur die gefundenen post_id zürück, die den Kriterien entsprechen
			};
		};
	};

	return $array_items;
}; // function getResultsForRadiusZipCode


// Webseite (HTML, XHTML, XML, image, etc.) von URL abholen
function getWebsite( $url ){
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "spider", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => true     	// Disabled SSL Cert checks
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $content;
}; // getWebsite( $url ){

exit(0);
?>
