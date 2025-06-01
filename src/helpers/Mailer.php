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

require_once __DIR__.'/../YbForumConfig.php';
require_once __DIR__.'/../model/ForumDb.php';
require_once __DIR__.'/../handlers/ConfirmHandler.php';
require_once __DIR__.'/Logger.php';
require_once __DIR__.'/MailerDelegate.php';
require_once __DIR__.'/PhpMailer.php';
require_once __DIR__.'/DebugOutMailer.php';

/** 
 * Helper class to send mails.
 *
 * @author Elias Gerber
 */
class Mailer 
{
    /**
     * Create a new mailer instance. Sets some header values that are the 
     * same for all mails being sent: mailfrom, return-path and content-type.
     */
    public function __construct(?MailerDelegate $delegate = null, ?Logger $logger = null) 
    {
        if(is_null($delegate)) {
            if(YbForumConfig::MAIL_DEBUG === true) {
                $this->m_delegate = new DebugOutMailer();
            }
            else
            {
                $this->m_delegate = new PhpMailer();
            }
        }
        else
        {
            $this->m_delegate = $delegate;
        }
        if(is_null($logger))
        {
            $this->m_logger = new Logger();
        }
        else
        {
            $this->m_logger = $logger;
        }

        $this->m_mailFrom = YbForumConfig::MAIL_FROM_NAME . ' <' . YbForumConfig::MAIL_FROM . '>';
        $this->m_returnPath = YbForumConfig::MAIL_FROM;
        $this->m_allMailBcc = YbForumConfig::MAIL_ALL_BCC;
        $this->m_contentType = 'text/plain; charset=utf-8';
    }
    
    /**
     * Sends an email with a confirmation link to confirm a user migration.
     * @param string $email
     * @param string $confirmationCode
     * @return bool True if sending the mail succeeded
     */
    public function SendMigrateUserConfirmMessage(string $email, string $nick,
            string $confirmationCode) : bool
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum Migration Stammposter',
                'confirm.php',
                array(
                    ConfirmHandler::PARAM_TYPE => ConfirmHandler::VALUE_TYPE_CONFIRM_USER,
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um die Migration '
                . 'deines Stammposterkontos '
                . $nick
                . ' für das 1898-Forum '
                . 'abzuschliessen:');
    }
    
    /**
     * Sends an email with a confirmation link to confirm registration of a 
     * user.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendRegisterUserConfirmMessage(string $email, string $nick,
            string $confirmationCode) : bool
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum Registrierung Stammposter',
                'confirm.php',
                array(
                    ConfirmHandler::PARAM_TYPE => ConfirmHandler::VALUE_TYPE_CONFIRM_USER,
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um die Registrierung '
                . 'deines Stammposterkontos '
                . $nick
                . ' für das 1898-Forum abzuschliessen:');
    }
    
    /**
     * Sends an email with a confirmation link to confirm updating the email
     * address of a user.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendUpdateEmailConfirmMessage(string $email, string $nick,
            string $confirmationCode) : bool
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum aktualisierte Stammposter-Mailadresse bestaetigen',
                'confirm.php',
                array(
                    ConfirmHandler::PARAM_TYPE => ConfirmHandler::VALUE_TYPE_UPDATEEMAIL,
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um die Mailadresse die '
                . 'mit deinem 1898-Forum Stammposterkonto '
                . $nick
                . ' verknüpft ist auf die Mailadresse ' 
                . $email 
                . ' zu aktualisieren:');
    }    
    
    /**
     * Sends an email with a reset password link.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendResetPasswordMessage(string $email, string $nick,
            string $confirmationCode) : bool
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum Stammposter-Passwort zuruecksetzen',
                'resetpassword.php',
                array(
                ConfirmHandler::PARAM_TYPE => ConfirmHandler::VALUE_TYPE_RESETPASS,
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um ein neues Passwort '
                . 'für dein 1898-Forum Stammposterkonto '
                . $nick
                . ' zu setzen:');
    }
    
    /**
     * Sends an email to notify a user that he has been accepted by some admin
     * and can start to post now.
     * @param string $email
     * @return type
     */
    public function SendNotifyUserAcceptedEmail(string $email, string $nick) : bool
    {
        $subject = 'Stammposter freigeschaltet';
        $mailBody = 'Willkommen im YB-Forum. Deine Registrierung wurde '
                . 'von einem Administrator freigeschaltet. '
                . 'Du kannst dein Stammposterkonto ' 
                . $nick 
                . ' ab sofort verwenden um Beiträge zu posten. '
                . 'Bitte beachte '
                . 'die Reihenfolge aus: ' . "\r\n\r\n"
                . 'https://1898.ch/showentry.php?idpost=672696';
        return $this->m_delegate->sendMessage($email, $subject, $mailBody, $this->GetHeaderString());
    }
    
    /**
     * Sends an email to notify a user that he has been denied and that his 
     * account is probably going to be deleted.
     * @param string $email
     * @return type
     */
    public function SendNotifyUserDeniedEmail(string $email) : bool
    {
        $subject = 'Registrierung abgelehnt';
        $mailBody = 'Deine Registrierung wurde abgelehnt.';
        return $this->m_delegate->sendMessage($email, $subject, $mailBody, $this->GetHeaderString());
    }
    
    /**
     * Sends a mail to an admin informing that a user has confirmed his registration
     * @param string $confirmedNick nickname of the user who completed registration
     * @param string $adminEmail destination email
     * @param ?string $registrationMsg A string with the Registration message or null
     * @return boolean True if sending succeeds
     */
    public function NotifyAdminUserConfirmedRegistration(string $confirmedNick, 
            string $adminEmail, ?string $registrationMsg) : bool
    {
        $subject = 'Benutzer wartet auf Freischaltung';
        $mailBody = 'Der Benutzer ' . $confirmedNick . ' hat seine '
                . 'Mailadresse bestätigt und wartet darauf freigeschaltet '
                . 'zu werden.' . "\r\n\r\n";
        $mailBody.= 'Registrierungsnachricht: ' . "\r\n";
        $mailBody.= $registrationMsg;
        return $this->m_delegate->sendMessage($adminEmail, $subject, $mailBody, $this->GetHeaderString());
    }

    /**
     * Sends an email to an admin informing that a generic
     * contact message was sent
     * @param string $contactMsg Message to send
     * @param string $contactEmail Email address provided with the contact message
     * @param string $adminEmail destination email
     */
    public function SendAdminContactMessage(string $contactEmail,
            string $contactMsg, string $adminEmail) : bool
    {
        $subject = 'Kontaktnachricht erhalten';
        $mailBody = 'Von der Email ' . $contactEmail . ' wurde '
                . 'eine Kontaktanfrage gesendet: '
                . "\r\n\r\n";
        $mailBody.= $contactMsg . "\r\n";
        $sent = $this->m_delegate->sendMessage($adminEmail, $subject, $mailBody, $this->GetHeaderString());
        if($sent)
        {
            $this->m_logger->LogMessage(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $adminEmail);
        }
        else
        {
            $this->m_logger->LogMessage(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $adminEmail);
        }
        return $sent;
    }
    
    /**
     * Sends a mail with a confirmation link using the passed values.
     * @param string $email Destination address.
     * @param string $subject Subject of the message.
     * @param string $page Page to navigate to as confirmation link.
     * @param array $args Arguments that are appended to the link as URL
     * parameters.
     * @param string $messageText Message to include in the mail before the 
     * link.
     * @return boolean True if sending mail succeeds
     */
    private function SendConfirmMail(string $email, string $subject,
            string $page, array $args, 
            string $messageText) : bool
    {
        assert(!empty($email));
        assert(!empty($messageText));

        $link = YbForumConfig::BASE_URL . $page . '?'
                . http_build_query($args);
        
        $validFor = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);        
        $validForText = 'Der Link ist ' . $validFor->format('%h Stunden')
                . ' lang gültig.';

        $mailBody = $messageText . "\r\n\r\n";
        $mailBody.= $link . "\r\n\r\n";
        $mailBody.= $validForText . "\r\n";
        
        $sent = $this->m_delegate->sendMessage($email, $subject, $mailBody, $this->GetHeaderString());
        if($sent)
        {
            $this->m_logger->LogMessage(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $email);
        }
        else
        {
            $this->m_logger->LogMessage(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $email);
        }
        return $sent;
    }
        
    /**
     * Format some string holding some reasonable email header values.
     * @return string
     */
    private function GetHeaderString() : string
    {
        $headers = array(
            'From' => $this->m_mailFrom,
            'Return-Path' => $this->m_returnPath,
            'MIME-Version' => '1.0',
            'Content-Type' => $this->m_contentType,
            'Date' => date('r')             
        );
        if($this->m_allMailBcc)
        {
            $headers['Bcc'] = $this->m_allMailBcc;
        }
        
        $str = '';
        foreach($headers as $key => $item) 
        {
            $str .= $key . ': ' . $item . PHP_EOL;
        }
        rtrim($str, PHP_EOL);
        return $str;
    }

    public function getMailFrom() : string
    {
        return $this->m_mailFrom;
    }

    public function getReturnPath() : string
    {
        return $this->m_returnPath;
    }

    public function getContentType() : string
    {
        return $this->m_contentType;
    }
    
    public function getAllMailBcc() : string
    {
        return $this->m_allMailBcc;
    }

    public function GetLogger() : Logger
    {
        return $this->m_logger;
    }

    public function GetMailerDelegate() : MailerDelegate
    {
        return $this->m_delegate;
    }

    private string $m_mailFrom;
    private string $m_returnPath;
    private string $m_contentType;
    private string $m_allMailBcc;

    private MailerDelegate $m_delegate;
    private Logger $m_logger;
}
