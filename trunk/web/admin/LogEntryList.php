<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/../model/ForumDb.php';


/**
 * Description of LogEntryList
 *
 * @author eli
 */
class LogEntryList {
    
    public function RenderHtmlDiv(ForumDb $db, int $maxIdLogValue = 0)
    {
        $haveSome = false;
        $query = 'SELECT l.idlog AS idlog, '
            . 'l.ts AS ts, lt.description AS description, '
            . 'l.iduser AS iduser, l.historic_user_context AS historic_user_context, '
            . 'u.nick AS nick, '
            . 'l.message AS message, l.request_uri AS request_uri, '
            . 'l.ip_address AS ip_address, l.admin_iduser AS admin_iduser '
            . 'FROM ((log_table l LEFT JOIN user_table u '
            . 'ON((l.iduser = u.iduser))) LEFT JOIN log_type_table lt '
            . 'ON((lt.idlog_type = l.idlog_type))) '
            . 'WHERE idlog <= :idlog '
            . 'ORDER BY l.idlog desc limit 100';        
        if($maxIdLogValue === 0)
        {
            $query = 'SELECT l.idlog AS idlog, '
                . 'l.ts AS ts, lt.description AS description, '
                . 'l.iduser AS iduser, l.historic_user_context AS historic_user_context, '
                . 'u.nick AS nick, '
                . 'l.message AS message, l.request_uri AS request_uri, '
                . 'l.ip_address AS ip_address, l.admin_iduser AS admin_iduser '
                . 'FROM ((log_table l LEFT JOIN user_table u '
                . 'ON((l.iduser = u.iduser))) LEFT JOIN log_type_table lt '
                . 'ON((lt.idlog_type = l.idlog_type))) '
                . 'ORDER BY l.idlog desc limit 100';
        }
        $stmt = $db->prepare($query);
        if($maxIdLogValue === 0)
        {
            $stmt->execute();
        }
        else
        {
            $stmt->execute(array(':idlog' => $maxIdLogValue));
        }
        $htmlTable = '<div><table>';
        $htmlTable.= '<tr>'
                . '<th>Id</th>'
                . '<th>Datum</th>'
                . '<th>Aktion</th>'
                . '<th>Nick (UserId)</th>'
                . '<th>Damaliger User Context</th>'
                . '<th>AdminId</th>'
                . '<th>Nachricht</th>'
                . '<th>IP Adresse</th>'
                . '<th>Url</th>'
                . '</tr>';
        while($row = $stmt->fetch())
        {
            $haveSome = true;
            $logTimestamp = new DateTime($row['ts']);
            $htmlTable.= '<tr>';
            $htmlTable.= '<td>' . $row['idlog'] . '</td>';
            $htmlTable.= '<td>' . $logTimestamp->format('d.m.Y H:i:s') . '</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['description']) . '</td>';
            if($row['iduser'])
            {
                $htmlTable.= '<td>' . htmlspecialchars($row['nick']) . ' ('
                        . $row['iduser'] . ')</td>';
            }
            else
            {
                $htmlTable.= '<td></td>';
            }
            if($row['historic_user_context'])
            {
                $htmlTable.= '<td>' . htmlspecialchars($row['historic_user_context']) . '</td>';                
            }
            else
            {
                $htmlTable.= '<td></td>';                
            }
            if($row['admin_iduser'])
            {
                $htmlTable.= '<td>' . $row['admin_iduser'] . '</td>';
            }
            else
            {
                $htmlTable.= '<td></td>';
            }
            $htmlTable.= '<td>' . htmlspecialchars($row['message']) . '</td>';
            $htmlTable.= '<td>' . $row['ip_address'] . '</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['request_uri']) . '</td>';
            $htmlTable.= '</tr>';
        }
        $htmlTable.= '</table></div>';
        if($haveSome)
        {
            return $htmlTable;
        }
        else
        {
            return '<div class="fitalic noTableEntries">Keine Logeinträge gefunden (was nicht möglich ist!)??</div>';
        }
    }
}
