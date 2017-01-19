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
			if ($_ !~ m/.svn/i && $_ !~ m/CVS/i && $_ !~ m/lib/i ){
				do_dir("$dir/$_");
			}
		} else {
	 		if(!(-l "$dir/$_")){
				if ((index($_,".php",0)!=-1)||(index($_,".txt",0)!=-1)||(index($_,".css",0)!=-1)){
					do_file("$dir/$_");
	 			}
				if ((index($_,".mo",0)!=-1)){
					do_file2("$dir/$_");
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
	system("svn propset svn:eol-style 'native' $file");
	system("svn propset svn:keywords 'Author Date Id Revision' $file");
}
sub do_file2{
	local ($file)=@_;
	print $file."\n";
	system("svn propset svn:executable yes $file");
}

