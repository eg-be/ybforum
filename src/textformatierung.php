<!DOCTYPE html>
<?php

/**
 * Copyright 2017 Elias Gerber <eg@zame.ch>
 *
 * This file is part of YbForum1898.
 *
 * YbForum1898 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * YbForum1898 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with YbForum1898.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__ . '/pageparts/TopNavigation.php';
require_once __DIR__ . '/pageparts/Logo.php';
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r183">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <style>
            table, th, td
            {
                text-align: left;
                border-top: 1px solid #ffffff;
                border-left: 1px solid #ffffff;
                border-bottom: 1px solid gray;
                border-right: 1px solid gray;
            }
            th, td
            {
                border-bottom: 1px solid #ffffff;
                border-right: 1px solid #ffffff;
                border-top: 1px solid gray;
                border-left: 1px solid gray;
                padding-left: 0.2em;
                padding-right: 0.2em;
                padding-top: 0.3em;
                padding-bottom: 0.3em;
            }
            .formatsample
            {
                /* Dummy to avoid warning about class not found */
            }
        </style>
        <script src="js/renderpost.js"></script>
        <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
        <script type="text/javascript">
        $( document ).ready(function() {
            $( ".formatsample" ).each(function() {
                renderSpans($(this));
                renderHtmlTags($(this));
                renderImgTags($(this));
                renderEmailTags($(this));
                renderColors($(this));
            });
        });
        </script>
    </head>
    <body>
        <?php
        try {
            $logo = new Logo();
            echo $logo->renderHtmlDiv();
        } catch (Exception $ex) {
            ErrorHandler::onException($ex);
        }
?>
        <div class="fullwidthcenter generictitle">Textformatierung</div>
        <hr>
        <?php
try {
    $topNav = new TopNavigation();
    echo $topNav->renderHtmlDiv();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        <hr>
        <div class="fullwidth">
            <p>
            Die folgende Tabelle zeigt die Möglichkeiten einen Beitrag zu formatieren:
            </p>
            <table style="margin: auto;">
                <tr>
                    <th>Formatierung</th><th>Pseudotag</th><th>Beispiel/Erläuterung</th><th>Anzeige</th>
                </tr>
                <tr>
                    <td>Fettschrift</td><td>[b]...[/b]</td><td>[b]Dies ist Fettschrift.[/b]</td><td class="formatsample">[b]Dies ist Fettschrift.[/b]</td>
                </tr>
                <tr>
                    <td>Kursivschrift</td><td>[i]...[/i]</td><td>[i]Dies ist Kursivschrift.[/i]</td><td class="formatsample">[i]Dies ist Kursivschrift.[/i]</td>
                </tr>
                <tr>
                    <td>Unterstreichen</td><td>[u]...[/u]</td><td>[u]Dies ist unterstrichen.[/u]</td><td class="formatsample">[u]Dies ist unterstrichen.[/u]</td>
                </tr>
                <tr>
                    <td>Link</td><td>[url]...[/url]</td><td>[url]https://letsencrypt.org/[/url]</td><td class="formatsample">[url]https://letsencrypt.org/[/url]</td>
                </tr>
                <tr>
                    <td>Link</td><td>[url=Link]...[/url]</td><td>[url=https://letsencrypt.org/]Let's Encrypt[/url]</td><td class="formatsample">[url=https://letsencrypt.org/]Let's Encrypt[/url]</td>
                </tr>
                <tr>
                    <td>Email</td><td>[email]...[/email]</td><td>[email]mani.musterman@gib-es-nicht.de[/email]</td><td class="formatsample">[email]mani.musterman@gib-es-nicht.de[/email]</td>
                </tr>
                <tr>
                    <td>Email</td><td>email=Addi]...[/email]</td><td>[email=mani.musterman@gib-es-nicht.de]Mail an Mani Mustermann[/email]</td><td class="formatsample">[email=mani.musterman@gib-es-nicht.de]Mail an Mani Mustermann[/email]</td>
                </tr>
                <tr>
                    <td>Bild</td><td>[img]...[/img]</td><td>[img]http://www.bscyb.ch/images/2014/yb-logo.png[/img]</td><td class="formatsample">[img]http://www.bscyb.ch/images/2014/yb-logo.png[/img]</td>
                </tr>
                <tr>
                    <td>Schriftfarben</td><td>[farbe]...[/farbe]</td><td>Dieser Text ist [yellow]Gelb[/yellow]-[black]Schwarz[/black]</td>
                    <td class="formatsample">
                        Dieser Text ist [yellow]Gelb[/yellow]-[black]Schwarz[/black]<br>
                        Die folgenden Werte für [i]farbe[/i] stehen zur Verfügung:<br>
                        [black]black[/black] [maroon]maroon[/maroon]
                        [green]green[/green] [olive]olive[/olive]
                        [navy]navy[/navy] [purple]purple[/purple]
                        [teal]teal[/teal] [silver]silver[/silver]
                        [gray]gray[/gray] [red]red[/red]
                        [lime]lime[/lime] [yellow]yellow[/yellow]
                        [blue]blue[/blue] [fuchsia]fuchsia[/fuchsia]
                        [aqua]aqua[/aqua] [white]white[/white]
                    </td>
                </tr>
            </table>
            <p>
                Die Pseudotags müssen exakt in der dargestellten Schreibweise verwenden werden (keine Leerzeichen, alle Zeichen in Kleinschreibung).
            </p>
        </div>
        <?php
include __DIR__ . '/pageparts/StandWithUkr.php';
?>
    </body>
</html>
