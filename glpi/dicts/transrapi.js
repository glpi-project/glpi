// TransRapi 0.3
//  
//  Interface web pour fichiers de langue au format PHP
//
//  Copyright (C) 2004 Olivier Fraysse.
//
//  This file is part of TransRapi.
//  
//  TransRapi is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
 
//  TransRapi is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
// 
//  You should have received a copy of the GNU General Public License
//  along with TransRapi; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// 
// (http://www.gnu.org/licenses/gpl.txt)
// 
// http://tr.fooye.net
// http://tr.fooye.net/source/
// Contact : tr@fooye.net 
// 
//******************************************************************

function choice(choice,bool)
{
	if(!bool)
	{
		document.getElementById(choice).style.display='block';
		document.getElementById('enable_'+choice).checked=true;
		document.getElementById('button_'+choice).setAttribute("class","langon");
	}
	else
	{
		document.getElementById('enable_'+choice).checked=false;
		document.getElementById(choice).style.display='none';
		document.getElementById('button_'+choice).setAttribute("class","langoff");
	}
}
function getCorrectForm(id,val)
{
	elem = document.getElementById(id);
	elem.innerHTML ='<input type="hidden" id="hidden_'+id+'" value="'+ val +'" /><input id="input_'+id+'" value="'+ val +'" /><br /><button onclick="undoCorrectForm(\''+id+'\');">Annuler</button><button onclick="sendCorrectForm(\''+id+'\')">Valider</button>';
	/*elem.innerHTML ='<input type="hidden" id="hidden_'+id+'" value="'+ elem.innerHTML +'" /><input id="input_'+id+'" value="'+ elem.innerHTML +'" /><br /><button onclick="undoCorrectForm(\''+id+'\');">Annuler</button><button onclick="sendCorrectForm(\''+id+'\')">Valider</button>';
	*/
	elem.onclick = false;

}
function undoCorrectForm(id)
{
 		elem = document.getElementById(id);
		val = document.getElementById("hidden_"+id).value;
 		elem.innerHTML = val;
 		elem.onclick=function(){getCorrectForm(id,val);};
}
function sendCorrectForm(id)
{
// 	elem = ;
    	newscript = document.createElement('script'); 
    	newscript.setAttribute("src", "?correct="+id+"&text="+document.getElementById("input_"+id).value); 
    	var aphead = document.getElementsByTagName('body').item(0); 
	document.getElementById(id).style.border='2px dashed red';

    	void(aphead.appendChild(newscript));
}
function changeRub(rub)
{
	document.getElementById("lfrub").value=rub;
	document.getElementById("lform").submit();
}
