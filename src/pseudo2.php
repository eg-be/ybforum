<?php
/*
$aTag[0][0]="[b]";$aTag[0][1]="[/b]";$aTag[0][2]="<b>";$aTag[0][3]="</b>";
$aTag[1][0]="[u]";$aTag[1][1]="[/u]";$aTag[1][2]="<u>";$aTag[1][3]="</u>";
$aTag[2][0]="[i]";$aTag[2][1]="[/i]";$aTag[2][2]="<i>";$aTag[2][3]="</i>";


$aTag[19][0]="[ul]"; $aTag[19][1]="[/ul]"; $aTag[19][2]="<ul>"; $aTag[19][3]="</ul>";
$aTag[20][0]="[li]"; $aTag[20][1]="[/li]"; $aTag[20][2]="<li>"; $aTag[20][3]="</li>";
$aTag[21][0]="[ol]"; $aTag[21][1]="[/ol]"; $aTag[21][2]="<ol>"; $aTag[21][3]="</ol>";

$aTag[22][0]="[ol=1]"; $aTag[22][1]="[/ol]"; $aTag[22][2]="<ol type=1>"; $aTag[22][3]="</ol>";
$aTag[23][0]="[ol=i]"; $aTag[23][1]="[/ol]"; $aTag[23][2]="<ol type=i>"; $aTag[23][3]="</ol>";
$aTag[24][0]="[ol=I]"; $aTag[24][1]="[/ol]"; $aTag[24][2]="<ol type=I>"; $aTag[24][3]="</ol>";
$aTag[25][0]="[ol=a]"; $aTag[25][1]="[/ol]"; $aTag[25][2]="<ol type=a>"; $aTag[25][3]="</ol>";
$aTag[26][0]="[ol=A]"; $aTag[26][1]="[/ol]"; $aTag[26][2]="<ol type=A>"; $aTag[26][3]="</ol>";

$aTag[27][0]="[center]";  $aTag[27][1]="[/center]";  $aTag[27][2]="<center>";          $aTag[27][3]="</center>";
$aTag[28][0]="[justify]"; $aTag[28][1]="[/justify]"; $aTag[28][2]="<p align=justify>"; $aTag[28][3]="</p>";
$aTag[29][0]="[left]";    $aTag[29][1]="[/left]";    $aTag[29][2]="<div align=left>";  $aTag[29][3]="</div>";
$aTag[30][0]="[right]";   $aTag[30][1]="[/right]";   $aTag[30][2]="<div align=right>"; $aTag[30][3]="</div>";

$aTag[31][0]="[h1]"; $aTag[31][1]="[/h1]"; $aTag[31][2]="<h1>"; $aTag[31][3]="</h1>";
$aTag[32][0]="[h2]"; $aTag[32][1]="[/h2]"; $aTag[32][2]="<h2>"; $aTag[32][3]="</h2>";
$aTag[33][0]="[h3]"; $aTag[33][1]="[/h3]"; $aTag[33][2]="<h3>"; $aTag[33][3]="</h3>";
$aTag[34][0]="[h4]"; $aTag[34][1]="[/h4]"; $aTag[34][2]="<h4>"; $aTag[34][3]="</h4>";

$aTag[35][0]="[table]";   $aTag[35][1]="[/table]"; $aTag[35][2]="<table border=0>"; $aTag[35][3]="</table>";
$aTag[36][0]="[table=1]"; $aTag[36][1]="[/table]"; $aTag[36][2]="<table border=1>"; $aTag[36][3]="</table>";
$aTag[37][0]="[table=2]"; $aTag[37][1]="[/table]"; $aTag[37][2]="<table border=2>"; $aTag[37][3]="</table>";
$aTag[38][0]="[table=3]"; $aTag[38][1]="[/table]"; $aTag[38][2]="<table border=3>"; $aTag[38][3]="</table>";
$aTag[39][0]="[table=4]"; $aTag[39][1]="[/table]"; $aTag[39][2]="<table border=4>"; $aTag[39][3]="</table>";
$aTag[40][0]="[table=5]"; $aTag[40][1]="[/table]"; $aTag[40][2]="<table border=5>"; $aTag[40][3]="</table>";

$aTag[42][0]="[size+4]"; $aTag[42][1]="[/size]"; $aTag[42][2]="<font size=+4>"; $aTag[42][3]="</font>";
$aTag[43][0]="[size+3]"; $aTag[43][1]="[/size]"; $aTag[43][2]="<font size=+3>"; $aTag[43][3]="</font>";
$aTag[44][0]="[size+2]"; $aTag[44][1]="[/size]"; $aTag[44][2]="<font size=+2>"; $aTag[44][3]="</font>";
$aTag[45][0]="[size+1]"; $aTag[45][1]="[/size]"; $aTag[45][2]="<font size=+1>"; $aTag[45][3]="</font>";
$aTag[46][0]="[size-1]"; $aTag[46][1]="[/size]"; $aTag[46][2]="<font size=-1>"; $aTag[46][3]="</font>";
$aTag[47][0]="[size-2]"; $aTag[47][1]="[/size]"; $aTag[47][2]="<font size=-2>"; $aTag[47][3]="</font>";
$aTag[48][0]="[size-3]"; $aTag[48][1]="[/size]"; $aTag[48][2]="<font size=-3>"; $aTag[48][3]="</font>";
$aTag[49][0]="[size-4]"; $aTag[49][1]="[/size]"; $aTag[49][2]="<font size=-4>"; $aTag[49][3]="</font>";

$aTag[50][0]="[sup]"; $aTag[50][1]="[/sup]"; $aTag[50][2]="<sup>"; $aTag[50][3]="<sup>";
$aTag[51][0]="[sub]"; $aTag[51][1]="[/sub]"; $aTag[51][2]="<sub>"; $aTag[51][3]="<sub>";

$aTag[52][0]="[email]"; $aTag[52][1]="[/email]"; $aTag[52][2]="<a href=\"mailto:REPL\">"       ; $aTag[52][3]="</a>";
$aTag[53][0]="[url]";   $aTag[53][1]="[/url]";   $aTag[53][2]="<a href=\"REPL\" target=_blank>"; $aTag[53][3]="</a>";
$aTag[54][0]="[img]";   $aTag[54][1]="[/img]";   $aTag[54][2]="<img src=\"REPL\" border=0>"; $aTag[54][3]="";

$aTag[55][0]="[tr]"; $aTag[55][1]="[/tr]"; $aTag[55][2]="<tr>"; $aTag[55][3]="</tr>";
$aTag[56][0]="[td]"; $aTag[56][1]="[/td]"; $aTag[56][2]="<td>"; $aTag[56][3]="</td>";

$aTag[57][0]="[td top]";    $aTag[57][1]="[/td]"; $aTag[57][2]="<td valign=top>";    $aTag[57][3]="</td>";
$aTag[58][0]="[td bottom]"; $aTag[58][1]="[/td]"; $aTag[58][2]="<td valign=bottom>"; $aTag[58][3]="</td>";
$aTag[41][0]="[td center]"; $aTag[41][1]="[/td]"; $aTag[41][2]="<td valign=center>"; $aTag[41][3]="</td>";
*/

function AddStdReplace($tag, $htmltag, &$arr){
 $arr[] = array("\[".$tag."\](.*)\[/".$tag."\]", "<".$htmltag.">\\1</".$htmltag.">");	
}

function AddColor($tag, $color, &$arr){
 $arr[] = array("\[".$tag."\](.*)\[/".$tag."\]", "<font color=#".$color.">\\1</font>");	
}



$aTagR=array();
AddStdReplace("i","i",$aTagR);
AddStdReplace("b","b",$aTagR);
AddStdReplace("u","u",$aTagR);

AddStdReplace("ul","ul",$aTagR);
AddStdReplace("li","li",$aTagR);
AddStdReplace("ol","ol",$aTagR);

AddColor("black","000000",$aTagR);
AddColor("maroon","800000",$aTagR);
AddColor("green","008000",$aTagR);
AddColor("olive","808000",$aTagR);
AddColor("navy","000080",$aTagR);
AddColor("purple","800080",$aTagR);
AddColor("teal","008080",$aTagR);
AddColor("silver","0c0c0c",$aTagR);
AddColor("gray","0c0c0c",$aTagR);
AddColor("red","ff0000",$aTagR);
AddColor("lime","00ff00",$aTagR);
AddColor("yellow","ffff00",$aTagR);
AddColor("blue","0000ff",$aTagR);
AddColor("fuchsia","ff00ff",$aTagR);
AddColor("aqua","00ffff",$aTagR);
AddColor("white","ffffff",$aTagR);


$aTagR[] = array("\[url\](http://[^\[]*)\[/url\]", "<a href=\"\\1\"\ target=_blank>\\1</a>");
$aTagR[] = array("\[url\](www[^\[]*)\[/url\]", "<a href=\"http://\\1\"\ target=_blank>\\1</a>");
$aTagR[] = array("\[url=(http://[^]]*)\]([^\[]*)\[/url\]", "<a href=\"\\1\"\ target=_blank>\\2</a>");
$aTagR[] = array("\[url=(www.[^]]*)\]([^[]*)\[/url\]", "<a href=\"http://\\1\"\ target=_blank>\\2</a>");
$aTagR[] = array("\[img\](http://.[^\[]*)\[/img\]", "<img src=\"\\1\"\ border=0>");
$aTagR[] = array("\[email\]([^\[]*)\[/email\]", "<a href=\"mailto:\\1\">\\1</a>");

?>