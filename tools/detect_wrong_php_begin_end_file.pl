#!/usr/bin/perl
#!/usr/bin/perl -w 

do_dir("..");


sub do_dir{
local ($dir)=@_;	
#print "Entering $dir\n";

opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){
		if (-d "$dir/$_"){
			if ($_ !~ m/.git/i && $_ !~ m/vendor/i && $_ !~ m/lib/i ){
				do_dir("$dir/$_");
			}
		} else {
	 		if(!(-l "$dir/$_")){
				if ((index($_,".php",0) == length($_)-4)){
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
   $end=`grep '?>' $file | grep -v '<?xml'`;
   $eof=`tail -1 $file`;
   if ($end) {
      if ($eof !~ m/^\s*\?\>$/) {
         print "problem with end of $file $eof\n";
      }
   }
   $bof=`head -1 $file`;
   if ($bof !~ m/^\<\?php\s*$/){
      print "problem with begin of $file $bof\n";
   }
}



