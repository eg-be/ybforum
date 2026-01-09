<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/../model/ForumDb.php';


/**
 * Description of AdminList
 *
 * @author eli
 */
class AdminList
{
    public function RenderHtmlDiv(ForumDb $db): string
    {
        $haveSome = false;
        $query = 'SELECT iduser, nick, email FROM user_table '
                . 'WHERE admin > 0';
        $stmt = $db->prepare($query);
        $stmt->execute();
        $htmlTable = '<div><table>';
        $htmlTable .= '<tr>'
                . '<th>Nick (UserId)</th>'
                . '<th>Email</th>'
                . '</tr>';
        while ($row = $stmt->fetch()) {
            $haveSome = true;
            $htmlTable .= '<tr>';
            $htmlTable .= '<td>' . htmlspecialchars($row['nick'])
                    . ' (' . $row['iduser'] . ')</td>';
            $htmlTable .= '<td>' . htmlspecialchars($row['email']) . '</td>';
            $htmlTable .= '</tr>';
        }
        $htmlTable .= '</table></div>';
        if ($haveSome) {
            return $htmlTable;
        } else {
            return '<div class="fitalic noTableEntries">Keine Admins vorhanden (und was bist du??)</div>';
        }
    }
}
