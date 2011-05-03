#!/usr/bin/perl
#!/usr/bin/perl -w 

# ----------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2006 by the INDEPNET Development Team.
# 
# http://indepnet.net/   http://glpi-project.org
# ----------------------------------------------------------------------
#
# LICENSE
#
#	This file is part of GLPI.
#
#    GLPI is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.
#
#    GLPI is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with GLPI; if not, write to the Free Software
#    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
# ------------------------------------------------------------------------

if (@ARGV==0){
print "USAGE find_twin_in_dict.pl dico_file\n\n";

exit();
}

$file=$ARGV[0];

open(INFO,$file) or die("Fichier ".$file." absent");
@lines=<INFO>;
close(INFO);

foreach (@lines)
{
	if ($_=~m/(\$[A-Z_]*LANG[A-Z_]*)\['([a-zA-Z]+)'\]\[([a-zA-Z0-9]+)\]\s*=\s*"(.*)";\s$/){
		$search_string=$4;
		$founded= `grep -i \"=\s*\\\"$search_string\\\"\" $file | wc -l`;
		if ($founded > 1){
			print "SEARCH $4 ";
			print $founded;
			print "\n";
		}
		
	}

}



