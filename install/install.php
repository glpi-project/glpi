<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


//Ce script g��e ses propres messages d'erreur 
//Pas besoin des warnings de PHP
error_reporting(0);
include ("_relpos.php");
include ($phproot . "/config/based_config.php");

session_save_path($cfg_glpi["doc_dir"]."/_sessions");

$cfg_glpi["debug"]=0;
//Print a correct  Html header for application
function header_html($etape)
{
	global $HTMLRel;

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html>";
	echo "<head>";
	echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
	echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\"> ";
	echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\"> ";
	echo "<meta http-equiv=\"Content-Language\" content=\"fr\"> ";
	echo "<meta name=\"generator\" content=\"\">";
	echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\">";
	echo "<title>Setup GLPI</title>";
	// CSS 
	echo "<link rel='stylesheet'  href='".$HTMLRel."css/style_install.css' type='text/css' media='screen' >";

	echo "</head>";
	echo "<body>";
	echo "<div id='principal'>";
	echo "<div id='bloc'>";
	echo "<div class='haut'></div>";
	echo "<h2>GLPI SETUP</h2>";
	echo "<br/><h3>". $etape ."</h3>";
}

//Display a great footer.
function footer_html()
{
	echo "<div class='bas'></div></div></div></body></html>";
}

// choose language

function choose_language()
{

	echo "<form action=\"install/install.php\" method=\"post\">";
	echo "<p style='text-align:center;'><label>Select your language </label><select name=\"language\">";
	echo "<option value=\"fr_FR\">Fran&ccedil;ais (fr_FR)</option>";
	echo "<option value=\"en_GB\">English (en_GB)</option>";
	echo "<option value=\"de_DE\">Deutch (de_DE)</option>";
	echo "<option value=\"it_IT\">Italiano (it_IT)</option>";
	echo "<option value=\"es_ES\">Espanol (es_ES)</option>";
	echo "<option value=\"es_AR\">Argentino (es_AR)</option>";
	echo "<option value=\"pt_PT\">Portuguese (pt_PT)</option>";
	echo "<option value=\"pt_BR\">Brazilian (pt_BR)</option>";
	echo "<option value=\"nl_NL\">Dutch (nl_NL)</option>";
	echo "<option value=\"hu_HU\">Hungarian (hu_HU)</option>";
	echo "<option value=\"bg_BG\">Bulgarian (bg_BG)</option>";
	echo "<option value=\"po_PO\">Polish (po_PO)</option>";
	echo "<option value=\"ro_RO\">Romanian (ro_RO)</option>";
	echo "<option value=\"ru_RU\">Russian (ru_RU)</option>";
	echo "<option value=\"zh_CN\">Simplified Chinese (zh_CN)</option>";
	echo "<option value=\"sv_SE\">Swedish (sv_SE)</option>";
	echo "</select></p>"; 
	echo "";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"lang_select\" /><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"OK\" /></p>";
	echo "</form>";
}

// load language

function loadLang($language) {

	unset($lang);
	global $lang;
	include ("_relpos.php");
	$file = $phproot ."/locales/".$language.".php";
	include($file);
}

function acceptLicence() {

	global $lang;

	echo "<div align='center'>";
	echo "<textarea id='license' cols='80' rows='10' readonly='readonly'>";
	echo "                        GNU GENERAL PUBLIC LICENSE
		Version 2, June 1991

		Copyright (C) 1989, 1991 Free Software Foundation, Inc.
		51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
		Everyone is permitted to copy and distribute verbatim copies
		of this license document, but changing it is not allowed.

		Preamble

		The licenses for most software are designed to take away your
		freedom to share and change it.  By contrast, the GNU General Public
		License is intended to guarantee your freedom to share and change free
		software--to make sure the software is free for all its users.  This
		General Public License applies to most of the Free Software
		Foundation's software and to any other program whose authors commit to
		using it.  (Some other Free Software Foundation software is covered by
				the GNU Library General Public License instead.)  You can apply it to
		your programs, too.

		When we speak of free software, we are referring to freedom, not
		price.  Our General Public Licenses are designed to make sure that you
		have the freedom to distribute copies of free software (and charge for
				this service if you wish), that you receive source code or can get it
		if you want it, that you can change the software or use pieces of it
			in new free programs; and that you know you can do these things.

				To protect your rights, we need to make restrictions that forbid
				anyone to deny you these rights or to ask you to surrender the rights.
				These restrictions translate to certain responsibilities for you if you
				distribute copies of the software, or if you modify it.

				For example, if you distribute copies of such a program, whether
				gratis or for a fee, you must give the recipients all the rights that
				you have.  You must make sure that they, too, receive or can get the
				source code.  And you must show them these terms so they know their
				rights.

				We protect your rights with two steps: (1) copyright the software, and
				(2) offer you this license which gives you legal permission to copy,
				distribute and/or modify the software.

					Also, for each author's protection and ours, we want to make certain
					that everyone understands that there is no warranty for this free
					software.  If the software is modified by someone else and passed on, we
					want its recipients to know that what they have is not the original, so
					that any problems introduced by others will not reflect on the original
					authors' reputations.

					Finally, any free program is threatened constantly by software
					patents.  We wish to avoid the danger that redistributors of a free
					program will individually obtain patent licenses, in effect making the
					program proprietary.  To prevent this, we have made it clear that any
					patent must be licensed for everyone's free use or not licensed at all.

					The precise terms and conditions for copying, distribution and
					modification follow.
					
					GNU GENERAL PUBLIC LICENSE
					TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

					0. This License applies to any program or other work which contains
					a notice placed by the copyright holder saying it may be distributed
					under the terms of this General Public License.  The \"Program\", below,
				refers to any such program or work, and a \"work based on the Program\"
					means either the Program or any derivative work under copyright law:
					that is to say, a work containing the Program or a portion of it,
				either verbatim or with modifications and/or translated into another
					language.  (Hereinafter, translation is included without limitation in
							the term \"modification\".)  Each licensee is addressed as \"you\".

					Activities other than copying, distribution and modification are not
					covered by this License; they are outside its scope.  The act of
					running the Program is not restricted, and the output from the Program
					is covered only if its contents constitute a work based on the
					Program (independent of having been made by running the Program).
					Whether that is true depends on what the Program does.

					1. You may copy and distribute verbatim copies of the Program's
					source code as you receive it, in any medium, provided that you
					conspicuously and appropriately publish on each copy an appropriate
					copyright notice and disclaimer of warranty; keep intact all the
					notices that refer to this License and to the absence of any warranty;
	and give any other recipients of the Program a copy of this License
		along with the Program.

		You may charge a fee for the physical act of transferring a copy, and
		you may at your option offer warranty protection in exchange for a fee.

		2. You may modify your copy or copies of the Program or any portion
		of it, thus forming a work based on the Program, and copy and
		distribute such modifications or work under the terms of Section 1
		above, provided that you also meet all of these conditions:

		a) You must cause the modified files to carry prominent notices
		stating that you changed the files and the date of any change.

		b) You must cause any work that you distribute or publish, that in
		whole or in part contains or is derived from the Program or any
		part thereof, to be licensed as a whole at no charge to all third
		parties under the terms of this License.

		c) If the modified program normally reads commands interactively
		when run, you must cause it, when started running for such
		interactive use in the most ordinary way, to print or display an
		announcement including an appropriate copyright notice and a
		notice that there is no warranty (or else, saying that you provide
				a warranty) and that users may redistribute the program under
		these conditions, and telling the user how to view a copy of this
		License.  (Exception: if the Program itself is interactive but
				does not normally print such an announcement, your work based on
				the Program is not required to print an announcement.)
		
		These requirements apply to the modified work as a whole.  If
		identifiable sections of that work are not derived from the Program,
			     and can be reasonably considered independent and separate works in
				     themselves, then this License, and its terms, do not apply to those
				     sections when you distribute them as separate works.  But when you
				     distribute the same sections as part of a whole which is a work based
				     on the Program, the distribution of the whole must be on the terms of
				     this License, whose permissions for other licensees extend to the
				     entire whole, and thus to each and every part regardless of who wrote it.

				     Thus, it is not the intent of this section to claim rights or contest
				     your rights to work written entirely by you; rather, the intent is to
				     exercise the right to control the distribution of derivative or
				     collective works based on the Program.

				     In addition, mere aggregation of another work not based on the Program
				     with the Program (or with a work based on the Program) on a volume of
				     a storage or distribution medium does not bring the other work under
				     the scope of this License.

				     3. You may copy and distribute the Program (or a work based on it,
						     under Section 2) in object code or executable form under the terms of
				     Sections 1 and 2 above provided that you also do one of the following:

				     a) Accompany it with the complete corresponding machine-readable
				     source code, which must be distributed under the terms of Sections
				     1 and 2 above on a medium customarily used for software interchange; or,

			     b) Accompany it with a written offer, valid for at least three
				     years, to give any third party, for a charge no more than your
				     cost of physically performing source distribution, a complete
				     machine-readable copy of the corresponding source code, to be
				     distributed under the terms of Sections 1 and 2 above on a medium
				     customarily used for software interchange; or,

			     c) Accompany it with the information you received as to the offer
				     to distribute corresponding source code.  (This alternative is
						     allowed only for noncommercial distribution and only if you
						     received the program in object code or executable form with such
						     an offer, in accord with Subsection b above.)

				     The source code for a work means the preferred form of the work for
				     making modifications to it.  For an executable work, complete source
				     code means all the source code for all modules it contains, plus any
				     associated interface definition files, plus the scripts used to
				     control compilation and installation of the executable.  However, as a
				     special exception, the source code distributed need not include
				     anything that is normally distributed (in either source or binary
						     form) with the major components (compiler, kernel, and so on) of the
				     operating system on which the executable runs, unless that component
				     itself accompanies the executable.

				     If distribution of executable or object code is made by offering
				     access to copy from a designated place, then offering equivalent
				     access to copy the source code from the same place counts as
				     distribution of the source code, even though third parties are not
				     compelled to copy the source along with the object code.
				     
				     4. You may not copy, modify, sublicense, or distribute the Program
				     except as expressly provided under this License.  Any attempt
				     otherwise to copy, modify, sublicense or distribute the Program is
				     void, and will automatically terminate your rights under this License.
				     However, parties who have received copies, or rights, from you under
				     this License will not have their licenses terminated so long as such
				     parties remain in full compliance.

				     5. You are not required to accept this License, since you have not
				     signed it.  However, nothing else grants you permission to modify or
				     distribute the Program or its derivative works.  These actions are
				     prohibited by law if you do not accept this License.  Therefore, by
				     modifying or distributing the Program (or any work based on the
						     Program), you indicate your acceptance of this License to do so, and
				     all its terms and conditions for copying, distributing or modifying
				     the Program or works based on it.

				     6. Each time you redistribute the Program (or any work based on the
						     Program), the recipient automatically receives a license from the
				     original licensor to copy, distribute or modify the Program subject to
				     these terms and conditions.  You may not impose any further
				     restrictions on the recipients' exercise of the rights granted herein.
				     You are not responsible for enforcing compliance by third parties to
				     this License.

				     7. If, as a consequence of a court judgment or allegation of patent
				     infringement or for any other reason (not limited to patent issues),
			     conditions are imposed on you (whether by court order, agreement or
					     otherwise) that contradict the conditions of this License, they do not
				     excuse you from the conditions of this License.  If you cannot
				     distribute so as to satisfy simultaneously your obligations under this
				     License and any other pertinent obligations, then as a consequence you
				     may not distribute the Program at all.  For example, if a patent
				     license would not permit royalty-free redistribution of the Program by
				     all those who receive copies directly or indirectly through you, then
				     the only way you could satisfy both it and this License would be to
				     refrain entirely from distribution of the Program.

				     If any portion of this section is held invalid or unenforceable under
				     any particular circumstance, the balance of the section is intended to
				     apply and the section as a whole is intended to apply in other
				     circumstances.

				     It is not the purpose of this section to induce you to infringe any
				     patents or other property right claims or to contest validity of any
				     such claims; this section has the sole purpose of protecting the
				     integrity of the free software distribution system, which is
				     implemented by public license practices.  Many people have made
				     generous contributions to the wide range of software distributed
				     through that system in reliance on consistent application of that
				     system; it is up to the author/donor to decide if he or she is willing
				     to distribute software through any other system and a licensee cannot
				     impose that choice.

				     This section is intended to make thoroughly clear what is believed to
				     be a consequence of the rest of this License.
				     
				     8. If the distribution and/or use of the Program is restricted in
				     certain countries either by patents or by copyrighted interfaces, the
				     original copyright holder who places the Program under this License
				     may add an explicit geographical distribution limitation excluding
				     those countries, so that distribution is permitted only in or among
				     countries not thus excluded.  In such case, this License incorporates
				     the limitation as if written in the body of this License.

				     9. The Free Software Foundation may publish revised and/or new versions
				     of the General Public License from time to time.  Such new versions will
				     be similar in spirit to the present version, but may differ in detail to
				     address new problems or concerns.

				     Each version is given a distinguishing version number.  If the Program
				     specifies a version number of this License which applies to it and \"any
				     later version\", you have the option of following the terms and conditions
				     either of that version or of any later version published by the Free
				     Software Foundation.  If the Program does not specify a version number of
				     this License, you may choose any version ever published by the Free Software
				     Foundation.

				     10. If you wish to incorporate parts of the Program into other free
				     programs whose distribution conditions are different, write to the author
				     to ask for permission.  For software which is copyrighted by the Free
				     Software Foundation, write to the Free Software Foundation; we sometimes
				     make exceptions for this.  Our decision will be guided by the two goals
				     of preserving the free status of all derivatives of our free software and
				     of promoting the sharing and reuse of software generally.

				     NO WARRANTY

				     11. BECAUSE THE PROGRAM IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
				     FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN
				     OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
				     PROVIDE THE PROGRAM \"AS IS\" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED
				     OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
				     MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS
				     TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE
				     PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING,
			     REPAIR OR CORRECTION.

				     12. IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
				     WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
				     REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES,
			     INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING
				     OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED
						     TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY
						     YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER
						     PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE
				     POSSIBILITY OF SUCH DAMAGES.

				     END OF TERMS AND CONDITIONS
				     
				     How to Apply These Terms to Your New Programs

				     If you develop a new program, and you want it to be of the greatest
				     possible use to the public, the best way to achieve this is to make it
				     free software which everyone can redistribute and change under these terms.

				     To do so, attach the following notices to the program.  It is safest
				     to attach them to the start of each source file to most effectively
				     convey the exclusion of warranty; and each file should have at least
				     the \"copyright\" line and a pointer to where the full notice is found.

				     <one line to give the program's name and a brief idea of what it does.>
				     Copyright (C) <year>  <name of author>

				     This program is free software; you can redistribute it and/or modify
				     it under the terms of the GNU General Public License as published by
				     the Free Software Foundation; either version 2 of the License, or
				     (at your option) any later version.

				     This program is distributed in the hope that it will be useful,
			     but WITHOUT ANY WARRANTY; without even the implied warranty of
				     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
				     GNU General Public License for more details.

				     You should have received a copy of the GNU General Public License
				     along with this program; if not, write to the Free Software
				     Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


				     Also add information on how to contact you by electronic and paper mail.

				     If the program is interactive, make it output a short notice like this
				     when it starts in an interactive mode:

				     Gnomovision version 69, Copyright (C) year name of author
				     Gnomovision comes with ABSOLUTELY NO WARRANTY; for details type `show w'.
				     This is free software, and you are welcome to redistribute it
				     under certain conditions; type `show c' for details.

				     The hypothetical commands `show w' and `show c' should show the appropriate
				     parts of the General Public License.  Of course, the commands you use may
				     be called something other than `show w' and `show c'; they could even be
				     mouse-clicks or menu items--whatever suits your program.

				     You should also get your employer (if you work as a programmer) or your
				     school, if any, to sign a \"copyright disclaimer\" for the program, if
				     necessary.  Here is a sample; alter the names:

				     Yoyodyne, Inc., hereby disclaims all copyright interest in the program
				     `Gnomovision' (which makes passes at compilers) written by James Hacker.

				     <signature of Ty Coon>, 1 April 1989
				     Ty Coon, President of Vice

				     This General Public License does not permit incorporating your program into
				     proprietary programs.  If your program is a subroutine library, you may
				     consider it more useful to permit linking proprietary applications with the
				     library.  If this is what you want to do, use the GNU Library General
				     Public License instead of this License.
				     ";
	echo "</textarea>";

	echo "<form action=\"install.php\" method=\"post\">";
	echo "<p>";
	echo " <label class=\"block\" for=\"agree\"><input type=\"radio\" name=\"install\" value=\"Licence\" />";
	echo $lang["install"][93];
	echo " </label></p>";


	echo "<p>";
	echo "<label class=\"block\" for=\"disagree\"><input type=\"radio\" name=\"install\" value=\"lang_select\" checked=\"checked\" />";
	echo $lang["install"][94];
	echo " </label>";
	echo "<p><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\"  /></p>";
	echo "</p></form>";
	echo "</div>";
}



//confirm install form
function step0()
{

	global $lang;
	echo "<h3>".$lang["install"][0]."</h3>";
	echo "<p>".$lang["install"][1]."</p>";
	echo "<p> ".$lang["install"][2]."</p>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"no\" />";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
	echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][3]."\" /></p>";
	echo "</form>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
	echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][4]."\" /></p>";
	echo "</form>";
}

//Step 1 checking some compatibilty issue and some write tests.
function step1($update)
{
	global $lang,$cfg_glpi,$phproot;

	$error = 0;
	echo "<h3>".$lang["install"][5]."</h3>";
	echo "<table>";
	echo "<tr><th>".$lang["install"][6]."</th><th >".$lang["install"][7]."</th></tr>";
	// Parser test
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][8]."</b></td>";
	// PHP Version  - exclude PHP3
	if (substr(phpversion(),0,1) == "3") {
		$error = 2;
		echo "<td  class='red'>".$lang["install"][9]."</a>.\n</td>";
	}
	elseif (substr(phpversion(),0,3) == "4.0" and ereg("0|1",substr(phpversion(),4,1))) {
		echo "<td><span class='red'>&nbsp;<td>".$lang["install"][10]."<td>";
		if($error != 2) $error = 1;
	}
	else {
		echo "<td>".$lang["install"][11]."</td></tr>";
	}
	// end parser test

	// Check for mysql extension ni php
	echo "<tr><td><b>".$lang["install"][71]."</b></td>";
	if(!function_exists("mysql_query")) {
		echo "<td  class='red'>".$lang["install"][72]."</td></tr>";
		$error = 2;
	} else {
		echo "<td>".$lang["install"][73]."</td></tr>";
	}




	// ***********

	// memory test
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][86]."</b></td>";

	$mem=ini_get("memory_limit");

	// Cette bidouille me plait pas
	//if(empty($mem)) {$mem=get_cfg_var("memory_limit");}  // Sous Win l'ini_get ne retourne rien.....

	preg_match("/([0-9]+)([KMG]*)/",$mem,$matches);

	// no K M or G 
	if (!isset($matches[2])){
		$mem=$matches[1];
	} else {
		$mem=$matches[1];
		switch ($matches[2]){
			case "G" : $mem*=1024;
			case "M" : $mem*=1024;
			case "K" : $mem*=1024;
			break;
		}
	}

	if( $mem == "" ){          // memory_limit non compil�-> no memory limit
		echo "<td>".$lang["install"][95]." - ".$lang["install"][89]."</td></tr>";
	}
	else if( $mem == "-1" ){   // memory_limit compil� mais illimit�
		echo "<td>".$lang["install"][96]." - ".$lang["install"][89]."</td></tr>";
	}
	else{	
		if ($mem<16*1024*1024){ // memoire insuffisante
			echo "<td  class='red'><b>".$lang["install"][87]." $mem octets</b><br>".$lang["install"][88]."<br>".$lang["install"][90]."</td></tr>";
		}
		else{ // on a sufisament de m�oire on passe �la suite
			echo "<td>".$lang["install"][91]." - ".$lang["install"][89]."</td></tr>";
		}
	}
	// session test
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][12]."</b></td>";



	// check whether session are enabled at all!!
	if (!extension_loaded('session')) {
		$error = 2;
		echo "<td  class='red'><b>".$lang["install"][13]."</b></td></tr>";
	} else {
		if ($_SESSION["Test_session_GLPI"] == 1) {
			echo "<td><i>".$lang["install"][14]."</i></td></tr>";
		}
		else {
			if($error != 2) $error = 1;
			echo "<td  class='red'>".$lang["install"][15]."</td></tr>";
		}
	}

	//Test for option session use trans_id loaded or not.
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][74]."</b></td>";
	//if(ini_get('session.use_trans_sid')) {
	if (isset($_POST[session_name()])||isset($_GET[session_name()])) {
		echo "<td class='red'>".$lang["install"][75]."</td></tr>";
		$error = 2;
	}
	else {
		echo "<td>".$lang["install"][76]."</td></tr>";

	}



	//Test for sybase extension loaded or not.
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][65]."</b></td>";
	if(ini_get('magic_quotes_sybase')) {
		echo "<td class='red'>".$lang["install"][66]."</td></tr>";
		$error = 2;
	}
	else {
		echo "<td>".$lang["install"][67]."</td></tr>";

	}


	//Test for utf8_encode function.
	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][83]."</b></td>";
	if(!function_exists('utf8_encode')||!function_exists('utf8_decode')) {
		echo "<td class='red'>".$lang["install"][84]."</td></tr>";
		$error = 2;
	}
	else {
		echo "<td>".$lang["install"][85]."</td></tr>";

	}


	// *********



	// file test

	// il faut un test dans /files/_dumps  et /files et /config/ et /files/_sessions et /files/_cron

	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][16]."</b></td>";

	$fp = fopen($cfg_glpi["dump_dir"] . "/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]."</p> ".$lang["install"][18]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($cfg_glpi["dump_dir"] . "/test_glpi.txt");
		if (!$delete) {
			echo "<td  class='red'>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";

		}
	}


	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][21]."</b></td>";	
	$fp = fopen($cfg_glpi['doc_dir'] . "/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]."</p> ".$lang["install"][22]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($cfg_glpi['doc_dir'] . "/test_glpi.txt");
		if (!$delete) {
			echo "<td  class='red'>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";

		}
	}


	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][23]."</b></td>";
	$fp = fopen($cfg_glpi["config_dir"] . "/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]. " " . $cfg_glpi["config_dir"] . "/test_glpi.txt" ."</p>". $lang["install"][24]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($cfg_glpi["config_dir"] . "/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";
		}
	}

	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][50]."</b></td>";
	$fp = fopen($cfg_glpi["doc_dir"]."/_sessions/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]. " " . $cfg_glpi["doc_dir"] . "/_sessions/test_glpi.txt" ."</p>". $lang["install"][51]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($cfg_glpi["doc_dir"] . "/_sessions/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";
		}
	}

	echo "<tr class='tab_bg_1'><td><b>".$lang["install"][52]."</b></td>";
	$fp = fopen($cfg_glpi["doc_dir"]."/_cron/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]. " " . $cfg_glpi["doc_dir"]."/_cron/test_glpi.txt" ."</p>". $lang["install"][53]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($cfg_glpi["doc_dir"]."/_cron/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";
		}
	}


	echo "</table>";
	switch ($error) {
		case 0 :       
			echo "<h3>".$lang["install"][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			echo "</form>";
			break;
		case 1 :       
			echo "<h3>".$lang["install"][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			echo "</form> &nbsp;&nbsp;";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][27]."\" /></p>";
			echo "</form>";
			break;
		case 2 :       
			echo "<h3>".$lang["install"][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][27]."\" /></p>";
			echo "</form>";
			break;
	}


}

//step 2 import mysql settings.
function step2($update)
{
	global $lang;
	echo "<p>".$lang["install"][28]."</p>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
	echo "<fieldset><legend>".$lang["install"][29]."</legend>";
	echo "<p><label>".$lang["install"][30] .": <input type=\"text\" name=\"db_host\" /></label></p>";
	echo "<p ><label>".$lang["install"][31] .": <input type=\"text\" name=\"db_user\" /></label></p>";
	echo "<p ><label>".$lang["install"][32]." : <input type=\"password\" name=\"db_pass\" /></label></p></fieldset>";
	echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
	echo "</form>";

}

//step 3 test mysql settings and select database.
function step3($host,$user,$password,$update)
{

	global $lang;
	error_reporting(16);
	echo "<h3>".$lang["install"][34]."</h3>";
	$link = mysql_connect($host,$user,$password);
	if (!$link || empty($host) || empty($user)) {
		echo "".$lang["install"][35]." : \n
			<br />".$lang["install"][36]." : ".mysql_error();
		if(empty($host) || empty($user)) {
			echo "<p>".$lang["install"][37]."</p>";
		}
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\"  value=\"".$lang["buttons"][13]."\" /></p>";
		echo "</form>";

	}
	else {
		echo  "<h3>".$lang["update"][93]."</h3>";

		if($update == "no") {

			echo "<p>".$lang["install"][38]."</p>";

			echo "<form action=\"install.php\" method=\"post\">";

			$db_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($db_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\" />$row->Database.</p>";
			}
			echo "<p><input type=\"radio\" name=\"databasename\" value=\"0\" />".$lang["install"][39];
			echo "<input type=\"text\" name=\"newdatabasename\"/></p>";
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\" />";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\" />";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
			echo "<input type=\"hidden\" name=\"install\" value=\"Etape_3\" />";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			mysql_close($link);
			echo "</form>";
		}
		elseif($update == "yes") {
			echo "<p>".$lang["install"][40]."</p>";
			echo "<form action=\"install.php\" method=\"post\">";

			$db_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($db_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\" />$row->Database.</p>";
			}
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\" />";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\" />";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
			echo "<input type=\"hidden\" name=\"install\" value=\"update_1\" />";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			mysql_close($link);
			echo "</form>";

		}
	}
}


//Step 4 Create and fill database.
function step4 ($host,$user,$password,$databasename,$newdatabasename)
{
	global $lang;
	//display the form to return to the previous step.

	function prev_form($host,$user,$password) {
		global $lang;
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo $lang["install"][30] .": <input type=\"hidden\" name=\"db_host\" value=\"". $host ."\"/><br />";
		echo $lang["install"][31] ." : <input type=\"hidden\" name=\"db_user\" value=\"". $user ."\"/>";
		echo $lang["install"][32] .": <input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["buttons"][13]."\" /></p>";
		echo "</form>";
	}
	//Display the form to go to the next page
	function next_form()
	{
		global $lang;

		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_4\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
		echo "</form>";
	}

	//Fill the database
	function fill_db()
	{
		global $lang, $cfg_glpi;		

		include ("_relpos.php");
		include ($phproot . "/inc/dbmysql.class.php");
		include ($phproot . "/inc/common.function.php");
		include ($cfg_glpi["config_dir"] . "/config_db.php");

		$db = new DB;
		$db_file = $phproot ."/install/mysql/glpi-0.68.1-empty.sql";
		$dbf_handle = fopen($db_file, "rt");
		$sql_query = fread($dbf_handle, filesize($db_file));
		fclose($dbf_handle);
		foreach ( explode(";\n", "$sql_query") as $sql_line) {
			if (get_magic_quotes_runtime()) $sql_line=stripslashes_deep($sql_line);
			$db->query($sql_line);
		}
		// Mise a jour de la langue par defaut
		$query = "UPDATE `glpi_config` SET default_language='".$_SESSION["dict"]."' ;";
		$db->query($query) or die("4203 ".$lang["update"][90].$db->error());
		$query = "UPDATE `glpi_users` SET language='".$_SESSION["dict"]."' ;";
		$db->query($query) or die("4203 ".$lang["update"][90].$db->error());
	}

	$link = mysql_connect($host,$user,$password);

	if(!empty($databasename)) {
		$db_selected = mysql_select_db($databasename, $link);

		if (!$db_selected) {
			echo $lang["install"][41];
			echo "<br />";
			echo $lang["install"][36]." ". mysql_error();
			prev_form($host,$user,$password);
		}
		else {
			if (create_conn_file($host,$user,$password,$databasename)) {
				fill_db();
				echo "<p>".$lang["install"][43]."</p>";
				echo "<p>".$lang["install"][44]."</p>";
				echo "<p>".$lang["install"][45]."</p>";
				echo "<p>".$lang["install"][46]."</p>";
				next_form();
			}
			else {
				echo "<p>".$lang["install"][47]."</p>";
				prev_form();
			}
		}
		mysql_close($link);
	}
	elseif(!empty($newdatabasename)) {
		// BUG cette fonction est obsol�e je l'ai remplac�par la nouvelle
		//if (mysql_create_db($newdatabasename)) {
		// END BUG
		if (mysql_query("CREATE DATABASE IF NOT EXISTS `".$newdatabasename."`")){

			echo "<p>".$lang["install"][82]."</p>";
			mysql_select_db($newdatabasename, $link);
			if (create_conn_file($host,$user,$password,$newdatabasename)) {
				fill_db();
				echo "<p>".$lang["install"][43]."</p>";
				echo "<p>".$lang["install"][44]."</p>";
				echo "<p>".$lang["install"][45]."</p>";
				echo "<p>".$lang["install"][46]."</p>";
				next_form();

			}
			else {
				echo "<p>".$lang["install"][47]."</p>";
				prev_form();
			}
		}
		else {
			echo $lang["install"][48];
			echo "<br />".$lang["install"][42] . mysql_error();
			prev_form();
		}
		mysql_close($link);
	}
	else {
		echo "<p>".$lang["install"][49]. "</p>";
		prev_form();
		mysql_close($link);
	}

	}



	function step7() {

		global $lang,$cfg_glpi;
		include ("_relpos.php");
		require_once ($phproot . "/inc/dbmysql.class.php");
		require_once ($phproot . "/inc/common.function.php");
		require_once ($cfg_glpi["config_dir"] . "/config_db.php");
		$db = new DB;

		// hack pour IIS qui ne connait pas $_SERVER['REQUEST_URI']  grrrr
		if ( !isset($_SERVER['REQUEST_URI']) ) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}

		$query="UPDATE glpi_config SET url_base='".ereg_replace("/install.php","",$_SERVER['HTTP_REFERER'])."' WHERE ID='1'";
		$db->query($query);


		echo "<h2>".$lang["install"][55]."</h2>";
		echo "<p>".$lang["install"][57]."</p>";
		echo "<p><ul><li> ".$lang["install"][58]."</li>";
		echo "<li>".$lang["install"][59]."</li>";
		echo "<li>".$lang["install"][60]."</li>";
		echo "<li>".$lang["install"][61]."</li></ul></p>";
		echo "<p>".$lang["install"][62]."</p>";
		echo "<p>".$lang["install"][63]."</p>";
		echo "<p class='submit'> <a href=\"".$HTMLRel."index.php\"><span class='button'>".$lang["install"][64]."</span></a></p>";
	}

	//Create the file config_db.php
	// an fill it with user connections info.
	function create_conn_file($host,$user,$password,$dbname)
	{
		global $cfg_glpi;
		$db_str = "<?php \n class DB extends DBmysql { \n var \$dbhost	= \"". $host ."\"; \n var \$dbuser 	= \"". $user ."\"; \n var \$dbpassword= \"". $password ."\"; \n var \$dbdefault	= \"". $dbname ."\"; \n } \n ?>";
		include ("_relpos.php");
		$fp = fopen($cfg_glpi["config_dir"] . "/config_db.php",'wt');
		if($fp) {
			$fw = fwrite($fp,$db_str);
			fclose($fp);
			return true;
		}
		else return false;
	}

	function update1($host,$user,$password,$dbname) {

		global $lang;	
		include ("_relpos.php");
		if(create_conn_file($host,$user,$password,$dbname) && !empty($dbname)) {

			$from_install = true;
			include($phproot ."/install/update.php");
		}
		else {
			echo $lang["install"][70];
			echo "<h3>".$lang["install"][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][25]."\" /></p>";
			echo "</form>";
		}


	}




	//------------Start of install script---------------------------
	if(!session_id()) session_start();
	include ("_relpos.php");
	if(empty($_SESSION["dict"])) $_SESSION["dict"] = "en_GB";
	if(isset($_POST["language"])) $_SESSION["dict"] = $_POST["language"];


	// If this file exists, it is load, allow to set configdir/dumpdir elsewhere
	if(file_exists($cfg_glpi["config_dir"] . "/config_path.php")) {
		include($cfg_glpi["config_dir"] . "/config_path.php");
	}


	loadLang($_SESSION["dict"]);
	if(!isset($_POST["install"])) {
		$_SESSION = array();
		if(file_exists($cfg_glpi["config_dir"] . "/config_db.php")) {
			include($phproot ."/index.php");
			die();
		}
		else {
			header_html("Language");
			choose_language();
		}
	}
	else {

		switch ($_POST["install"]) {

			case "lang_select" :
				header_html("".$lang["install"][92]."");
			acceptLicence();
			break;
			case "Licence" :
				header_html("".$lang["install"][81]."");
			step0();
			break;
			case "Etape_0" :
				header_html($lang["install"][77]." 0");
			$_SESSION["Test_session_GLPI"] = 1;
			step1($_POST["update"]);
			break;
			case "Etape_1" :
				header_html($lang["install"][77]." 1");
			step2($_POST["update"]);
			break;
			case "Etape_2" :
				header_html($lang["install"][77]." 2");
			step3($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["update"]);
			break;
			case "Etape_3" :

				header_html($lang["install"][77]." 3");
			if(empty($_POST["databasename"])) $_POST["databasename"] ="";
			if(empty($_POST["newdatabasename"])) $_POST["newdatabasename"] ="";

			step4($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"],$_POST["newdatabasename"]);
			break;
			case "Etape_4" :
				header_html($lang["install"][77]." 4");
			step7();
			break;

			case "update_1" : 
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
			update1($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"]);
			break;
		}
	}
	footer_html();
	//FIn du script
	?>
