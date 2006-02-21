#!/usr/bin/perl
#!/usr/bin/perl -w 

# ----------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2005 by the INDEPNET Development Team.
# 
# http://indepnet.net/   http://glpi.indepnet.org
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

if (@ARGV!=2){
print "USAGE script.pl module max_number \n Must to be launch in GLPI root dir\n\n";

exit();
}
$module=@ARGV[0];
print "MODULE : $module\n";
$max=@ARGV[1];
print "MAX : $max\n";

$a=`grep wc -l update.php`;
print $a;

for ($i=0;$i<=$max;$i++){
print "SEARCH \$lang\[\"$module\"\]\[$i\] : ";
$count=0;
do_dir(".",$module,$i);
print $count;
print "\n";
}


sub do_dir{
local ($dir,$module,$i)=@_;	

#print "Entering $dir\n";
my $found_php=0;
opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
if ($_ ne '..' && $_ ne '.'){
	
	if (-d "$dir/$_" && $_!~m/dicts/ && $_!~m/CVS/){
		do_dir("$dir/$_",$module,$i);
	} else {
 		if (!-d "$dir/$_" && (index($_,".php",0)==length($_)-4)){
		$found_php=1;
	}
	}
}
}
if ($found_php==1){
	open COUNT, "cat $dir/*.php | grep \'\\\$lang\\\[\\\"$module\\\"\\\]\\\[$i\\\]\' | wc -l |";
	while(<COUNT>) {
           	$count+=$_;
		#print $_."\n";
	}
}
 
closedir DIRHANDLE; 
	
}

