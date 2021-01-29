<?php
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
//ini_set("error_log", "C:\\xampp\\htdocs\\php-error.log");	// Logge Fehlermeldungen in eine lokale Datei
#error_reporting(E_WARNING); // Gib alle Warnungen aus
#####error_reporting(E_ALL);
#error_log(message, 3, "errorlog.txt");

/*
Autor: 		Sebastian Enger, Jan Wagener
Email: 		s.enger@drei-i-m.de, j.wagener@drei-i-m.de
Zweck: 		StarkeRegion Plattform: Darstellen einer Anzeige als Werbemittel für Drittseiten
Version: 	1.0.5
Datum: 		12.01.2021 / 35e2ab0725537a12f4454bc1d8aedc7d

http://localhost/banner/banner_responsive.php?id=2&hochquer=t&count=3
*/

header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/html");

require_once("library/Security.inc.php");
require_once("library/SQL.inc.php");
$sql_obj						= new SQL();
$secu_obj						= new Security();

$avail_multisites		= array(2,7,12,24);	# Hier die Ids der Multisite eintragen
$hpt_avail_multi		= array(
	2 => "xyz.starkeregion.digital",
	7 => "xyz.starkeregion.digital",
	12 => "xyz.starkeregion.digital",
	24 => "xyz.starkeregion.digital"
);

// Welche Multisite ID soll angezeigt werden
$hpt_multisite_id		= 2; // default Wert
$hpt_multisite_valid= false;
$meine_multisite_id = "";

if(isset($_GET['id'])) {
	$meine_multisite_id = $secu_obj->sanitizeRequestSimple($_GET['id']);
	foreach ($avail_multisites as $myMultisiteID){
		if ($myMultisiteID == $meine_multisite_id){
			//$hpt_multisite_valid = true;
			$hpt_multisite_id = $meine_multisite_id;
		};
	};
};

# Konfiguration der Hauptdatenbank
$hpt_server      	= "localhost";					// (entfernen bei Veröffentlichung auf github)
$hpt_user        	= "";										// (entfernen bei Veröffentlichung auf github)
$hpt_passwort    	= "";										// (entfernen bei Veröffentlichung auf github)
$hpt_datenbank   	= "";										// (entfernen bei Veröffentlichung auf github)
$hpt_tableprefix	= "myprefix";
$hpt_domain			= $hpt_avail_multi[$hpt_multisite_id];
$hpt_wpposts		= $hpt_tableprefix."_".$hpt_multisite_id."_posts";
$hpt_wppostmeta		= $hpt_tableprefix."_".$hpt_multisite_id."_postmeta";

$hpt_bilderurl		= "https://xxx/wp-content/uploads/sites/"; // Link zu den Multisites, in deren Unterordner die jeweiligen Bilder abgelegt werden
$hpt_connection		= $sql_obj->sql_connect($hpt_server, $hpt_datenbank, $hpt_user, $hpt_passwort);
$post_count				= 3; // Anzahl an anzuzeigenden Anzeigen

if(isset($_GET['count'])) {
	$post_count 		= $secu_obj->sanitizeRequestSimple($_GET['count']);
	if ($post_count == 0){
		$post_count = 1; // damit es später nicht zu einer Division durch Null kommt
	}
}

// Querformat = false oder Hochformat = true
$hochquer = false;
if(isset($_GET['hochquer'])) {
	if ($_GET['hochquer'] == "true" or $_GET['hochquer'] == "t") {
		$hochquer = true;
	}
}

?>
<html>
<head>
	<?php //Quelle: https://google-webfonts-helper.herokuapp.com/fonts/roboto?subsets=latin ?>
	<!-- <link xhref="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">	 -->
	<style>
	/* roboto-regular - latin: die Fonts werden mit freigegeben und sollten von dem eigenen Server geladen werden */
	/* Fonts liegen mit anbei */
	@font-face {
		font-family: 'Roboto';
		font-style: normal;
		font-weight: 400;
		src: url('https://xxx/fonts/roboto-v20-latin-regular.eot'); /* IE9 Compat Modes */
		src: local('Roboto'), local('Roboto-Regular'),
			 url('https://xxx/fonts/roboto-v20-latin-regular.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */
			 url('https://xxx/fonts/roboto-v20-latin-regular.woff2') format('woff2'), /* Super Modern Browsers */
			 url('https://xxx/fonts/roboto-v20-latin-regular.woff') format('woff'), /* Modern Browsers */
			 url('https://xxx/fonts/roboto-v20-latin-regular.ttf') format('truetype'), /* Safari, Android, iOS */
			 url('https://xxx/fonts/roboto-v20-latin-regular.svg#Roboto') format('svg'); /* Legacy iOS */
	}
	</style>
</head>
<body>
	<?php if ($hochquer) { //Hochformat ?>
	<div style="width:320px; height:100%; font-family: 'Roboto', serif; background-color: rgba(255,255,255, 0.5); ">
		<div style="font-style: normal; font-weight: normal; font-size: 20px;">
	<?php } else { //Querformat ?>
	<div style="width:100%; height:230px; font-family: 'Roboto', serif">
		<div style="height: 28px; font-style: normal; font-weight: normal; font-size: 20px; overflow: hidden;">
	<?php } ?>

			<span style="padding:8px;">
				<strong style="font-size: 24px;	font-style: normal;	font-weight: bold;">starkeRegion<span style="color:#00CCFF;">.</span></strong>
			</span>
			<?php if($hochquer) { ?><div style="font-size: 12px; padding: 2px 0px 10px 8px;"><?php } ?>
					<span>Aktuelle Einträge</span>
			<?php if($hochquer) { ?></div><?php } ?>
		</div>
		<div style="height: calc(100% - 50px);">

		<?php
		$a = get_all_posts($post_count);

		if ($hochquer) { //Hochformat
			$img_width = "100%";
			$img_height = floor(100/$post_count). "%";
		} else { //Querformat
			$img_width = floor(100/$post_count). "%";
			$img_height = "100%";
		}

		foreach ($a as $post_id){
			$h 								= getContentForBanner($post_id);
			$article_link 		= $h['article_link'];
			$mein_bild_weburl = $h['mein_bild_weburl'];
			$mein_post_titel 	= $h['mein_post_titel'];
			$mein_post_preis 	= $h['mein_post_preis'];

			if ($mein_post_preis == 00 or $mein_post_preis == 000 or $mein_post_preis == 0000){
				$mein_post_preis = 0;
			}
			?>

				<div style="float: left; width: <?php echo $img_width; ?>; height: <?php echo $img_height; ?>; ">
					<a href="<?php echo $article_link; ?>" target="_blank" title="<?php echo $mein_post_titel; ?>">
						<img src="<?php echo $mein_bild_weburl; ?>" alt="<?php echo $mein_post_titel; ?>" style="padding: 5px;	width: 90%;	height: 90%;	border-radius: 5px;	object-fit: cover;	box-shadow: 4px 1px 13px 1px rgba(217, 217, 217, 1);border-radius: 5px;"></img>
					</a>

					<div style="position: sticky; width: 85%;	background: #ffffff; padding: 3px; padding-left: 8px; border-radius: 0px; margin-top: -45px;">
						<a href="<?php echo $article_link; ?>" target="_blank" title="<?php echo $mein_post_titel; ?>" class="button" style="background-color: #00CCFF; border: none; color: white; padding: 12px 13px; text-align: center; text-decoration: none; display: inline-block; font-size: 8px; xmargin: 4px 2px; cursor: pointer; float: right;"><img src="https://starkeregion.digital/img_logo/arrow-streg.svg" style="font-size: 14px;"></i></a>

						<div style="height: 18px; font-weight: 500; font-size: 14px; overflow: hidden;">
							<b><?php echo $mein_post_titel; ?></b>
						</div>
						<span style=" font-weight: bold; font-size: 16px; color: #00CCFF;">
							<b><?php echo $mein_post_preis." €"; ?></b>
						</span>
					</div>
				</div>
			<?php
		}; // foreach

		?>
		</div>
	</div>
</body>
</html>
<?php


function getContentForBanner($post_id){
	global $sql_obj;
	global $hpt_wpposts;
	global $hpt_wppostmeta;
	global $hpt_connection;
	global $hpt_multisite_id;
	global $hpt_bilderurl;

	$supported_image = array(
		'gif',
		'jpg',
		'jpeg',
		'png'
	);

	/*
	#Daten benötigt: Bild als Grafik, Titel, Ausschnitt vom Inhalt (großes Banner: 140 Zeichen, kleines Banner: 80 Zeichen), Preis, Spende (postmeta), Link zum Artikel, Link zur Webseite, Guid (glaube ich nicht)+
	*/

	$mein_bild_weburl 	= "";
	$mein_post_titel 	= "";
	$mein_post_content 	= "";
	$mein_post_content_kurz 	= "";
	$mein_post_name 	= "";
	$mein_post_guid 	= "";
	$mein_post_preis 	= "";
	$mein_post_vhb 		= "";

	$sql_query 			= "SELECT `post_title`, `post_content`, `post_name`, `guid` FROM `$hpt_wpposts` WHERE `ID` = '$post_id'";
	$row1				= $sql_obj->sql_fetchALL($hpt_connection, $sql_query);

	foreach ($row1 as $k1 => $v1){
		foreach ($v1 as $key => $val){
			switch($key){
				case "post_title":
					$mein_post_titel = $val;
					break;
				case "post_content":
					$mein_post_content = $val;
					break;
				case "post_name":
					$mein_post_name = $val;
					break;
				case "guid":
					$mein_post_guid = $val;
					break;
				default:
					1;
			}; // switch($key){
		}; // foreach ($v1 as $k => $v){
	}; // foreach ($row2 as $k1 => $v1){

	$p_url 				= parse_url($mein_post_guid);
	$backlink			= "https://".$p_url['host']."/";
	$article_link = "https://".$p_url['host']."/ads/".$mein_post_name;

	$sql_bilder 	= "SELECT * FROM `$hpt_wpposts` WHERE (`ID` = '$post_id' OR `post_parent` = '$post_id') AND `post_type` = 'attachment'";
	$row2					= $sql_obj->sql_fetchALL($hpt_connection, $sql_bilder);
	$row42 				= array(); // leer initialisieren
	$myIds 				= "";
	$sql_meta2 		= "";
	if (count($row2) > 1){
		$myIds .= "(";
		foreach ($row2 as $k1 => $v1){
			foreach ($v1 as $k => $v){
				if ($k=="ID"){
					$myIds .= trim($v).",";
				}; // if ($k=="ID"){
			}; // foreach ($v1 as $k => $v){
		}; // foreach ($row2 as $k1 => $v1){
		$myIds = substr($myIds, 0, -1);
		$myIds .= ")";
		// wir wollen hier mehere Einträge gegen das SQL übergeben und abprüfen
		#SELECT * FROM TABLE WHERE ID IN (id1, id2, ..., idn)
		$sql_meta2 		= "SELECT * FROM `$hpt_wppostmeta` WHERE `post_id` IN $myIds";
		//echo $sql_meta2;
	} elseif (count($row2) == 1) {
		// wir wollen hier nur einen Eintrag gegen das SQL übergeben und abprüfen
		$sql_meta2 		= "SELECT * FROM `$hpt_wppostmeta` WHERE `post_id` = '".$row2[0]['ID']."'";
		//echo $sql_meta2;
	} else {
		$sql_meta2 		= "SELECT 1"; // Dummy Request
	} // if (count($row2) > 1){
	$row42 				= $sql_obj->sql_fetchAll($hpt_connection, $sql_meta2);

foreach ($row42 as $k1 => $v1){
	foreach ($v1 as $k => $v){
		// prüfe mit einem definierten Regex, ob wir ein Bild als meta_value bekommen haben
		$ext = strtolower(pathinfo($v, PATHINFO_EXTENSION)); // Using strtolower to overcome case sensitive
		if ($k=="meta_value" and in_array($ext, $supported_image)){
			for ($i=2;$i<10;$i++){
				$mein_bild_weburl = $hpt_bilderurl . "$i/" . $v;	// Wandele den Bildpfad in eine URL um, die man abfragen bzw. abholen kann
				if (checkRemoteFile($mein_bild_weburl) === true){
						break;
				}
			}
			//$row42[$k1][$k] = $v_new;				// schreibe die neuen Bild URLS an die jeweilige Stelle im Ergebnis Array
		}; // if ($k=="ID"){
	}; // foreach ($v1 as $k => $v){
}; // foreach ($row2 as $k1 => $v1){

	$sql_meta 			= "SELECT * FROM `$hpt_wppostmeta` WHERE `post_id` = '$post_id' AND (`meta_key` = 'cp_vhb' OR `meta_key` = 'cp_price')";
	$row4 				= $sql_obj->sql_fetchAll($hpt_connection, $sql_meta);
	#pretty_print($row4);

	foreach ($row4 as $k1 => $v1){
		$dyna_variable		= "";
		foreach ($v1 as $key => $val){
			switch($key){
				case "meta_key":
					$dyna_variable = $val;
				default:
					1;
			};
			switch($dyna_variable){
				case "cp_price":
					$mein_post_preis = $val;
					break;
				case "cp_vhb":
					$mein_post_vhb .= $val.",";
					break;
				default:
					1;
			}; // switch($key){
		}; // foreach ($v1 as $k => $v){
		$dyna_variable		= "";
	}; // foreach ($row2 as $k1 => $v1){

	$mein_post_vhb 					= str_replace("cp_vhb,","",$mein_post_vhb);
	$mein_post_vhb 					= mb_substr($mein_post_vhb, 0, -1);

	$result 								= preg_split('/(?<=[.?!;:])\s+/', $mein_post_content, -1, PREG_SPLIT_NO_EMPTY);
	$mein_post_content_kurz = $result[0];
	//$mein_post_titel 				= mb_substr($mein_post_titel, 0, 32) . "...";
	$truncated_mein_post_titel = (strlen($mein_post_titel) > 32) ? substr($mein_post_titel, 0, 32) . '...' : $mein_post_titel;
	//$pos = strcasecmp('...', $truncated_mein_post_titel);
	if (strcasecmp('...', $truncated_mein_post_titel) != 0) {
		1;//$truncated_mein_post_titel .= "...";
	};


	$array = array(
		"article_link" 			=> $article_link,
		"mein_bild_weburl" 	=> $mein_bild_weburl,
		"mein_post_titel" 	=> $truncated_mein_post_titel,
		"mein_post_preis" 	=> $mein_post_preis
	);

	return $array;
}; // function getContentForBanner


// zeige alle post_ids an, die einen Artikel beinhalten
function get_all_posts($post_count){
	global $sql_obj;
	global $hpt_wpposts;
	global $hpt_connection;
	global $hpt_domain;

	$ret_val	= array();
	$sql_query 	= "SELECT `ID` FROM `$hpt_wpposts` WHERE `post_status` = \"publish\" AND `guid` LIKE '%$hpt_domain%' AND `guid` LIKE '%post_type=ad_listing%' ORDER BY UNIX_TIMESTAMP(`post_date`) DESC LIMIT $post_count";
	$row		= $sql_obj->sql_fetchAll($hpt_connection, $sql_query);
	foreach ($row as $key1){
		foreach ($key1 as $key){
			if (is_numeric($key)){
				array_push($ret_val, $key);
			}; // if (is_numeric($key)){
		}; // foreach ($key1 as $key){
	} // foreach ($row as $key){
	return $ret_val;
} // function get_all_posts

function checkRemoteFile($file){
	$ch 			= curl_init($file);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// $retcode >= 400 -> not found, $retcode = 200, found.
	curl_close($ch);
	if ($retcode == 200){
		return true;
	} else {
		return false;
	}
}

// Ergebnisse schön darstellen - für den DEBUG oder Entwicklungsmodus
function pretty_print($data){
	echo "<b>pretty_print/DEBUG:</b><br>";
	echo "<pre>";
	var_dump($data);
	echo "</pre>";
} // function pretty_print

exit(0);
?>
