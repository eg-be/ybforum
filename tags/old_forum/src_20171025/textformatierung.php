<?php 
  while (list ($sKey, $sVal) = each ($_POST)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_GET)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_SERVER)) {
    $$sKey=$sVal;
  }
include ("cfg/config.php");
include ("functions.php");
include_once("chkloginmode.php");

$sSubTitle="Textformatierung Version 2.01";
EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);

EchoFormatMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);

?>  

<p align=justify>
<br>
Die folgende Tabelle zeigt Ihnen die M&ouml;glichkeiten Ihren Beitrag zu formatieren:</p>

Update 26. 11. 2008: Viele Formatierungen wurden gekickt. Wer diese trozdem wieder möchte, kann sich im Forum melden.

<center><table border=1 cellpadding=5>
<tr>
<td valign=top><b>Formatierung</b></td>
<td valign=top><b>Pseudotag</b></td>
<td valign=top><b>HTML-Entsprechung</b></td>
<td valign=top><b>Beispiel/Erl&auml;uterung</b></td>
<td valign=top><b>Anzeige</b></td>
</tr>

<tr>
<td valign=top>Fettschrift</td>
<td valign=top>[b]...[/b]</td>
<td valign=top>&lt;b&gt;...&lt;/b&gt;</td>
<td valign=top>[b]Dies ist Fettschrift.[/b]</td>
<td valign=top><b>Dies ist Fettschrift.</b></td>
</tr>

<tr>
<td valign=top>Kursivschrift</td>
<td valign=top>[i]...[/i]</td>
<td valign=top>&lt;i&gt;...&lt;/i&gt;</td>
<td valign=top>[i]Dies ist Kursivschrift.[/i]</td>
<td valign=top><i>Dies ist Kursivschrift.</i></td>
</tr>

<tr>
<td valign=top>Unterstreichen</td>
<td valign=top>[u]...[/u]</td>
<td valign=top>&lt;u&gt;...&lt;/u&gt;</td>
<td valign=top>[u]Dies ist unterstrichen.[/u]</td>
<td valign=top><u>Dies ist unterstrichen.</u></td>
</tr>
<!--
<tr>
<td valign=top>Zentrieren</td>
<td valign=top>[center]...[/center]</td>
<td valign=top>&lt;center&gt;...&lt;/center&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Blocksatz</td>
<td valign=top>[justify]...[/justify]</td>
<td valign=top>&lt;p align=justify&gt;...&lt;/p&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Linksb&uuml;ndig</td>
<td valign=top>[left]...[/left]</td>
<td valign=top>&lt;div align=left&gt;...&lt;/div&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Rechtsb&uuml;ndig</td>
<td valign=top>[right]...[/right]</td>
<td valign=top>&lt;div align=right&gt;...&lt;/right&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Hochstellen</td>
<td valign=top>[sup]...[/sup]</td>
<td valign=top>&lt;sup&gt;...&lt;/sup&gt;</td>
<td valign=top>normal [sup]hoch[/sup] normal</td>
<td valign=top>normal <sup>hoch</sup> normal</td>
</tr>

<tr>
<td valign=top>Tiefstellen</td>
<td valign=top>[sub]...[/sub]</td>
<td valign=top>&lt;sub&gt;...&lt;/sub&gt;</td>
<td valign=top>normal [sub]tief[/sub] normal</td>
<td valign=top>normal <sub>tief</sub> normal</td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e 4</td>
<td valign=top>[size+4]...[/size]</td>
<td valign=top>&lt;font size=+4&gt;...&lt;/font&gt;</td>
<td valign=top>[size+4]vier[/size]</td>
<td valign=top><font size="+4">vier</font></td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e 3</td>
<td valign=top>[size+3]...[/size]</td>
<td valign=top>&lt;font size=+3&gt;...&lt;/font&gt;</td>
<td valign=top>[size+3]drei[/size]</td>
<td valign=top><font size="+3">drei</font></td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e 2</td>
<td valign=top>[size+2]...[/size]</td>
<td valign=top>&lt;font size=+2&gt;...&lt;/font&gt;</td>
<td valign=top>[size+2]zwei[/size]</td>
<td valign=top><font size="+2">zwei</font></td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e 1</td>
<td valign=top>[size+1]...[/size]</td>
<td valign=top>&lt;font size=+1&gt;...&lt;/font&gt;</td>
<td valign=top>[size+1]eins[/size]</td>
<td valign=top><font size="+1">eins</font></td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e -1</td>
<td valign=top>[size+1]...[/size]</td>
<td valign=top>&lt;font size=-1&gt;...&lt;/font&gt;</td>
<td valign=top>[size+1]-eins[/size]</td>
<td valign=top><font size="-1">-eins</font></td>
</tr>

<tr>
<td valign=top>Schriftgr&ouml;&szlig;e -2</td>
<td valign=top>[size-2]...[/size]</td>
<td valign=top>&lt;font size=-2&gt;...&lt;/font&gt;</td>
<td valign=top>[size-2]-zwei[/size]</td>
<td valign=top><font size="-2">-zwei</font></td>
</tr>

<tr>
<td valign=top>Schriftart</td>
<td valign=top>[face=Schriftart]...[/face]</td>
<td valign=top>&lt;font face=Schriftart<&gt;...&lt;/font&gt;</td>
<td valign=top>[face=courier]courier[/face]</td>
<td valign=top><font face=courier>courier</font></td>
</tr>
-->
<tr>
<td valign=top>Link</td>
<td valign=top>[url]...[/url]</td>
<td valign=top>&lt;a href=&quot&quot target=_blank&gt;...&lt;/a&gt;</td>
<td valign=top>[url]http:///[/url]</td>
<td valign=top><a href="http:///" target=_blank>http:///</a></td>
</tr>

<tr>
<td valign=top>Link</td>
<td valign=top>[url=Link]...[/url]</td>
<td valign=top>&lt;a href=&quotLink&quot target=_blank&gt;...&lt;/a&gt;</td>
<td valign=top>[url=http:///]Forum[/url]</td>
<td valign=top><a href="http:///" target=_blank>Forum</a></td>
</tr>

<tr>
<td valign=top>Email</td>
<td valign=top>[email]...[/email]</td>
<td valign=top>&lt;a href=&quot;mailto:&quot&gt;...&lt;/a&gt;</td>
<td valign=top>[email]mani.musterman@gib-es-nicht.de[/email]</td>
<td valign=top><a href="mailto:mani.musterman@gib-es-nicht.de">mani.musterman@gib-es-nicht.de</a></td>
</tr>

<tr>
<td valign=top>Email</td>
<td valign=top>[email=Addi]...[/email]</td>
<td valign=top>&lt;a href=&quot;mailto:&quot&gt;...&lt;/a&gt;</td>
<td valign=top>[email=mani.musterman@gib-es-nicht.de]Mail an Mani Mustermann[/email]</td>
<td valign=top><a href="mailto:mani.musterman@gib-es-nicht.de">Mail an Mani Mustermann</a></td>
</tr>

<tr>
<td valign=top>Bild</td>
<td valign=top>[img]...[/img]</td>
<td valign=top>&lt;img src=&quot;&quot; border=0&gt;</td>
<td valign=top>[img]rotes-x.gif[/img]</td>
<td valign=top><img src="nixda.gif" border=0></td>
</tr>

<tr>
<td valign=top>Unnumerierte Liste</td>
<td valign=top>[ul]...[/ul]</td>
<td valign=top>&lt;ul&gt;...&lt;/ul&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Numerierte Liste</td>
<td valign=top>[ol]...[/ol]</td>
<td valign=top>&lt;ol&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<!--
<tr>
<td valign=top>Numerierte Liste - 1, 2, 3...</td>
<td valign=top>[ol=1]...[/ol]</td>
<td valign=top>&lt;ol type=1&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Numerierte Liste - i, ii, iii...</td>
<td valign=top>[ol=i]...[/ol]</td>
<td valign=top>&lt;ol type=i&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Numerierte Liste - I, II, III...</td>
<td valign=top>[ol=I]...[/ol]</td>
<td valign=top>&lt;ol type=I&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Numerierte Liste - a, b, c...</td>
<td valign=top>[ol=a]...[/ol]</td>
<td valign=top>&lt;ol type=a&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Numerierte Liste - A, B, C...</td>
<td valign=top>[ol=A]...[/ol]</td>
<td valign=top>&lt;ol type=A&gt;...&lt;/ol&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
-->
<tr>
<td valign=top>Zeile in numerierter und unnumerierter Liste</td>
<td valign=top>[li]...[/li]</td>
<td valign=top>&lt;li&gt;...&lt;/li&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<!--
<tr>
<td valign=top>Tabelle</td>
<td valign=top>[table]...[/table]</td>
<td valign=top>&lt;table&gt;...&lt;/table&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabelle mit Rand 1-5</td>
<td valign=top>[table=n]...[/table]</td>
<td valign=top>&lt;table border=n-5&gt;...&lt;/table&gt;</td>
<td>n=1, 2, 3, 4 oder 5</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabellenzeile</td>
<td valign=top>[tr]...[/tr]</td>
<td valign=top>&lt;tr&gt;...&lt;/tr&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabellenzelle</td>
<td valign=top>[td]...[/td]</td>
<td valign=top>&lt;td&gt;...&lt;/td&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabellenzelle vertikale Ausrichtung oben</td>
<td valign=top>[td top]...[/td]</td>
<td valign=top>&lt;td valign=top&gt;...&lt;/td&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabellenzelle vertikale Ausrichtung unten</td>
<td valign=top>[td bottom]...[/td]</td>
<td valign=top>&lt;td valign=bottom&gt;...&lt;/td&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>Tabellenzelle vertikale Ausrichtung zentriert</td>
<td valign=top>[td center]...[/td]</td>
<td valign=top>&lt;td valign=center&gt;...&lt;/td&gt;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>

<tr>
<td valign=top>&Uuml;berschriften 1-4</td>
<td valign=top>[hn]...[/hn]</td>
<td valign=top>&lt;hn&gt;...&lt;/hn&gt;</td>
<td>n=1,2,3 oder 4</td>
<td>&nbsp;</td>
</tr>

-->
<tr>
<td valign=top>Schriftfarben</td>
<td valign=top>[farbe]...[/farbe]</td>
<td valign=top>&lt;font color=farbe&gt;...&lt;/font&gt;</td>
<td>f&uuml;r &quot;farbe&quot; eines der folgenden farbworte setzen</td>
<td>
<font color=#000000>black</font> <font color=#800000>maroon</font><br>
<font color=#008000>green</font> <font color=#808000>olive</font><br>
<font color=#000080>navy</font> <font color=#800080>purple</font><br>
<font color=#008080>teal</font> <font color=#0c0c0c>silver</font><br>
<font color=#0c0c0c>gray</font> <font color=#ff0000>red</font><br>
<font color=#00ff00>lime</font> <font color=#ffff00>yellow</font><br>
<font color=#0000ff>blue</font> <font color=#ff00ff>fuchsia</font><br>
<font color=#00ffff>aqua</font> <font color=#ffffff>white</font>
</td>
</tr>
<!--
<tr>
<td valign=top>Schriftfarben</td>
<td valign=top>[color=xy]...[/color]</td>
<td valign=top>&lt;font color=xy&gt;...&lt;/font&gt;</td>
<td valign=top>&nbsp;</td>
<td valign=top>Für xy können die Farbw&ouml;rter wie oben dargestellt gesetzt werden oder RGB-Werte im Hex-Format, z.B. #ff0000 f&uuml;r rot.</td>
</tr>
-->
</table></center><br>

<p align=justify>Bitte beachten Sie, dass Sie die Pseudotags in exakt der dargestellten Schreibweise verwenden m&uuml;ssen, also weder Leerzeichen noch Gro&szlig;buchstaben d&uuml;rfen verwendet werden.<br><br> F&uuml;r den HTML-Tag &lt;br&gt; gibt es keine Entsprechung, da Sie dies durch gew&ouml;hnliches Dr&uuml;cken der Enter-Taste beim Schreiben eines Beitrags erreichen k&ouml;nnen.</p>

<?php 
EchoFooter();
?>  
