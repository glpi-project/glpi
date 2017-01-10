#!/usr/bin/perl
#!/usr/bin/perl -w 

#/**
# * ---------------------------------------------------------------------
# * GLPI - Gestionnaire Libre de Parc Informatique
# * Copyright (C) 2015-2017 Teclib' and contributors.
# *
# * http://glpi-project.org
# *
# * based on GLPI - Gestionnaire Libre de Parc Informatique
# * Copyright (C) 2003-2014 by the INDEPNET Development Team.
# *
# * ---------------------------------------------------------------------
# *
# * LICENSE
# *
# * This file is part of GLPI.
# *
# * GLPI is free software; you can redistribute it and/or modify
# * it under the terms of the GNU General Public License as published by
# * the Free Software Foundation; either version 2 of the License, or
# * (at your option) any later version.
# *
# * GLPI is distributed in the hope that it will be useful,
# * but WITHOUT ANY WARRANTY; without even the implied warranty of
# * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# * GNU General Public License for more details.
# *
# * You should have received a copy of the GNU General Public License
# * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
# * ---------------------------------------------------------------------
# */

do_dir("..");


sub do_dir{
local ($dir)=@_;	
print "Entering $dir\n";

opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){
		if (-d "$dir/$_"){
			if ($_ !~ m/.svn/i && $_ !~ m/CVS/i && $_ !~ m/lib/i){
				
				do_dir("$dir/$_");
			}
		} else {
	 		if(!(-l "$dir/$_")){
				if ((index($_,".php",0)!=-1)||(index($_,".txt",0)!=-1)||(index($_,".css",0)!=-1)){
					do_file("$dir/$_");
	 			}
			}
		}
	}
}
closedir DIRHANDLE; 

}

sub do_file{
	local ($file)=@_;
	print $file."\n";
	### DELETE HEADERS
	open(INIT_FILE,$file);
	@lines=<INIT_FILE>;
	close(INIT_FILE);	

	open(TMP_FILE,">/tmp/tmp_glpi.txt");

	$status='';
	foreach (@lines){
		if ($_ =~ m/\*\//){
			$status="END";
		} 

		if ($status =~ m/END/||$status !~ m/BEGIN/){
		print TMP_FILE $_;
		} 

		if ($status !~ m/END/){
			if ($_ =~ m/\/\*/){
				$status="BEGIN";
				##### ADD NEW HEADERS
				open(HEADER_FILE,"HEADER");
				@headers=<HEADER_FILE>;
				foreach (@headers){
					print TMP_FILE $_;
				}
				close(HEADER_FILE) ;
				
			} 
		}
	}
	close(TMP_FILE); 
	
	system("cp -f /tmp/tmp_glpi.txt $file");

	

}



