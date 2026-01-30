<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/../model/ForumDb.php';

/**
 * Description of UserView
 *
 * @author eli
 */
class UserView
{
    public const PARAM_USERACTION = 'userview_action';
    public const VALUE_ACTIVATE = 'userview_activate';
    public const VALUE_DEACTIVATE = 'userview_deactivate';
    public const VALUE_SETADMIN = 'userview_setadmin';
    public const VALUE_REMOVEADMIN = 'userview_removeadmin';
    public const VALUE_MAKEDUMMY = 'userview_makedummy';
    public const VALUE_CONFIRM_MAKE_DUMMY = 'userview_confirmmakedummy';
    public const VALUE_DELETE = 'userview_delete';

    public const PARAM_USERID = 'userview_userid';
    public const PARAM_NICK_OR_EMAIL = 'userview_nickoremail';
    public const PARAM_REASON = 'userview_reason';

    public function __construct()
    {
        $this->m_userId = null;
        $this->m_nick = null;
        $this->m_email = null;

        $this->m_userId = filter_input(INPUT_POST, self::PARAM_USERID, FILTER_VALIDATE_INT);
        if (!$this->m_userId) {
            $this->m_userId = null;
            $this->m_email = filter_input(INPUT_POST, self::PARAM_NICK_OR_EMAIL, FILTER_VALIDATE_EMAIL);
            if (!$this->m_email) {
                $this->m_email = null;
                $this->m_nick = filter_input(INPUT_POST, self::PARAM_NICK_OR_EMAIL, FILTER_UNSAFE_RAW);
                if ($this->m_nick === false) {
                    $this->m_nick = null;
                }
            }
        }
    }

    public function handleActionsAndGetResultDiv(ForumDb $db, int $adminUserId): string
    {
        try {
            $admin = $db->LoadUserById($adminUserId);
            if (!($admin->IsAdmin() && $admin->IsActive())) {
                throw new InvalidArgumentException('Admin user required');
            }
            $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
            if ($userActionValue === self::VALUE_ACTIVATE || $userActionValue === self::VALUE_DEACTIVATE) {
                return $this->handleActivateAction($db, $admin);
            } elseif ($userActionValue === self::VALUE_SETADMIN || $userActionValue === self::VALUE_REMOVEADMIN) {
                return $this->handleAdminAction($db);
            } elseif ($userActionValue === self::VALUE_MAKEDUMMY || $userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY) {
                return $this->handleDummyAction($db);
            } elseif ($userActionValue === self::VALUE_DELETE) {
                return $this->handleDeleteAction($db);
            } else {
                return '';
            }
        } catch (InvalidArgumentException $ex) {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }

    private function handleDummyAction(ForumDb $db): string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if ($userActionValue === self::VALUE_MAKEDUMMY && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            $htmlStr = '<div class="actionConfirm">ACHTUNG: Durch diese Operation '
                    . 'wird der Stammposter (nahezu) unumkehrbar entfernt. Nur '
                    . 'sein Nick bleibt erhalten. Sicher dass der Stammposter '
                    . '<span class="fitalic">'
                    . $user->getNick()
                    . '</span> zu einem Dummy gemacht '
                    . 'werden soll?';
            $htmlStr .= $this->getConfirmTurnInfoDummyForm($user);
            $htmlStr .= '</div>';
            return $htmlStr;
        } elseif ($userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            $db->MakeDummy($user);
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' ist jetzt ein Dummy</div>';
        } else {
            return '';
        }
    }

    private function handleDeleteAction(ForumDb $db): string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if ($userActionValue === self::VALUE_DELETE && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            $db->DeleteUser($user);
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' gelöscht</div>';
        } elseif ($userActionValue === self::VALUE_CONFIRM_MAKE_DUMMY && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            $db->MakeDummy($user);
            return '<div class="actionSucceeded">Benutzer ' . $user->GetId() . ' ist jetzt ein Dummy</div>';
        } else {
            return '';
        }
    }

    private function handleAdminAction(ForumDb $db): string
    {
        $user = null;
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if (($userActionValue === self::VALUE_SETADMIN || $userActionValue === self::VALUE_REMOVEADMIN)
            && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            if (!$user) {
                throw new InvalidArgumentException('No user with id ' . $userId
                        . ' was found');
            }
        }
        if ($userActionValue === self::VALUE_SETADMIN && $user) {
            $db->SetAdmin($user, true);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' ist jetzt Admin</div>';
        } elseif ($userActionValue === self::VALUE_REMOVEADMIN && $user) {
            $db->SetAdmin($user, false);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' wurden Admin-Rechte entzogen</div>';
        } else {
            return '';
        }
    }

    private function handleActivateAction(ForumDb $db, User $admin): string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_USERACTION, FILTER_UNSAFE_RAW);
        if ($userActionValue === self::VALUE_ACTIVATE && $this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
            if (!$user) {
                throw new InvalidArgumentException('No user with id ' . $userId
                        . ' was found');
            }
            $db->ActivateUser($user);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' aktiviert</div>';
        } elseif ($userActionValue === self::VALUE_DEACTIVATE && $this->m_userId) {
            $reason = filter_input(INPUT_POST, self::PARAM_REASON, FILTER_UNSAFE_RAW);
            if (!$reason) {
                return '<div class="actionFailed">Es muss ein Grund angegeben werden</div>';
            }
            $user = $db->LoadUserById($this->m_userId);
            if (!$user) {
                throw new InvalidArgumentException('No user with id ' . $userId
                        . ' was found');
            }
            $db->DeactivateUser($user, $reason, $admin);
            return '<div class="actionSucceeded">Benutzer ' . $this->m_userId . ' deaktiviert</div>';
        } else {
            return '';
        }
    }

    private function loadUser(ForumDb $db): ?User
    {
        $user = null;
        if ($this->m_userId) {
            $user = $db->LoadUserById($this->m_userId);
        } elseif ($this->m_email) {
            $user = $db->LoadUserByEmail($this->m_email);
        } else {
            $user = $db->LoadUserByNick($this->m_nick);
        }
        return $user;
    }

    private function getTurnIntoDummyForm(User $user): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr .= '<input type="submit" value="Zu Dummy machen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_MAKEDUMMY . '"/>';
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    private function getDeleteUserForm(User $user): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr .= '<input type="submit" value="Stammposter endgültig löschen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_DELETE . '"/>';
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    private function getConfirmTurnInfoDummyForm(User $user): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        $htmlStr .= '<input type="submit" value="Stammposter ' . $user->getNick() . ' zu einem Dummy machen Bestätigen"/>'
                . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_CONFIRM_MAKE_DUMMY . '"/>';
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    private function getToggleActiveForm(User $user): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        if ($user->IsActive()) {
            $htmlStr .= '<input type="submit" value="Deaktivieren"/>Grund: <input type="text" name="' . self::PARAM_REASON . '"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_DEACTIVATE . '"/>';
        } else {
            if ($user->IsConfirmed()) {
                $htmlStr .= '<input type="submit" value="Aktivieren"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_ACTIVATE . '"/>';
            } else {
                $htmlStr .= '<span class="hint">Benutzer ohne bestätige Mailadresse können nicht aktiviert werden</span>';
            }
        }
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    private function getToggleAdminForm(User $user): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $user->GetId() . '"/>';
        if ($user->IsAdmin()) {
            $htmlStr .= '<input type="submit" value="Adminrechte entziehen"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_REMOVEADMIN . '"/>';
        } else {
            if ($user->IsConfirmed()) {
                $htmlStr .= '<input type="submit" value="Adminrechte vergeben"/>'
                        . '<input type="hidden" name="' . self::PARAM_USERACTION . '" value="' . self::VALUE_SETADMIN . '"/>';
            } else {
                $htmlStr .= '<span class="hint">Benutzer ohne bestätige Mailadresse können nicht zu einem Admin werden</span>';
            }
        }
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    public function renderHtmlDiv(ForumDb $db): string
    {
        if (!$this->m_userId && !$this->m_email && !$this->m_nick) {
            return '<div></div>';
        }
        $user = $this->loadUser($db);
        if (!$user) {
            if ($this->m_userId) {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit BenutzerId ' . $this->m_userId . '</div>';
            } elseif ($this->m_email) {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit Mailadresse ' . $this->m_email . '</div>';
            } else {
                return '<div class="fitalic noTableEntries">Kein Stammposter gefunden mit Nick ' . $this->m_nick . '</div>';
            }
        }

        $htmlStr = '<div><table class="actiontable">';
        $htmlStr .= '<tr><td>Id:</td><td>' . $user->GetId() . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Stammpostername:</td><td>' . htmlspecialchars($user->getNick()) . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Email:</td><td>' . ($user->HasEmail() ? htmlspecialchars($user->getEmail()) : '') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Registriert seit:</td><td>' . $user->GetRegistrationTimestamp()->format('d.m.Y H:i:s') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Registrierungsnachricht:</td><td>' . $user->GetRegistrationMsg() . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Email bestätigt am:</td><td>' . ($user->GetConfirmationTimestamp() ? $user->GetConfirmationTimestamp()->format('d.m.Y H:i:s') : '') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Aktiv:</td><td>' . ($user->IsActive() ? 'Ja' : 'Nein')
                . '</td><td>'
                . $this->getToggleActiveForm($user)
                . '</td></tr>';
        $htmlStr .= '<tr><td>Admin:</td><td>' . ($user->IsAdmin() ? 'Ja' : 'Nein')
                . '</td><td>'
                . $this->getToggleAdminForm($user)
                . '</td></tr>';
        $htmlStr .= '<tr><td>Dummy:</td><td>' . ($user->IsDummyUser() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Hat neues Passwort</td><td>' . ($user->HasPassword() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Hat altes Passwort</td><td>' . ($user->HasOldPassword() ? 'Ja' : 'Nein') . '</td><td></td></tr>';
        $postByUserCount = $db->GetPostByUserCount($user);
        $htmlStr .= '<tr><td>Anzahl Posts</td><td>' . $postByUserCount . '</td><td>';
        if ($postByUserCount > 0 && !$user->IsDummyUser()) {
            $htmlStr .= $this->getTurnIntoDummyForm($user);
        } elseif ($postByUserCount == 0) {
            $htmlStr .= $this->getDeleteUserForm($user);
        }
        $htmlStr .= '</td></tr>';
        $htmlStr .= '</table></div>';
        return $htmlStr;
    }

    private $m_userId;
    private $m_email;
    private $m_nick;
}
