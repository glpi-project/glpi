#!/usr/bin/perl
#!/usr/bin/perl -w 

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
                # Included filetypes
				if ((index($_,".php",0)!=-1)||(index($_,".txt",0)!=-1)||(index($_,".css",0)!=-1)||(index($_,".js",0)!=-1)){
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
			if ($_ =~ m/\/\*\*/){
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