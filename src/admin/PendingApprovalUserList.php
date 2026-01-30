<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/../model/ForumDb.php';
require_once __DIR__ . '/../helpers/Mailer.php';

/**
 * Description of PendingApprovalUserList
 *
 * @author eli
 */
class PendingApprovalUserList
{
    public const PARAM_PENDINGAPPROVAL_ACTION = 'pendingapproval_action';
    public const VALUE_ACCEPT = 'pendingapproval_accept';
    public const VALUE_DENY = 'pendingapproval_deny';

    public const PARAM_USERID = 'pendingapproval_userid';

    public function __construct()
    {
        $this->m_clientIpAddress = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
    }

    public function handleActionsAndGetResultDiv(ForumDb $db): string
    {
        try {
            $resultDiv = '';
            $userId = filter_input(INPUT_POST, self::PARAM_USERID, FILTER_VALIDATE_INT);
            $userActionValue = filter_input(INPUT_POST, self::PARAM_PENDINGAPPROVAL_ACTION, FILTER_UNSAFE_RAW);
            if ($userId && $userActionValue) {
                $user = $db->loadUserById($userId);
                $logger = new Logger($db);
                $mailer = new Mailer();
                $sent = false;
                if ($userActionValue === self::VALUE_ACCEPT && $user) {
                    $db->activateUser($user);
                    $sent = $mailer->sendNotifyUserAcceptedEmail($user->getEmail(), $user->getNick());
                    $logger->logMessageWithUserId(LogType::LOG_NOTIFIED_USER_ACCEPTED, $user);
                    $resultDiv = '<div class="actionSucceeded">Benutzer '
                            . $user->getNick() . ' freigeschaltet (Mail sent: '
                            . ($sent ? 'Ja' : 'Nein') . ')</div>';
                } elseif ($userActionValue === self::VALUE_DENY && $user) {
                    $db->deleteUser($user);
                    $sent = false;
                    //$sent = $mailer->sendNotifyUserDeniedEmail($user->getEmail());
                    if ($sent) {
                        $logger->logMessage(LogType::LOG_NOTIFIED_USER_DENIED, 'Deleted user: ' . $user->getNick() . '(' . $user->getId() . ')');
                    }
                    return '<div class="actionSucceeded">Benutzer '
                            . $user->getNick() . ' abgelehnt (Mail sent: '
                            . ($sent ? 'Ja' : 'Nein') . ')</div>';
                }
            }
            return $resultDiv;
        } catch (InvalidArgumentException $ex) {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }

    public function renderHtmlDiv(ForumDb $db): string
    {
        $haveSome = false;
        $query = 'SELECT user_table.iduser, nick, email, registration_ts, '
                . 'registration_msg, confirmation_ts '
                . 'FROM user_table '
                . 'WHERE ((confirmation_ts IS NOT NULL) '
                . 'AND active = 0 '
                . 'AND (NOT(user_table.iduser IN (SELECT '
                . ' iduser FROM user_deactivated_reason_table))))';
        $stmt = $db->prepare($query);
        $stmt->execute();
        $htmlTable = '<div><table class="actiontable">';
        $htmlTable .= '<tr>'
                . '<th>Nick (UserId)</th>'
                . '<th>Email</th>'
                . '<th>Registriert</th>'
                . '<th>Email bestätigt</th>'
                . '<th>Registrierungsnachricht</th>'
                . '</tr>';
        while ($row = $stmt->fetch()) {
            $haveSome = true;
            $registrationDate = new DateTime($row['registration_ts']);
            $confirmationDate = new DateTime($row['confirmation_ts']);
            $htmlTable .= '<tr>';
            $htmlTable .= '<td>' . htmlspecialchars($row['nick'])
                    . ' (' . $row['iduser'] . ')</td>';
            $htmlTable .= '<td>' . htmlspecialchars($row['email']) . '</td>';
            $htmlTable .= '<td>' . $registrationDate->format('d.m.Y H:i:s') . '</td>';
            $htmlTable .= '<td>' . $confirmationDate->format('d.m.Y H:i:s') . '</td>';
            $htmlTable .= '<td>' . (is_null($row['registration_msg']) ? '' : htmlspecialchars($row['registration_msg'])) . '</td>';
            $htmlTable .= '<td>';
            $htmlTable .= '<form method="post" action="" accept-charset="utf-8">'
                    . '<input type="submit" value="Freischalten"/>'
                    . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $row['iduser'] . '"/>'
                    . '<input type="hidden" name="' . self::PARAM_PENDINGAPPROVAL_ACTION . '" value="' . self::VALUE_ACCEPT . '"/>'
                    . '</form>'
                    . '<form method="post" action="" accept-charset="utf-8">'
                    . '<input type="submit" value="Ablehnen"/>'
                    . '<input type="hidden" name="' . self::PARAM_USERID . '" value="' . $row['iduser'] . '"/>'
                    . '<input type="hidden" name="' . self::PARAM_PENDINGAPPROVAL_ACTION . '" value="' . self::VALUE_DENY . '"/>'
                    . '</form>'
                    . '</td>';
            $htmlTable .= '</tr>';
        }
        $htmlTable .= '</table></div>';
        if ($haveSome) {
            return $htmlTable;
        } else {
            return '<div class="fitalic noTableEntries">Keine Stammposter vorhanden die auf eine Bestätigung warten</div>';
        }
    }

    private $m_clientIpAddress;
}
