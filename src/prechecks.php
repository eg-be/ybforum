<?php 
// Archivierungsmodus abfangen
  if (CheckArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbFnc)) {
    $sSubTitle="Archivierung l&auml;uft";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	
	?>
	<p align=justify>Das Forum wird im Moment archiviert. W�hrend dieser Zeit stehen die 
	Funktionen des Forums nicht zu Verf�gung. Der Vorgang kann einige Minuten dauern. 
	Sobald die Archivierung abgeschlossen ist, steht das Forum wieder zur Verf�gung.</p>
	<?php 
	EchoFooter();
	exit();
  }
?>