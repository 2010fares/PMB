<?php
// +-------------------------------------------------+
// © 2002-2005 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: autoindex_record.class.php,v 1.1.2.8 2013-10-31 08:45:04 ngantier Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once("$class_path/autoindex/autoindex_document.class.php");

require_once("$class_path/marc_table.class.php");
require_once("$class_path/category.class.php");
require_once("$class_path/thesaurus.class.php");
require_once("$class_path/facette_search_opac.class.php");
require_once("$class_path/notice_doublon.class.php");

class autoindex_record extends autoindex_document {
	
	
	/**
	 * Identifiant de la notice
	 * @var integer
	 * @access protected
	 */
	protected $record_id=0;
	
	/**
	 * Liste des champs, sous-champs utiles
	 * $this->fields_list =array(
	 *   array(
	 *     'field'=>1,
	 *     'subfields' => array(
	 *       2,3
	 *     )
	 *   )
	 * @var array
	 * @access protected
	 */
	protected $fields_list = array(
								array(
										'field'=>1,
										'subfields'=>array(0),
									),
								array(
										'field'=>14,
										'subfields'=>array(0),
									)
								);
	
	
	public function __construct() {
		
		$this->collection = new autoindex_documents_collection($this->fields_list);
	
	}
	
	
	/**
	 * fonction de test
	 */
	public function test($raw_text='', $lang='fr_FR', $id_thesaurus=0) {
	
		$this->raw_text=$raw_text;
		$this->lang=$lang;
		$this->id_thesaurus=$id_thesaurus;
	}
	
	/**
	 * fonction de test
	 */
	public function process() {	
		$this->get_raw_text();
		$this->get_lang();
		$this->get_thesaurus();
		$this->find_revelants_words(); 
		$this->get_relevants_terms();
		$this->calc_total_terms_relevancy();
		$this->calc_document_terms_distances();
		$this->sort_terms();
	}
		
	/**
	 * Récupère le contenu des champs de la notice à indexer
	 *
	 * @return string
	 * @access public
	 */
	public function get_raw_text() {
		global $autoindex_txt;
		$this->raw_text=stripslashes($autoindex_txt);
		return stripslashes($autoindex_txt);
	}
	
	
	/**
	 * Récupère la langue de l'interface
	 *
	 * @return string
	 * @access public
	 */
	public function get_lang() {
		global $user_lang,$lang;
		
		if(!$user_lang)$user_lang=$lang;
		if(!$user_lang) $user_lang="fr_FR";
		
		$this->lang=$user_lang;
		return $user_lang;
	}
	
	
	/**
	 * Récupère l'identifiant du thésaurus à utiliser pour la recherche de termes.
	 *
	 * @return integer
	 * @access public
	 */
	public function get_thesaurus() {
		global $id_thes;
		$this->id_thesaurus=$id_thes;
		return $id_thes;
	}	
	
	public function get_form() {		
		global $charset;
		global $msg;
		global $caller,$thesaurus_auto_index_notice_fields,$lang,$include_path,$search_type,$user_lang;
		
		$tpl_index_auto="";
		if ($caller=='notice' && $thesaurus_auto_index_notice_fields) {
			$fields=explode(",",$thesaurus_auto_index_notice_fields);
		
			$notice_fields=new notice_doublon();
			$tpl_field="";
			$tpl_field_type="";
			$tpl_field_name="";
			foreach($fields as $field){
				if($tpl_field){
					$tpl_field.=",";
					$tpl_field_type.=",";
					$tpl_field_name.=",";
				}
				if($notice_fields->fields[$field]['html']){
					// champ de notice
					$tpl_field.="'".$notice_fields->fields[$field]['html']."'";
					$tpl_field_type.="'".$notice_fields->fields[$field]['type']."'";
					$tpl_field_name.="'".$field."'";
				}else{
					// champ perso
					$tpl_field.="'".$field."'";
					$tpl_field_type.="''";
					$tpl_field_name.="'".$field."'";
				}	
			}	
			if(!$user_lang)$user_lang=$lang;
			if(!$user_lang) $user_lang="fr_FR";
			
			$langues = new XMLlist("$include_path/messages/languages.xml");
			$langues->analyser();
			$clang = $langues->table;
			if($search_type!="autoindex")
				$display="style='display:none'";
			$combo = "
				<div id='autoindex_selector_lang' $display>".$msg["autoindex_selector_lang"].
				"<select name='user_lang' id='user_lang' class='saisie-20em'  onChange=\"autoindex_get_index();this.form.submit();\">";
			while(list($cle, $value) = each($clang)) {
				// arabe seulement si on est en utf-8
				if (($charset != 'utf-8' and $user_lang != 'ar') or ($charset == 'utf-8')) {
					if(strcmp($cle, $user_lang) != 0) $combo .= "<option value='$cle'>$value ($cle)</option>";
					else $combo .= "<option value='$cle' selected>$value ($cle)</option>";
				}
			}
			$combo .= "</select></div>";
		
			$tpl_index_auto="
			<script type='text/javascript'>
				var fields_index_auto=new Array($tpl_field);
				var fields_index_auto_type=new Array($tpl_field_type);
				var fields_index_auto_name=new Array($tpl_field_name);
				//var fields_index_auto_values=new Array();
					
				function autoindex_get_index(){
					if(!parent.window.opener.document.forms['$caller']) return;
					//lecture des champs de la notice
					var txt='';
					for(var i=0; i<fields_index_auto.length; i++){						
						if( parent.window.opener.document.forms['$caller'].elements[fields_index_auto[i]]){
							//fields_index_auto_values[fields_index_auto_name[i]]=parent.window.opener.document.forms['$caller'].elements[fields_index_auto[i]].value;
							//fields_index_auto_values[fields_index_auto_name[i]] = fields_index_auto_values[fields_index_auto_name[i]].replace(/\\n/g, '<br />');
							txt+=parent.window.opener.document.forms['$caller'].elements[fields_index_auto[i]].value + ' ';
						
						}else{
							// field non présent dans le formulaire 
						}
					}
					
					document.getElementById('autoindex_txt').value=txt;
					//alert ('txt='+document.getElementById('autoindex_txt').value);
					//pmb_serialize(fields_index_auto_values);
					parent.document.getElementsByTagName('frameset')[0].rows = '' ;
				}
				
				
			</script>
			&nbsp;
			<input type='radio' value='autoindex' name='search_type' !!autoindex_checked!! onClick=\"autoindex_get_index();this.form.submit();\" />&nbsp;".$msg["autoindex_selector_search"]."
			<input type='hidden' value='' name='autoindex_txt' id='autoindex_txt'/>
			<br />
			$combo
			";			
		}
		return $tpl_index_auto;
	}
		
	function index_list(){
		global $charset;
		global $categ_browser_autoindex;
		global $thesaurus_mode_pmb;
		global $include_path,$caller;	
		global $msg;

		$this->process();
		
		$categ_list=$this->terms;
		
		$browser_content="<h3>".$msg["autoindex_selector_title"]."</h3>";	
		
		foreach($this->terms as $categ_obj){
			if($categ_obj->see)$categ_id=$categ_obj->see;
			else $categ_id=$categ_obj->id;
			$tcateg =  new category($categ_id);
			$browser_content .= "<tr><td>";
			if($id_thes == -1 && $thesaurus_mode_pmb){
				$display = '['.htmlentities($tcateg->thes->libelle_thesaurus,ENT_QUOTES, $charset).']';
			} else {
				$display = '';
			}
			if($tcateg->voir_id) {
				$tcateg_voir = new category($tcateg->voir_id);
				$display .= "$tcateg->libelle -&gt;<i>".$tcateg_voir->catalog_form."@</i>";
				$id_=$tcateg->voir_id;
				if($libelle_partiel){
					$libelle_=$tcateg_voir->libelle;
				}else{
					$libelle_=$tcateg_voir->catalog_form;
				}
			} else {
				$id_=$tcateg->id;
				if($libelle_partiel){
					$libelle_=$tcateg->libelle;
				}else{
					$libelle_=$tcateg->catalog_form;
				}
				$display .= $tcateg->libelle;
			}
			if($tcateg->has_child) {
				$browser_content .= "<a href='$base_url".$tcateg->id."&id2=".$tcateg->id.'&id_thes='.$tcateg->thes->id_thesaurus."'>";//On mets le bon identifiant de thésaurus
				$browser_content .= "<img src='$base_path/images/folderclosed.gif' hspace='3' border='0'/></a>";
			} else {
				$browser_content .= "<img src='$base_path/images/doc.gif' hspace='3' border='0'/>";
			}
			if ($tcateg->commentaire) {
				$zoom_comment = "<div id='zoom_comment".$tcateg->id."' style='border: solid 2px #555555; background-color: #FFFFFF; position: absolute; display:none; z-index: 2000;'>".htmlentities($tcateg->commentaire,ENT_QUOTES, $charset)."</div>" ;
				$java_comment = " onmouseover=\"z=document.getElementById('zoom_comment".$tcateg->id."'); z.style.display=''; \" onmouseout=\"z=document.getElementById('zoom_comment".$tcateg->id."'); z.style.display='none'; \"" ;
			} else {
				$zoom_comment = "" ;
				$java_comment = "" ;
			}
			if ($thesaurus_mode_pmb ) $nom_tesaurus='['.$tcateg->thes->getLibelle().'] ' ;
			else $nom_tesaurus='' ;
	
			$browser_content .= "<a href='#' $java_comment onclick=\"set_parent('$caller', '$id_', '".htmlentities(addslashes($nom_tesaurus.$libelle_),ENT_QUOTES, $charset)."','$callback','".$tcateg->thes->id_thesaurus."')\">";
			$browser_content .= $display;
			$browser_content .= "</a>$zoom_comment\n";
			$browser_content .= "</td></tr>";
			if($tpl_insert_all_index){
				$tpl_insert_all_index.=",";
				$tpl_insert_all_index_name.=",";
			}
			$tpl_insert_all_index.=$id_;
			$tpl_insert_all_index_name.="'".htmlentities(addslashes($nom_tesaurus.$libelle_),ENT_QUOTES, $charset)."'";
					
		}
		$categ_browser_autoindex = str_replace('!!browser_content!!', $browser_content, $categ_browser_autoindex);
		$categ_browser_autoindex = str_replace('!!base_url!!', $base_url, $categ_browser_autoindex);
		
		if(count($this->terms))
			$categ_browser_autoindex.="
			<script type='text/javascript'>
				function insert_all_index(){
					var categs=new Array($tpl_insert_all_index);
					var categs_name=new Array($tpl_insert_all_index_name);
					for(var i=0; i<categs.length; i++){
						set_parent('$caller', categs[i], categs_name[i],'','1');
					}
				}
			</script>
			<input type='button' class='bouton_small' value='".$msg["autoindex_selector_add_all"]."' onclick='insert_all_index()' />
			";
		
		return $categ_browser_autoindex;
	}
	
	
}

