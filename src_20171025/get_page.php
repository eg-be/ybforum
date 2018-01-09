<?php
  $iActPage=$iMaxPages=0;
  if ($ThreadsPerPage>0) {
    $Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass);
    $Q=@mysql_query("select no from $DbTab 
                                    where preno = 0
                                    and   del   = '-'");
    $iThreads=@mysql_num_rows($Q);
    $iMaxPages = floor($iThreads / $ThreadsPerPage);
    if (($iThreads % $ThreadsPerPage)!=0) {$iMaxPages++;}
    $iActPage=1;
    if (isset($_GET['iActPage'])) {
      $iActPage=intval($_GET['iActPage']);
	  if (($iActPage<=0) || ($iActPage>$iMaxPages)) {
	    $iActPage=1;
	  }
    }
    elseif (isset($_COOKIE['iActPage'])) {
      $iActPage=intval($_COOKIE['iActPage']);
	  if (($iActPage<=0) || ($iActPage>$iMaxPages)) {
	    $iActPage=1;
	  }
    }
    else {
      $iActPage=1;
    }
  
    setcookie("iActPage",strval($iActPage),time()+$MaxLoginTime,"/");
  
    @mysql_close();
  }
?>