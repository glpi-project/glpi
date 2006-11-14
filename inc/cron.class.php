<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Adaptation du fichier cron.php de SPIP Merci à leurs auteurs
// Purpose of file: cron class
// ----------------------------------------------------------------------
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt                       *
 \***************************************************************************/


// --------------------------
// Gestion des taches de fond
// --------------------------

// Deux difficultes:
// - la plupart des hebergeurs ne fournissent pas le Cron d'Unix
// - les scripts usuels standard sont limites a 30 secondes

// Solution:
// les scripts usuels les plus brefs, en plus de livrer la page demandee,
// s'achevent  par un appel a la fonction cron.
// Celle-ci prend dans la liste des taches a effectuer la plus prioritaire.
// Une seule tache est executee pour eviter la guillotine des 30 secondes.
// Une fonction executant une tache doit retourner un nombre:
// - nul, si la tache n'a pas a etre effecutee -> ex il n'y a pas d'échéance de  contrat à notifier
// - positif, si la tache a ete effectuee
// - negatif, si la tache doit etre poursuivie ou recommencee
// Elle recoit en argument la date de la derniere execution de la tache.

// On peut appeler cron avec d'autres taches (pour etendre GLPI)
// specifiee par des fonctions respectant le protocole ci-dessus
// On peut modifier la frequence de chaque tache et leur ordre d'analyse
// en modifiant les variables ci-dessous.

//----------

// Les taches sont dans un array ('nom de la tache' => periodicite)
// Cette fonction execute la tache la plus urgente (celle dont la date
// de derniere execution + la periodicite est minimale) sous reserve que
// le serveur MySQL soit actif.
// La date de la derniere intervention est donnee par un fichier homonyme,
// de suffixe ".lock", modifie a chaque intervention et des le debut
// de celle-ci afin qu'un processus concurrent ne la demarre pas aussi.
// Les taches les plus longues sont tronconnees, ce qui impose d'antidater
// le fichier de verrouillage (avec la valeur absolue du code de retour).
// La fonction executant la tache est un homonyme de prefixe "cron_"
// Le fichier homonyme de prefixe "inc_"
// est automatiquement charge si besoin, et est supposee la definir si ce
// n'est fait ici.

// touch : verifie si un fichier existe et n'est pas vieux (duree en s)
// et le cas echeant le touch() ; renvoie true si la condition est verifiee
// et fait touch() sauf si ca n'est pas souhaite
// (regle aussi le probleme des droits sur les fichiers touch())



class Cron {


	var $taches=array(); 

	function Cron($taches=array()){
		global $cfg_glpi;
		if(count($taches)>0){
			$this->taches=$taches;
		}else{
			// la cle est la tache, la valeur le temps minimal, en secondes, entre
			// deux memes taches ex $this->taches["test"]=30;

			if ($cfg_glpi["ocs_mode"]){
				// Every 5 mns
				$this->taches["ocsng"]=300;
			}
			// Mailing alerts if mailing activated
			if ($cfg_glpi["mailing"]){
				if ($cfg_glpi["cartridges_alert"]>0)
					$this->taches["cartridge"]=DAY_TIMESTAMP;
				if ($cfg_glpi["consumables_alert"]>0)
					$this->taches["consumable"]=DAY_TIMESTAMP;
			}
			$this->taches["contract"]=DAY_TIMESTAMP;
			$this->taches["infocom"]=DAY_TIMESTAMP;
		
			// Auto update check
			if ($cfg_glpi["auto_update_check"]>0)
				$this->taches["check_update"]=$cfg_glpi["auto_update_check"]*DAY_TIMESTAMP;
		}
	}

	function launch() {

		global $cfg_glpi, $phproot,$lang;

		$t = time();

		// Quelle est la tache la plus urgente ?
		$tache = FALSE;
		$tmin = $t;


		foreach ($this->taches as $nom => $periode) {
			$lock = $cfg_glpi["doc_dir"].'/_cron/' . $nom . '.lock';
			$date_lock = @filemtime($lock);

			if ($date_lock + $periode < $tmin) {
				$tmin = $date_lock + $periode;
				$tache = $nom;
				$last = $date_lock;

			}
			// debug : si la date du fichier est superieure a l'heure actuelle,
			// c'est que le serveur a (ou a eu) des problemes de reglage horaire
			// qui peuvent mettre en peril les taches cron : signaler dans le log
			// (On laisse toutefois flotter sur une heure, pas la peine de s'exciter
			// pour si peu)
			//		else if ($date_lock > $t + HOUR_TIMESTAMP)
			//echo "Erreur de date du fichier $lock : $date_lock > $t !";
		}
		if (!$tache) return;

		// Interdire des taches paralleles, de maniere a eviter toute concurrence
		// entre deux appli partageant la meme base, ainsi que toute interaction
		// bizarre entre des taches differentes
		// Ne rien lancer non plus si serveur naze evidemment

		if (!$this->get_lock('cron')) {
			//echo "tache $tache: pas de lock cron";
			return;
		}

		// Un autre lock dans _DIR_SESSIONS, pour plus de securite
		$lock = $cfg_glpi["doc_dir"].'/_cron/'. $tache . '.lock';
		if ($this->touch($lock, $this->taches[$tache])) {
			// preparer la tache
			$this->timer('tache');

			$fonction = 'cron_' . $tache;

			$fct_trouve=false;
			if (!function_exists($fonction)){
				// pas trouvé de fonction -> inclusion de la fonction 
				if(file_exists($phproot.'/inc/'.$tache.'.function.php')) include_once($phproot.'/inc/'.$tache.'.function.php');
				if(file_exists($phproot.'/inc/'.$tache.'.class.php')) include_once($phproot.'/inc/'.$tache.'.class.php');

			} else { $fct_trouve=true;}

			if ($fct_trouve||function_exists($fonction)){
				// la fonction a été inclus ou la fonction existe
				// l'appeler
				$code_de_retour = $fonction($last);

				// si la tache a eu un effet : log
				if ($code_de_retour) {
					//echo "cron: $tache (" . $this->timer('tache') . ")";
					// eventuellement modifier la date du fichier

					if ($code_de_retour < 0) @touch($lock, (0 - $code_de_retour));
					else // Log Event 
						logEvent("-1", "system", 3, "cron", $tache." (" . $this->timer('tache') . ") ".$lang["log"][45] );
				}# else log("cron $tache a reprendre");
			} else {echo "Erreur fonction manquante";}




		}

		// relacher le lock mysql
		$this->release_lock('cron');
	}


	function touch($fichier, $duree=0, $touch=true) {
		if (!($exists = @is_readable($fichier))
				|| ($duree == 0)
				|| (@filemtime($fichier) < time() - $duree)) {
			if ($touch) {
				if (!@touch($fichier)) { @unlink($fichier); @touch($fichier); };
				if (!$exists) @chmod($fichier, 0666);
			}
			return true;
		}
		return false;
	}




	//
	// timer : on l'appelle deux fois et on a la difference, affichable
	//
	function timer($t='rien') {
		static $time;
		$a=time(); $b=microtime();

		if (isset($time[$t])) {
			$p = $a + $b - $time[$t];
			unset($time[$t]);
			return sprintf("%.2fs", $p);
		} else
			$time[$t] = $a + $b;
	}



	//
	// Poser un verrou local 
	//
	function get_lock($nom, $timeout = 0) {
		global $db, $cfg_glpi;

		// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
		define('_LOCK_TIME', intval(time()/HOUR_TIMESTAMP-316982));
		$nom .= _LOCK_TIME;

		$nom = addslashes($nom);
		$query = "SELECT GET_LOCK('$nom', $timeout)";
		$result = $db->query($query);
		list($lock_ok) = $db->fetch_array($result);

		if (!$lock_ok) log("pas de lock sql pour $nom");
		return $lock_ok;
	}


	function release_lock($nom) {
		global $db,$cfg_glpi;

		$nom .= _LOCK_TIME;

		$nom = addslashes($nom);
		$query = "SELECT RELEASE_LOCK('$nom')";
		$result = $db->query($query);
	}





}

class Alert extends CommonDBTM {

	function Alert () {
		$this->table="glpi_alerts";
		$this->type=0;
	}
	function getFromDBForDevice ($type,$ID) {

		// Make new database object and fill variables
		global $db;
		if (empty($ID)) return false;

		$query = "SELECT * FROM ".$this->table." WHERE (device_type='$type' AND FK_device = '$ID')";

		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1){
				$this->fields = $db->fetch_assoc($result);
				return true;
			} else return false;
		} else {
			return false;
		}
	}

}

?>
