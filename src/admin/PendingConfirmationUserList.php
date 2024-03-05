<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/../model/ForumDb.php';

/**
 * Description of PendingConfirmationUserList
 *
 * @author eli
 */
class PendingConfirmationUserList 
{
    const PARAM_PENDINGCONFIRM_ACTION = 'pendingconfirm_action';
    const VALUE_CONFIRM = 'pendingconfirm_confirm';
    const VALUE_DELETE = 'pendingconfirm_delete';
    const VALUE_DELETE_AND_BLOCK = 'pendingconfirm_delete_and_block';
    
    const PARAM_USERID = 'pendingconfirm_userid';        
    
    public function __construct()
    {
        
    }
    
    public function HandleActionsAndGetResultDiv(ForumDb $db) : string
    {
        try
        {
            $resultDiv = '';
            $userId = filter_input(INPUT_POST, self::PARAM_USERID, FILTER_VALIDATE_INT);
            $userActionValue = filter_input(INPUT_POST, self::PARAM_PENDINGCONFIRM_ACTION, FILTER_UNSAFE_RAW);
            $user = null;
            if($userId > 0)
            {
                $user = User::LoadUserById($db, $userId);
            }
            if($user && $userActionValue === self::VALUE_DELETE)
            {
                // If this is the registration of a new user, also delete
                // the corresponding entry in the user table
                $confirmReason = $db->GetConfirmReason($user);
                $db->RemoveConfirmUserCode($user);
                if($confirmReason == ForumDb::CONFIRM_SOURCE_NEWUSER)
                {
                    $db->DeleteUser($user);
                    $resultDiv = '<div class="actionSucceeded">Registerungs-Eintrag für Benutzer ' 
                            . $user->GetNick() . ' (' . $user->GetId() .') '
                            . 'entfernt (inkl. Benutzereintrag)</div>';
                }
                else
                {
                    $resultDiv = '<div class="actionSucceeded">Migrations-Eintrag für Benutzer ' 
                            . $user->GetNick() . ' (' . $user->GetId() . ') '
                            . 'entfernt</div>';
                }
            }
            else if($user && $userActionValue === self::VALUE_DELETE_AND_BLOCK)
            {
                // If this is the registration of a new user, also delete
                // the corresponding entry in the user table and add it to the
                // list of blocked emails
                $confirmReason = $db->GetConfirmReason($user);
                $db->RemoveConfirmUserCode($user);
                if($confirmReason == ForumDb::CONFIRM_SOURCE_NEWUSER)
                {
                    $db->AddBlacklist($user->GetEmail(), 'Blocked from admin');
                    $db->DeleteUser($user);
                    $resultDiv = '<div class="actionSucceeded">Registerungs-Eintrag für Benutzer ' 
                            . $user->GetNick() . ' (' . $user->GetId() .') '
                            . 'entfernt (inkl. Benutzereintrag), '
                            . 'Mailadresse ' . $user->GetEmail() 
                            . ' blockiert</div>';
                }
                else
                {
                    $resultDiv = '<div class="actionSucceeded">Migrations-Eintrag für Benutzer ' 
                            . $user->GetNick() . ' (' . $user->GetId() . ') '
                            . 'entfernt</div>';
                }
            }            
            else if($user && $userActionValue === self::VALUE_CONFIRM)
            {
                // Read the values required for confirming
                $query = 'SELECT email, password, confirm_source '
                        . 'FROM confirm_user_table '
                        . 'WHERE iduser = :iduser';
                $stmt = $db->prepare($query);
                $stmt->execute(array(':iduser' => $user->GetId()));
                $result = $stmt->fetch();
                if(!$result)
                {
                    throw new InvalidArgumentException('No row found in '
                            . 'confirm_user_table matching iduser '
                            . $user->GetId());
                }
                $password = $result['password'];
                $email = $result['email'];
                $confirmSource = $result['confirm_source'];
                $activate = ($confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE);
                $db->RemoveConfirmUserCode($user);
                $db->ConfirmUser($user, $password, $email, $activate);
                $resultDiv = '<div class="actionSucceeded">Benutzer ' 
                        . $user->GetNick() . ' (' . $user->GetId() . ')'
                        . 'bestätigt (Aktiviert: ' 
                        . ($activate ? 'Ja' : 'Nein') . ')</div>';
            }
            return $resultDiv;
        }
        catch(InvalidArgumentException $ex)
        {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }    
    
    public function RenderHtmlDiv(ForumDb $db) : string
    {
        $haveSome = false;
        $query = 'SELECT cut.iduser AS iduser, u.nick AS nick, '
                . 'cut.email AS email, cut.confirm_code AS confirm_code, '
                . 'cut.request_date AS request_date, '
                . 'cut.request_ip_address AS request_ip_address, '
                . 'cut.confirm_source AS confirm_source '
                . 'FROM confirm_user_table cut '
                . 'LEFT JOIN user_table u ON cut.iduser = u.iduser '
                . 'ORDER BY cut.iduser';
        $stmt = $db->prepare($query);
        $stmt->execute();
        $htmlTable = '<div><table class="actiontable">';
        $htmlTable.= '<tr>'
                . '<th>Nick (UserId)</th>'
                . '<th>Email</th>'
                . '<th>Request Datum</th>'
                . '<th>Request IP</th>'
                . '<th>Request Quelle</th>'
                . '<th>Code</th>'                
                . '</tr>';
        while($row = $stmt->fetch())
        {
            $haveSome = true;
            $requestDate = new DateTime($row['request_date']);
            $codeTooOld = !$db->IsDateWithinConfirmPeriod($requestDate);
            $htmlTable.= '<tr>';
            $htmlTable.= '<td>' . htmlspecialchars($row['nick']) 
                    . ' (' . $row['iduser'] . ')</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['email']) . '</td>';
            if($codeTooOld)
            {
                if($row['confirm_source'] === ForumDb::CONFIRM_SOURCE_NEWUSER)
                {
                    $htmlTable.= '<td class="colorNotice fbold">';
                }
                else
                {
                    $htmlTable.= '<td class="colorNotice">';                    
                }
            }
            else
            {
                $htmlTable.= '<td>';
            }
            $htmlTable.= $requestDate->format('d.m.Y H:i:s') . '</td>';
            $htmlTable.= '<td>' . $row['request_ip_address'] . '</td>';
            $htmlTable.= '<td>' . htmlspecialchars($row['confirm_source']) . '</td>';
            $htmlTable.= '<td style="word-wrap: break-word;">' . htmlspecialchars($row['confirm_code']) . '</td>';            
            $htmlTable.= '<td>';
            $htmlTable.= 
                      '<form method="post" action="" accept-charset="utf-8">'
                    . '<input type="submit" value="Eintrag löschen"/>'
                    . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $row['iduser'] . '"/>'
                    . '<input type="hidden" name="' . self::PARAM_PENDINGCONFIRM_ACTION . '" value="' . self::VALUE_DELETE . '"/>'
                    . '</form>';
            if($row['confirm_source'] === ForumDb::CONFIRM_SOURCE_NEWUSER)
            {
                $htmlTable.= '<form method="post" action="" accept-charset="utf-8">'
                    . '<input type="submit" value="Eintrag löschen und Mailadresse blockieren"/>'
                    . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $row['iduser'] . '"/>'
                    . '<input type="hidden" name="' . self::PARAM_PENDINGCONFIRM_ACTION . '" value="' . self::VALUE_DELETE_AND_BLOCK . '"/>'
                    . '</form>';
            }
            $htmlTable.= '<form method="post" action="" accept-charset="utf-8">'
                    . '<input type="submit" value="Manuell bestätigen"/>'
                    . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $row['iduser'] . '"/>'
                    . '<input type="hidden" name="' . self::PARAM_PENDINGCONFIRM_ACTION . '" value="' . self::VALUE_CONFIRM . '"/>'
                    . '</form>'
                    . '</td>';
            $htmlTable.= '</tr>';
        }
        $htmlTable.= '</table></div>';
        if($haveSome)
        {
            return $htmlTable;
        }
        else
        {
            return '<div class="fitalic noTableEntries">Keine Einträge vorhanden die auf eine Bestätigung durch einen Benutzer warten.</div>';
        }
    }
}
