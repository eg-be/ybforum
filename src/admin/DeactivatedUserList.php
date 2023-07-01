<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/../model/ForumDb.php';

/**
 * Description of DeactivatedUserList
 *
 * @author eli
 */
class DeactivatedUserList {
    
    public function RenderHtmlDiv(ForumDb $db) : string
    {
        $haveSome = false;
        $query = 'SELECT d.iduser AS deactivated_id, '
                . 'u1.nick AS deactivated_nick, u1.email AS deactivated_email, '
                . 'u2.nick AS deactivated_bynick, '
                . 'd.reason AS reason, d.deactivated_ts AS deactivated_ts '
                . 'FROM ((user_deactivated_reason_table d JOIN user_table u1 '
                . 'ON((u1.iduser = d.iduser))) JOIN user_table u2 '
                . 'ON((u2.iduser = d.deactivated_by_iduser))) '
                . 'WHERE (u1.active = 0)';
        $stmt = $db->prepare($query);
        $stmt->execute();
        $htmlTable = '<div><table>';
        $htmlTable.= '<tr>'
                . '<th>Nick (UserId)</th>'
                . '<th>Email</th>'
                . '<th>Deaktviert seit</th>'
                . '<th>Deaktivert von</th>'
                . '<th>Grund</th>'
                . '</tr>';
        while($row = $stmt->fetch())
        {
            $haveSome = true;
            $deactivatedDate = new DateTime($row['deactivated_ts']);
            $htmlTable.= '<tr>';
            $htmlTable.= '<td>' . htmlspecialchars($row['deactivated_nick']) 
                    . ' (' . $row['deactivated_id'] . ')</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['deactivated_email']) . '</td>';
            $htmlTable.= '<td>' . $deactivatedDate->format('d.m.Y H:i:s') . '</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['deactivated_bynick']) . '</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['reason']) . '</td>';
            $htmlTable.= '</tr>';
        }
        $htmlTable.= '</table></div>';
        if($haveSome)
        {
            return $htmlTable;
        }
        else
        {
            return '<div class="fitalic noTableEntries">Keine deaktivierten Stammposter vorhanden</div>';
        }
    }
    
}
