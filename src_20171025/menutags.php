<?php

include_once("cfg/config.php");

$aTag[0][0]="[index]";    $aTag[0][1]="[/index]";    $aTag[0][2]="<a href=\"index.php\">";                      $aTag[0][3]="</a>";
$aTag[1][0]="[recent]";   $aTag[1][1]="[/recent]";   $aTag[1][2]="<a href=\"recent.php\">";                     $aTag[1][3]="</a>";
$aTag[2][0]="[search]";   $aTag[2][1]="[/search]";   $aTag[2][2]="<a href=\"search.php\">";                     $aTag[2][3]="</a>";
$aTag[3][0]="[post]";     $aTag[3][1]="[/post]";     $aTag[3][2]="<a href=\"post.php?sNo=";                     $aTag[3][3]="</a>";
if ($LoginRequired=="X") {
  $aTag[4][0]="[regulars]"; $aTag[4][1]="[/regulars]"; $aTag[4][2]="<a href=\"reglogin.php\">";     $aTag[4][3]="</a>";
}
else {
  $aTag[4][0]="[regulars]"; $aTag[4][1]="[/regulars]"; $aTag[4][2]="<a href=\"reglogin.php\" target=_blank>";     $aTag[4][3]="</a>";
}  
$aTag[5][0]="[format]";   $aTag[5][1]="[/format]";   $aTag[5][2]="<a href=\"textformatierung.php\">";           $aTag[5][3]="</a>";
$aTag[6][0]="[toggle]";   $aTag[6][1]="[/toggle]";   $aTag[6][2]="<a href=\"index.php?sToogleThreadShow=X\">";  $aTag[6][3]="</a>";
$aTag[7][0]="[atoggle]";  $aTag[7][1]="[/atoggle]";  $aTag[7][2]="<a href=\"archive.php?sToogleThreadShow=X&>"; $aTag[7][3]="</a>";
$aTag[8][0]="[archive]";  $aTag[8][1]="[/archive]";  $aTag[8][2]="<a href=\"archive.php?"; $aTag[8][3]="</a>";

$aTag[9][0]="[NewView]";  $aTag[9][1]="[/NewView]";  $aTag[9][2]="<a href=\"index.php?OrderThreadsByNewestsPost=X\">"; $aTag[9][3]="</a>";

?>