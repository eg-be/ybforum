<?php
//
// CREATE FULLTEXT INDEX index_forum_posts on forum_forum (ptext,author,subject)

 /* while (list ($sKey, $sVal) = each ($_POST)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_GET)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_SERVER)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_COOKIE)) {
    $$sKey=$sVal;
  }*/
  $sAction=$_POST['sAction'];
  $sSearch=$_POST['sSearch'];
  
  include ("cfg/config.php");
  include ("functions.php");
  include ("prechecks.php");
  include_once("chkloginmode.php");
	$MAX_POSTS_SEARCH=250;
  $sSubTitle="Beitragssuche";
  
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  
  EchoSearchMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
  
  if (!isset($sAction)) {
    $sAction="INI";
	$sSearch="";
	$sCaseSens="";
	$sAllWords="A";
		  $iCount=-1;
  }
  if ($sAction=="SEARCH") {
    $sSearch=trim(chop($sSearch));
    $sCpySearch=$sSearch;
	if ($sCaseSens!="X") {
	  $sCpySearch=strtolower($sCpySearch);
	}
	if ($sCpySearch!="") {
	  $aSearch=explode(" ",$sCpySearch);
	
	  $iCount=0;
      // Zun‰chst in den online stehenden Beitr‰gen suchen
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	  		    $sText=$DbRow[13];
		$sText=stripcslashes($sSearch);	
		$bmode="";
		if (ereg('(\+|\-|and|or|")',$sText))
			$bmode="IN BOOLEAN MODE";
		$smatch=" MATCH (ptext,author,subject) AGAINST ('".mysql_escape_string($sSearch)."' $bmode)";
	  		$query="select *, ($smatch) as score from $DbTab where $smatch and  del='-' order by score desc, no desc  limit ".$MAX_POSTS_SEARCH;
	  
	    $DbQuery=mysql_db_query($DbName,$query,$Db);
	   
	    mysql_close($Db);
	  }
	  
	  while ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sText=$DbRow[13];
	  	$sText=stripcslashes($sText);
          if (($RegsActive=="X") && (($DbRow[4]=="R") || ($DbRow[4]=="A"))) {
		    $Db=NULL;
            $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$DbRow[2],$DbRow[4],$RegColor,$AdminColor,$RegsSameCol);
          }
		  else {
		    $sAuthor=$DbRow[2];
		  }
		
          $sNo=strval($DbRow[0]);
          $sUrl="showentry.php?sNo=".$sNo;
		  $sSubject=$DbRow[10];
		  $sDate=$DbRow[5];
		  $sTime=$DbRow[6];
          $aSearchRes[$iCount]="<a href=\"$sUrl\" >$sSubject</a>&nbsp;-&nbsp;$sAuthor&nbsp;-&nbsp;$sDate&nbsp;$sTime";
          if ($DbRow[12]=="X") {
            $aSearchRes[$iCount].="&nbsp;-&nbsp;(abgeschlossen)";
          }
		  $iCount++;

	  }
	  
	  
	  // Jetzt die Archive durchsuchen
/*	  if ($aArchive=BuildArchiveList()) {
	    $iAnz=sizeof($aArchive);
		for ($i=0;$i<$iAnz;$i++) {
		  if ($aPosts=ReadPosts($aArchive[$i])) {
		    $iAnzPost=sizeof($aPosts);
			for ($k=0;$k<$iAnzPost;$k++) {
			  $sText=ReadArcPostText($aArchive[$i],$aPosts[$k][0]);
			  if (KffSearch($aPosts[$k],$sText,$aSearch,$sAllWords,$sCaseSens)) {
			    $aPost=$aPosts[$k];
                if (($RegsActive=="X") && (($aPost[4]=="R") || ($aPost[4]=="A"))) {
                  $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$aPost[2],$aPost[4],$RegColor,$AdminColor,$RegsSameCol);
                }
		        else {
		          $sAuthor=$aPost[2];
		        }
				
                $sNo=strval($aPost[0]);
		        $sLinkNo=substr($aArchive[$i],0,strpos($aArchive[$i],".txt"));
		        $sLinkNo=substr($sLinkNo,5,(strlen($sLinkNo)-5));
				
                $sUrl="showarcentry.php?sNo=".$sNo."&sFile=".$aArchive[$i]."&sLinkNo=$sLinkNo";
		        $sSubject=$aPost[10];
		        $sDate=$aPost[5];
		        $sTime=$aPost[6];
                $aSearchRes[$iCount]="<a href=\"$sUrl\" target=_blank>$sSubject</a>&nbsp;-&nbsp;$sAuthor&nbsp;-&nbsp;$sDate&nbsp;$sTime";
                if ($aPost[12]=="X") {
                  $aSearchRes[$iCount].="&nbsp;-&nbsp;(abgeschlossen)";
                }
		        $iCount++;	
			  }
		    }
		  }
		}
	  }*/
	}
  }
  	
  ?>
  <form action="search.php" method=post>
  <input type=hidden name=sAction value="SEARCH">
  <center><table>
  
  <tr>
  <td><b>Suchbegriffe:</b></td>
  <td colspan=2><input type=text name=sSearch size=50 maxlength=100 value="<?php echo $sSearch;?>"></td>
  </tr>

  <!-- <tr>
  <td><input type=checkbox name=sCaseSens value="X" <?php if ($sCaseSens=="X") {echo "checked";}?>>Groﬂ-/Kleinschreibung beachten</td>
  <td><input type=radio name=sAllWords value="A" <?php if ($sAllWords=="A") {echo "checked";}?>>Alle Suchbegriffe finden</td>
  <td><input type=radio name=sAllWords value="O" <?php if ($sAllWords=="O") {echo "checked";}?>>Einen der Suchbegriffe finden</td>
  </tr>-->
  
  <tr>
  <td colspan=3 align=center><input type=submit value="Suchen"></td>
  </tr>  
  
  </table></center>
  </form>
  
  <?php  if ($iCount>-1) {  ?>
  <hr>
  <center><b>Suchergebnisse</b></center><br>

  <?php
  if ($iCount==0) {
  	if (strlen($sSearch)<4) {
  			echo "<div align=\"center\"><i>Leider wurde nichts gefunden! Der Suchbegriff ist zu kurz!</i></div>";  
  	} else 
			echo "<div align=\"center\"><i>Leider wurde nichts gefunden!</i></div>";  
	}
  if ($iCount>=$MAX_POSTS_SEARCH)
  	 echo "<div align=\"right\"><i>Es wurden mehr als $MAX_POSTS_SEARCH Beitr‰ge gefunden! Es werden nur die neusten Eintr‰ge angezeigt!</i></div><br>";
  if ($sAction=="SEARCH") {
    $iAnz=sizeof($aSearchRes);
	for ($i=0;$i<$iAnz;$i++) {
	  echo $aSearchRes[$i]."<br>";
	}
  }	   
  
 } 
  EchoFooter();
  
//===============================================================================  
  
  function KffSearch($DbRow,$sText,$aSearch,$sAllWords,$sCaseSens) {
    $bFound=KffLookup($DbRow[2],$aSearch,$sAllWords,$sCaseSens);
    if (!$bFound) {$bFound=KffLookup($DbRow[10],$aSearch,$sAllWords,$sCaseSens);}	
    if (!$bFound) {$bFound=KffLookup($sText,$aSearch,$sAllWords,$sCaseSens);}	
	return($bFound);
  }
  
  function KffLookup($sSearch,$aSearch,$sAllWords,$sCaseSens) {
    $sSearch="#".$sSearch;
	if ($sCaseSens!="X") {
	  $sSearch=strtolower($sSearch);
	}
    $iAnz=sizeof($aSearch);
    if ($sAllWords=="A") {
	  for ($i=0;$i<$iAnz;$i++) {
	    if (!(strpos($sSearch,$aSearch[$i]))) {
		  return (false);
		}
	  }
	  return (true);
	}
    if ($sAllWords=="O") {
	  for ($i=0;$i<$iAnz;$i++) {
	    if (strpos($sSearch,$aSearch[$i])) {
		  return (true);
		}
	  }
	  return (false);
	}	
  }
?>