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
			if ($_ !~ m/.svn/i && $_ !~ m/CVS/i && $_ !~ m/CAS/i && $_ !~ m/ezpdf/i  && $_ !~ m/tiny_mce/i  && $_ !~ m/vcardclass/i  && $_ !~ m/phpmailer/i  && $_ !~ m/calendar/i && $_ !~ m/locales/i){
				
				do_dir("$dir/$_");
			}
		} else {
	 		if(!(-l "$dir/$_")){
				if ((index($_,".php",0)!=-1)){
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
	
	system("vim -s indent.script.vi $file");

	

}



