#!/usr/bin/perl
#!/usr/bin/perl -w
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2017 Teclib' and contributors.
#  *
#  * http://glpi-project.org
#  *
#  * based on GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2003-2014 by the INDEPNET Development Team.
#  *
#  * ---------------------------------------------------------------------
#  *
#  * LICENSE
#  *
#  * This file is part of GLPI.
#  *
#  * GLPI is free software; you can redistribute it and/or modify
#  * it under the terms of the GNU General Public License as published by
#  * the Free Software Foundation; either version 2 of the License, or
#  * (at your option) any later version.
#  *
#  * GLPI is distributed in the hope that it will be useful,
#  * but WITHOUT ANY WARRANTY; without even the implied warranty of
#  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  * GNU General Public License for more details.
#  *
#  * You should have received a copy of the GNU General Public License
#  * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
#  * ---------------------------------------------------------------------
# */
do_dir("..");


sub do_dir{
local ($dir)=@_;
print "Entering $dir\n";

opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n";
foreach (readdir(DIRHANDLE)){
	if ($_ ne '..' && $_ ne '.'){
		if (-d "$dir/$_"){
			# Excluded directories
			if ($_ !~ m/.git/i && $_ !~ m/lib/i && $_ !~ m/plugins/i && $_ !~ m/vendor/i){
				do_dir("$dir/$_");
			}
		} else {
	 		if(!(-l "$dir/$_")){
				# Included filetypes - php, css, js => default comment style
				if ((index($_,".php",0)!=-1)||(index($_,".css",0)!=-1)||(index($_,".js",0)!=-1)){
					do_file("$dir/$_", "");
	 			}
				# Included filetypes - sql, sh, pl => Add a specific comment style (#)
				if ((index($_,".sql",0)!=-1)||(index($_,".sh",0)!=-1)||(index($_,".pl",0)!=-1)){
					do_file("$dir/$_", "# ");
	 			}
			}
		}
	}
}
closedir DIRHANDLE;

}

sub do_file{
	local ($file, $format)=@_;
    if($format ne "") {
        print $file." (Using specific comment " . $format . ")\n";
    } else {
        print $file."\n";
    }

	### DELETE HEADERS
	open(INIT_FILE,$file);
	@lines=<INIT_FILE>;
	close(INIT_FILE);

	open(TMP_FILE,">/tmp/tmp_glpi.txt");

	$status='';
	foreach (@lines){
		# Did we found header closure tag ?
		if ($_ =~ m/$format\*\//){
			$status="END";
		}

		# If we have reach the header closure tag, we print the rest of the file
		if ($status =~ m/END/||$status !~ m/BEGIN/){
			print TMP_FILE $_;
		}

		# If we haven't reach the header closure tag
		if ($status !~ m/END/){
			# If we found the header open tag...
			if ($_ =~ m/$format\/\*\*/){
				$status="BEGIN";
				##### ADD NEW HEADERS
				open(HEADER_FILE,"HEADER");
				@headers=<HEADER_FILE>;
				foreach (@headers){
					print TMP_FILE $format;
					print TMP_FILE $_;
				}
				close(HEADER_FILE) ;
			}
		}
	}
	close(TMP_FILE);
	system("cp -f /tmp/tmp_glpi.txt $file");

    # If we haven't found an header on the file, report it
    if($status eq '') {
        print "Unable to found an header on $file. Please add it manually";
        exit 1;
    }
}
