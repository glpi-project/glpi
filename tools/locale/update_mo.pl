#!/usr/bin/perl
#!/usr/bin/perl -w 

if (@ARGV!=0){
print "USAGE update_mo.pl\n\n";

exit();
}

# only to en and fr for the moment

`msgfmt locales/en_GB.po -o locales/en_GB.mo`;
`msgfmt locales/fr_FR.po -o locales/fr_FR.mo`;
`msgfmt locales/es_ES.po -o locales/es_ES.mo`;
exit();


opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `msgfmt locales/$_ -o locales/$lang.mo`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
