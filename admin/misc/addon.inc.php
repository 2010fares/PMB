<?php
// +-------------------------------------------------+
//  2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: addon.inc.php,v 1.2.2.21 2013-11-07 13:56:04 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

function traite_rqt($requete="", $message="") {

	global $dbh;
	$retour="";
	$res = mysql_query($requete, $dbh) ; 
	$erreur_no = mysql_errno();
	if (!$erreur_no) {
		$retour = "Successful";
	} else {
		switch ($erreur_no) {
			case "1060":
				$retour = "Field already exists, no problem.";
				break;
			case "1061":
				$retour = "Key already exists, no problem.";
				break;
			case "1091":
				$retour = "Object already deleted, no problem.";
				break;
			default:
				$retour = "<font color=\"#FF0000\">Error may be fatal : <i>".mysql_error()."<i></font>";
				break;
			}
	}		
	return "<tr><td><font size='1'>".$message."</font></td><td><font size='1'>".$retour."</font></td></tr>";
}
echo "<table>";

/******************** AJOUTER ICI LES MODIFICATIONS *******************************/
// MB - Indexer la colonne num_renvoi_voir de la table noeuds
$rqt = "ALTER TABLE noeuds DROP INDEX i_num_renvoi_voir";
echo traite_rqt($rqt,"ALTER TABLE noeuds DROP INDEX i_num_renvoi_voir");
$rqt = "ALTER TABLE noeuds ADD INDEX i_num_renvoi_voir (num_renvoi_voir)";
echo traite_rqt($rqt,"ALTER TABLE noeuds ADD INDEX i_num_renvoi_voir (num_renvoi_voir)");
// FT - Ajout des paramètres pour forcer les tags meta pour les moteurs de recherche
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_description' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_description','','Contenu du meta tag description pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_description");
}
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_keywords' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_keywords','','Contenu du meta tag keywords pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_keywords");
}	
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_author' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_author','','Contenu du meta tag author pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_author");
}

//DG - autoriser le code HTML dans les cotes exemplaires
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='html_allow_expl_cote' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
			VALUES (0, 'pmb', 'html_allow_expl_cote', '0', 'Autoriser le code HTML dans les cotes exemplaires ? \n 0 : non \n 1', '',0) ";
	echo traite_rqt($rqt, "insert pmb_html_allow_expl_cote=0 into parametres");
}
//maj valeurs possibles pour empr_sort_rows
$rqt = "update parametres set comment_param='Colonnes qui seront disponibles pour le tri des emprunteurs. Les colonnes possibles sont : \n n: nom+prénom \n b: code-barres \n c: catégories \n g: groupes \n l: localisation \n s: statut \n cp: code postal \n v: ville \n y: année de naissance \n ab: type d\'abonnement \n #n : id des champs personnalisés' where type_param= 'empr' and sstype_param='sort_rows' ";
echo traite_rqt($rqt,"update empr_sort_rows into parametres");


// MB - Création d'une table de cache pour les cadres du portail pour accélérer l'affichage
// MB - Un TEXT pour cache_cadre_content ne suffisait pas ;-)
$rqt = "DROP TABLE IF EXISTS cms_cache_cadres";
echo traite_rqt($rqt,"DROP TABLE IF EXISTS cms_cache_cadres");
$rqt = "CREATE TABLE  cms_cache_cadres (
		cache_cadre_hash VARCHAR( 32 ) NOT NULL,
		cache_cadre_type_content VARCHAR(30) NOT NULL,
		cache_cadre_create_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
		cache_cadre_content MEDIUMTEXT NOT NULL,
		PRIMARY KEY (  cache_cadre_hash, cache_cadre_type_content )
	);";
echo traite_rqt($rqt,"CREATE TABLE cms_cache_cadres");

// AP - Ajout de la gestion de l'ordre dans le contenu éditorial
$rqt = "ALTER TABLE cms_sections ADD section_order INT UNSIGNED default 0";
echo traite_rqt($rqt,"alter table cms_sections add section_order");

$rqt = "ALTER TABLE cms_articles ADD article_order INT UNSIGNED default 0";
echo traite_rqt($rqt,"alter table cms_articles add article_order");

//DG - CSS add on en gestion
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='default_style_addon' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
		VALUES (0, 'pmb', 'default_style_addon', '', 'Ajout de styles CSS aux feuilles déjà incluses ?\n Ne mettre que le code CSS, exemple:  body {background-color: #FF0000;}', '',0) ";
	echo traite_rqt($rqt, "insert pmb_default_style_addon into parametres");
}

//MB - Augmenter la taille du libellé de groupe
$rqt = "ALTER TABLE groupe CHANGE libelle_groupe libelle_groupe VARCHAR(255) NOT NULL";
echo traite_rqt($rqt,"alter table groupe");

//AR - Ajout d'un type de cache pour un cadre
$rqt = "alter table cms_cadres add cadre_modcache varchar(255) not null default 'get_post_view'";
echo traite_rqt($rqt,"alter table cms_cadres add cadre_modcache");

//DG - Type de relation par défaut en création de périodique
$rqt = "ALTER TABLE users ADD value_deflt_relation_serial VARCHAR( 20 ) NOT NULL DEFAULT '' AFTER value_deflt_relation";
echo traite_rqt($rqt,"ALTER TABLE users ADD default value_deflt_relation_serial after value_deflt_relation");

//DG - Type de relation par défaut en création de bulletin
$rqt = "ALTER TABLE users ADD value_deflt_relation_bulletin VARCHAR( 20 ) NOT NULL DEFAULT '' AFTER value_deflt_relation_serial";
echo traite_rqt($rqt,"ALTER TABLE users ADD default value_deflt_relation_bulletin after value_deflt_relation_serial");

//DG - Type de relation par défaut en création d'article
$rqt = "ALTER TABLE users ADD value_deflt_relation_analysis VARCHAR( 20 ) NOT NULL DEFAULT '' AFTER value_deflt_relation_bulletin";
echo traite_rqt($rqt,"ALTER TABLE users ADD default value_deflt_relation_analysis after value_deflt_relation_bulletin");

//DG - Mise à jour des valeurs en fonction du type de relation par défaut en création de notice, si la valeur est vide !
if ($res = mysql_query("select userid, value_deflt_relation,value_deflt_relation_serial,value_deflt_relation_bulletin,value_deflt_relation_analysis from users")){
	while ( $row = mysql_fetch_object($res)) {
		if ($row->value_deflt_relation_serial == '') mysql_query("update users set value_deflt_relation_serial='".$row->value_deflt_relation."' where userid=".$row->userid);
		if ($row->value_deflt_relation_bulletin == '') mysql_query("update users set value_deflt_relation_bulletin='".$row->value_deflt_relation."' where userid=".$row->userid);
		if ($row->value_deflt_relation_analysis == '') mysql_query("update users set value_deflt_relation_analysis='".$row->value_deflt_relation."' where userid=".$row->userid);
	}
}

//DG - Activer le prêt court par défaut
$rqt = "ALTER TABLE users ADD deflt_short_loan_activate INT(1) UNSIGNED DEFAULT 0 NOT NULL ";
echo traite_rqt($rqt, "ALTER TABLE users ADD deflt_short_loan_activate");

//DG - Alerter l'utilisateur par mail des nouvelles inscriptions en OPAC ?
$rqt = "ALTER TABLE users ADD user_alert_subscribemail INT(1) UNSIGNED NOT NULL DEFAULT 0 after user_alert_demandesmail";
echo traite_rqt($rqt,"ALTER TABLE users add user_alert_subscribemail default 0");

//DB - Modification commentaire autolevel
$rqt = "update parametres set comment_param='0 : mode normal de recherche.\n1 : Affiche le résultat de la recherche tous les champs après calcul du niveau 1 de recherche.\n2 : Affiche directement le résultat de la recherche tous les champs sans passer par le calcul du niveau 1 de recherche.' where type_param= 'opac' and sstype_param='autolevel2' ";
echo traite_rqt($rqt,"update parameter comment for opac_autolevel2");

//AR - Ajout du paramètres pour la durée de validité du cache des cadres du potail
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'cms' and sstype_param='cache_ttl' "))==0){
	$rqt = "insert into parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
		VALUES (0, 'cms', 'cache_ttl', '1800', 'durée de vie du cache des cadres du portail (en secondes)', '',0) ";
	echo traite_rqt($rqt, "insert cms_caches_ttl into parametres");
}

//DG - Périodicité : Jour du mois
$rqt = "ALTER TABLE planificateur ADD perio_jour_mois VARCHAR( 128 ) DEFAULT '*' AFTER perio_minute";
echo traite_rqt($rqt,"ALTER TABLE planificateur ADD perio_jour_mois DEFAULT * after perio_minute");

//DG - Replanifier la tâche en cas d'échec
$rqt = "alter table taches_type add restart_on_failure int(1) UNSIGNED DEFAULT 0 NOT NULL";
echo traite_rqt($rqt,"alter table taches_type add restart_on_failure");

//DG - Alerte mail en cas d'échec de la tâche
$rqt = "alter table taches_type add alert_mail_on_failure VARCHAR(255) DEFAULT ''";
echo traite_rqt($rqt,"alter table taches_type add alert_mail_on_failure");

//DG - Préremplissage de la vignette des dépouillements
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='serial_thumbnail_url_article' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
			VALUES (0, 'pmb', 'serial_thumbnail_url_article', '0', 'Préremplissage de l\'url de la vignette des dépouillements avec l\'url de la vignette de la notice mère en catalogage des périodiques ? \n 0 : Non \n 1 : Oui', '',0) ";
	echo traite_rqt($rqt, "insert pmb_serial_thumbnail_url_article=0 into parametres");
}

//DG - Délai en millisecondes entre les mails envoyés lors d'un envoi groupé
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='mail_delay' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES(0,'pmb','mail_delay','0','Temps d\'attente en millisecondes entre chaque mail envoyé lors d\'un envoi groupé. \n 0 : Pas d\'attente', '',0)" ;
	echo traite_rqt($rqt,"insert pmb_mail_delay=0 into parametres") ;
}

//DG - Timeout cURL sur la vérifications des liens
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='curl_timeout' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES(0,'pmb','curl_timeout','5','Timeout cURL (en secondes) pour la vérification des liens', '',1)" ;
	echo traite_rqt($rqt,"insert pmb_curl_timeout=0 into parametres") ;
}
	
//DG - Autoriser la prolongation groupée pour tous les membres
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'empr' and sstype_param='allow_prolong_members_group' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'empr', 'allow_prolong_members_group', '0', 'Autoriser la prolongation groupée des adhésions des membres d\'un groupe ? \n 0 : Non \n 1 : Oui', '',0) ";
	echo traite_rqt($rqt, "insert empr_allow_prolong_members_group=0 into parametres");
}

//DB - ajout d'un index stem+lang sur la table words
$rqt = "alter table words add index i_stem_lang(stem, lang)";
echo traite_rqt($rqt, "alter table words add index i_stem_lang");

//NG - Autoindex
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'thesaurus' and sstype_param='auto_index_notice_fields' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
	VALUES (0, 'thesaurus', 'auto_index_notice_fields', '', 'Liste des champs de notice à utiliser pour l\'indexation automatique, séparés par une virgule.\nLes noms des champs sont les identifiants des champs listés dans le fichier XML pmb/notice/notice.xml\nExemple: tit1,n_resume', 'categories',0) ";
	echo traite_rqt($rqt, "insert thesaurus_auto_index_notice_fields='' into parametres");
}

//NG - Autoindex: surchage du parametrage de la recherche
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'thesaurus' and sstype_param='auto_index_search_param' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
	VALUES (0, 'thesaurus', 'auto_index_search_param', '', 'Surchage des paramètres de recherche de l\'indexation automatique./nSyntaxe: param=valeur;\n\n Listes des parametres:\nautoindex_max_up_distance,\nautoindex_max_down_distance,\nautoindex_stem_ratio,\nautoindex_see_also_ratio,\nautoindex_max_down_ratio,\nautoindex_max_up_ratio,\nautoindex_deep_ratio,\nautoindex_distance_ratio,\nmax_relevant_words,\nmax_relevant_terms', 'categories',0) ";
	echo traite_rqt($rqt, "insert thesaurus_auto_index_search_param='' into parametres");
}

//DG - Choix par défaut pour la prolongation des lecteurs
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'empr' and sstype_param='abonnement_default_debit' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param) VALUES (0, 'empr', 'abonnement_default_debit', '0', 'Choix par défaut pour la prolongation des lecteurs. \n 0 : Ne pas débiter l\'abonnement \n 1 : Débiter l\'abonnement sans la caution \n 2 : Débiter l\'abonnement et la caution') " ;
	echo traite_rqt($rqt,"insert empr_abonnement_default_debit = 0 into parametres");
}

/******************** JUSQU'ICI **************************************************/
/* PENSER à  faire +1 au paramètre $pmb_subversion_database_as_it_shouldbe dans includes/config.inc.php */
/* COMMITER les deux fichiers addon.inc.php ET config.inc.php en même temps */

echo traite_rqt("update parametres set valeur_param='".$pmb_subversion_database_as_it_shouldbe."' where type_param='pmb' and sstype_param='bdd_subversion'","Update to $pmb_subversion_database_as_it_shouldbe database subversion.");
echo "<table>";