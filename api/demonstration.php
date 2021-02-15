<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$url 				= "http://localhost/api/api_github.php";

$array 				= array(
	"kategorie" 	=> "Produktionsräume",
	"title"			=> "Potsdam Autobahn Testeintrag",
	"preis"			=> "199",
	"preis_allg"	=> "135",
	"art"			=> "Vermietung,Überlassung",
	"gilt_fuer"		=> "Kommunale und öffentliche Einrichtungen",
	"beschreibung"	=> "Ich bin eine leere Lagerhalle, die an Öffentliche Einrichtungen zu überlassen ist.",
	"angebot_ab"	=> "23.12.2020",
	"einsatzort"	=> "Am Standort",
	"str_nr"		=> "Auf dem Strengfeld 6",
	"plz"			=> "14542",
	"stadt"			=> "Werder (Havel)",
	"opt_gewicht"	=> "",
	"opt_menge"		=> "",
	"opt_mass"		=> "",
	"opt_alter"		=> "",
	"opt_kwh"		=> "",
	"opt_zustand"	=> "",
	"kat_fahrzeug"	=> "Transporter,LKW",
	"kat_maschinen"	=> "Produktionsmaschinen, Rampen",
	"kat_diverse"	=> "außen",
	"kat_branchen"	=> "Sonstige",
	// API Benutzer Authentifizierung
	"api_email"			=> "s.enger@company.de",
	"api_key"			=> "56d9320489ffa79124bb8991fdbca1c7aa8722a0d14eaF7464ce3492978a92e50865089999fd1b051b0a833",
);

//$j 				= json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$j 					= json_encode($array);

//open connection
$ch 				= curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $j);

//So that curl_exec returns the contents of the cURL; rather than echoing it
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

//execute post
$result 			= curl_exec($ch);
#echo 'Curl-Fehler: ' . curl_error($ch);
echo $result;
exit(1);

?>
