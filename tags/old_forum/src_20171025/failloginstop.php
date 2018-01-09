<?php

if ($UseLoginLock=="X") {
  if ($iLockTimeRemaining=CheckLoginLock($DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$REMOTE_ADDR)) {
    $sSubTitle="<font color=#$ErrColor>Fehler: Sie sind wegen Fehllogings<br>
    noch f&uuml;r $iLockTimeRemaining Sekunden vom Forum ausgeschlossen!</font>";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
    echo "<center><a href=\"index.php\">Zur&uuml;ck</a></center>";
    EchoFooter();
    exit();
  }
}

?>