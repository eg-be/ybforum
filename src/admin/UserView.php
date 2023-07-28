<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/../model/ForumDb.php';

/**
 * Description of UserView
 *
 * @author eli
 */
class UserView {
    
    const PARAM_USERACTION = 'userview_action';
    const VALUE_ACTIVATE = 'userview_activate';
    const VALUE_DEACTIVATE = 'userview_deactivate';
    const VALUE_SETADMIN = 'userview_setadmin';
    const VALUE_REMOVEADMIN = 'userview_removeadmin';
    const VALUE_MAKEDUMMY = 'userview_makedummy';
    const VALUE_CONFIRM_MAKE_DUMMY = 'userview_confirmmakedummy';
    const VALUE_DELETE = 'userview_delete';
    
    const PARAM_USERID = 'userview_userid';
    const PARAM_NICK_OR_EMAIL = 'userview_nickoremail';
    const PARAM_REASON = 'userview_reason';
    
    public function __construct()
    {
        $this->m_userId = null;
        $this->m_nick = null;
        $this->m_email = null;       
                
        $this->m_userId = filter_input(INPUT_POST, self::PARAM_USERID, FILTER_VALIDATE_INT);
        if(!$this->m_userId)
        {
            $this->m_userId = null;
            $this->m_email = filter_input(INPUT_POST, self::PARAM_NICK_OR_EMAIL, FILTER_VALIDATE_EMAIL);
            if(!$this->m_email)
            {
                $this->m_email = null;
                $this->m_nick = filter_input(INPUT_POST, self::PARAM_NICK_OR_EMAIL, FILTER_UNSAFE_RAW);
                if($this->m_nick === false)
                {
                    $this->m_nick = null;
                }
            }
        }
    }
    
    public function HandleActionsAndGetResultDiv(ForumDb $db, int $adminUserId) : string
    {
        try
        {
            $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
            if($userActionValue === self::VALUE_ACTIVATE || $userActionValue === self::VALUE_DEACTIVATE)
            {
                return $this->HandleActivateAction($db, $adminUserId);
            }
            else if($userActionValue === self::VALUE_SETADMIN || $userActionValue === self::VALUE_REMOVEADMIN)
            {
                return $this->HandleAdminAction($db);
            }
            else if($userActionValue === self::VALUE_MAKEDUMMY || $userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY)
            {
                return $this->HandleDummyAction($db);
            }
            else if($userActionValue === self::VALUE_DELETE)
            {
                return $this->HandleDeleteAction($db);
            }
            else {
                return '';
            }
        }
        catch(InvalidArgumentException $ex)
        {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }
    
    private function HandleDummyAction(ForumDb $db) : string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if($userActionValue === self::VALUE_MAKEDUMMY && $this->m_userId)
        {
            $user = User::LoadUserById($db, $this->m_userId);            
            $htmlStr = '<div class="actionConfirm">ACHTUNG: Durch diese Operation '
                    . 'wird der Stammposter (nahezu) unumkehrbar entfernt. Nur '
                    . 'sein Nick bleibt erhalten. Sicher dass der Stammposter '
                    . '<span class="fitalic">'
                    . $user->GetNick()
                    . '</span> zu einem Dummy gemacht '
                    . 'werden soll?';
            $htmlStr.= $this->GetConfirmTurnInfoDummyForm($user);
            $htmlStr.= '</div>';
            return $htmlStr;
        }
        else if($userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY && $this->m_userId)
        {
            $user = User::LoadUserById($db, $this->m_userId);
            $db->MakeDummy($user->GetId());
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' ist jetzt ein Dummy</div>';
        }
        else
        {
            return '';
        }
    }
    
    private function HandleDeleteAction(ForumDb $db) : string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if($userActionValue === self::VALUE_DELETE && $this->m_userId)
        {
            $user = User::LoadUserById($db, $this->m_userId);
            $db->DeleteUser($user->GetId());
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' gelöscht</div>';
        }
        else if($userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY && $this->m_userId)
        {
            $user = User::LoadUserById($db, $this->m_userId);
            $db->MakeDummy($user->GetId());
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' ist jetzt ein Dummy</div>';
        }
        else
        {
            return '';
        }
    }
    
    private function HandleAdminAction(ForumDb $db) : string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if($userActionValue === self::VALUE_SETADMIN && $this->m_userId)
        {
            $db->SetAdmin($this->m_userId, true);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' ist jetzt Admin</div>';
        }
        else if($userActionValue === self::VALUE_REMOVEADMIN && $this->m_userId)
        {
            $db->SetAdmin($this->m_userId, false);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' wurden Admin-Rechte entzogen</div>';
        }
        else
        {
            return '';
        }
    }
    
    private function HandleActivateAction(ForumDb $db, int $adminUserId) : string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if($userActionValue === self::VALUE_ACTIVATE && $this->m_userId)
        {
            $db->ActivateUser($this->m_userId);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' aktiviert</div>';
        }
        else if($userActionValue === self::VALUE_DEACTIVATE && $this->m_userId)
        {
            $reason = filter_input(INPUT_POST, self::PARAM_REASON, FILTER_UNSAFE_RAW);
            if(!$reason)
            {
                return '<div class="actionFailed">Es muss ein Grund angegeben werden</div>';
            }
            $db->DeactivateUser($this->m_userId, $reason, $adminUserId);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' deaktiviert</div>';
        }
        else 
        {
            return '';
        }        
    }
    
    private function LoadUser(ForumDb $db) : ?User
    {
        $user = null;
        if($this->m_userId)
        {
            $user = User::LoadUserById($db, $this->m_userId);
        }
        else if($this->m_email)
        {
            $user = User::LoadUserByEmail($db, $this->m_email);
        }
        else
        {
            $user = User::LoadUserByNick($db, $this->m_nick);
        }
        return $user;        
    }
    
    private function GetTurnIntoDummyForm(User $user) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr.= '<input type="submit" value="Zu Dummy machen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_MAKEDUMMY . '"/>';
        $htmlStr.= '</form>';
        return $htmlStr;
    }
    
    private function GetDeleteUserForm(User $user) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr.= '<input type="submit" value="Stammposter endgültig löschen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_DELETE . '"/>';
        $htmlStr.= '</form>';
        return $htmlStr;
    }
    
    private function GetConfirmTurnInfoDummyForm(User $user) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr.= '<input type="submit" value="Stammposter ' . $user->GetNick() . ' zu einem Dummy machen Bestätigen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_CONFIRM_MAKE_DUMMY . '"/>';
        $htmlStr.= '</form>';
        return $htmlStr;        
    }
    
    private function GetToggleActiveForm(User $user) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        if($user->IsActive())
        {
            $htmlStr.= '<input type="submit" value="Deaktivieren"/>Grund: <input type="text" name="' . self::PARAM_REASON . '"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_DEACTIVATE . '"/>';
        }
        else
        {
            if($user->IsConfirmed())
            {
                $htmlStr.= '<input type="submit" value="Aktivieren"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_ACTIVATE . '"/>';
            }
            else
            {
                $htmlStr.= '<span class="hint">Benutzer ohne bestätige Mailadresse können nicht aktiviert werden</span>';
            }
        }
        $htmlStr.= '</form>';
        return $htmlStr;
    }
    
    private function GetToggleAdminForm(User $user) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        if($user->IsAdmin())
        {
            $htmlStr.= '<input type="submit" value="Adminrechte entziehen"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_REMOVEADMIN . '"/>';
        }
        else
        {
            if($user->IsConfirmed())
            {
                $htmlStr.= '<input type="submit" value="Adminrechte vergeben"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_SETADMIN . '"/>';
            }
            else
            {
                $htmlStr.= '<span class="hint">Benutzer ohne bestätige Mailadresse können nicht zu einem Admin werden</span>';
            }
        }
        $htmlStr.= '</form>';
        return $htmlStr;
    }
    
    public function RenderHtmlDiv(ForumDb $db) : string
    {
        if(!$this->m_userId && !$this->m_email && !$this->m_nick)
        {
            return '<div></div>';            
        }
        $user = $this->LoadUser($db);
        if(!$user)
        {
            if($this->m_userId)
            {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit BenutzerId ' . $this->m_userId . '</div>';
            }
            else if($this->m_email)
            {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit Mailadresse ' . $this->m_email . '</div>';                
            }
            else
            {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit Nick ' . $this->m_nick . '</div>';                
            }
        }
        
        $htmlStr = '<div><table class="actiontable">';
        $htmlStr.= '<tr><td>Id:</td><td>' . $user->GetId() . '</td><td></td></tr>';        
        $htmlStr.= '<tr><td>Stammpostername:</td><td>' . htmlspecialchars($user->GetNick()) . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Email:</td><td>' . htmlspecialchars($user->GetEmail()) . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Registriert seit:</td><td>' . $user->GetRegistrationTimestamp()->format('d.m.Y H:i:s') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Registrierungsnachricht:</td><td>' . $user->GetRegistrationMsg() . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Email bestätigt am:</td><td>' . ($user->GetConfirmationTimestamp() ? $user->GetConfirmationTimestamp()->format('d.m.Y H:i:s') : '') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Aktiv:</td><td>' . ($user->IsActive() ? 'Ja' : 'Nein') 
                . '</td><td>'
                . $this->GetToggleActiveForm($user)
                . '</td></tr>';
        $htmlStr.= '<tr><td>Admin:</td><td>' . ($user->IsAdmin() ? 'Ja' : 'Nein') 
                . '</td><td>' 
                . $this->GetToggleAdminForm($user)
                . '</td></tr>';
        $htmlStr.= '<tr><td>Dummy:</td><td>' . ($user->IsDummyUser() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Hat neues Passwort</td><td>' . ($user->HasPassword() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Hat altes Passwort</td><td>' . ($user->HasOldPassword() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $postByUserCount = $db->GetPostByUserCount($user->GetId());
        $htmlStr.= '<tr><td>Anzahl Posts</td><td>' . $postByUserCount . '</td><td>';
        if($postByUserCount > 0 && !$user->IsDummyUser())
        {
            $htmlStr.= $this->GetTurnIntoDummyForm($user);
        }
        else if($postByUserCount == 0)
        {
            $htmlStr.= $this->GetDeleteUserForm($user);
        }
        $htmlStr.= '</td></tr>';
        $htmlStr.= '</table></div>';
        return $htmlStr;
    }
    
    private $m_userId;
    private $m_email;
    private $m_nick;
}
