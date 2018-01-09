<?php

function DbQuery($Db,$DbHost,$DbName,$DbUSer,$DbPass,$sQuery) {
  $bClose=false;
  if (!$Db) {
    if ($Db=OpenDb($DbHost,$DbName,$DbUSer,$DbPass)) {
      $bClose=true;
    }
    else {
      return (false);
    }  
  }

  $DbQuery=mysql_query($sQuery);

  if ($bClose) {
    mysql_close($Db);
  }

  return ($DbQuery);
}

function OpenDb($DbHost,$DbName,$DbUser,$DbPass) {
  $Db=@mysql_pconnect($DbHost,$DbUser,$DbPass);
  if ($Db) {
    $DbCheck=@mysql_select_db($DbName);
	if ($DbCheck) {
	  return ($Db);
	}
	mysql_close($Db);
  }
  return (false);
}

?>
