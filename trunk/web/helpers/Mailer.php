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

/**
 * Helper class to send mails.
 *
 * @author Elias Gerber
 */
class Mailer 
{    
    /**
     * Try to determine if this is some sort of a preview-request
     * @return boolean True if the HTTP_USER_AGENT contains the word 'BingPreview'
     */
    public static function IsPreviewRequest()
    {
        // BingPreview
        $userAgent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_UNSAFE_RAW);
        if($userAgent && strpos($userAgent, 'BingPreview') !== false)
        {
            return true;
        }
        return false;
    }    
    
    /**
     * Create a new mailer instance. Sets some header values that are the 
     * same for all mails being sent: mailfrom, return-path and content-type.
     */
    public function __construct() 
    {
        $this->m_mailFrom = 'YB-Forum <' . YbForumConfig::MAIL_FROM . '>';
        $this->m_returnPath = YbForumConfig::MAIL_FROM;
        $this->m_contentType = 'text/plain; charset=utf-8';
    }
    
    /**
     * Sends an email with a confirmation link to confirm a user migration.
     * @param string $email
     * @param string $confirmationCode
     * @return bool True if sending the mail succeeded
     */
    public function SendMigrateUserConfirmMessage(string $email, 
            string $confirmationCode)
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
                'Bitte besuche den folgenden Link um die Migration deines Stammposterkontos für das 1898-Forum abzuschliessen:');
    }
    
    /**
     * Sends an email with a confirmation link to confirm registration of a 
     * user.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendRegisterUserConfirmMessage(string $email, 
            string $confirmationCode)
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
                'Bitte besuche den folgenden Link um die Registrierung deines Stammposterkontos für das 1898-Forum abzuschliessen:');
    }
    
    /**
     * Sends an email with a confirmation link to confirm updating the email
     * address of a user.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendUpdateEmailConfirmMessage(string $email, 
            string $confirmationCode)
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum aktualisierte Stammposter-Mailadresse bestätigen',
                'confirm.php',
                array(
                    ConfirmHandler::PARAM_TYPE => ConfirmHandler::VALUE_TYPE_UPDATEEMAIL,
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um die Mailadresse die mit deinem 1898-Forum Stammposterkonto verknüpft ist auf die Mailadresse ' 
                        . $email . ' zu aktualisieren:');
    }    
    
    /**
     * Sends an email with a reset password link.
     * @param string $email
     * @param string $confirmationCode
     * @return type
     */
    public function SendResetPasswordMessage(string $email, 
            string $confirmationCode)
    {
        assert(!empty($email));
        assert(!empty($confirmationCode));
        
        return $this->SendConfirmMail($email, 
                '1898-Forum Stammposter-Passwort zurücksetzen',
                'resetpassword.php',
                array(
                    ConfirmHandler::PARAM_CODE => $confirmationCode
                ),
                'Bitte besuche den folgenden Link um ein neues Passwort für dein 1898-Forum Stammposterkonto zu setzen:');
    }
    
    /**
     * Sends an email to notify a user that he has been accepted by some admin
     * and can start to post now.
     * @param string $email
     * @return type
     */
    public function SendNotifyUserAcceptedEmail(string $email)
    {
        $subject = 'Stammposter freigeschaltet';
        $mailBody = 'Willkommen im YB-Forum. Deine Registrierung wurde '
                . 'von einem Administrator freigeschaltet.';
        $sent = mail($email, $subject, $mailBody, $this->GetHeaderString());
        return $sent;
    }
    
    /**
     * Sends an email to notify a user that he has been denied and that his 
     * account is probably going to be deleted.
     * @param string $email
     * @return type
     */
    public function SendNotifyUserDeniedEmail(string $email)
    {
        $subject = 'Registrierung abgelehnt';
        $mailBody = 'Deine Registrierung wurde abgelehnt.';
        $sent = mail($email, $subject, $mailBody, $this->GetHeaderString());
        return $sent;
    }
    
    public function NotifyAdminUserConfirmedRegistraion(string $confirmedNick, 
            string $adminEmail)
    {
        $subject = 'Benutzer wartet auf Freischaltung';
        $mailBody = 'Der Benutzer ' . $confirmedNick . ' hat seine '
                . 'Mailadresse bestätigt und wartet darauf freigeschaltet '
                . 'zu werden.';
        $sent = mail($adminEmail, $subject, $mailBody, $this->GetHeaderString());
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
            string $messageText)
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
        
        // If we do not force a sender, the reply-to: address is still set to www-data (?)
//        $sent = mail($email, $subject, $mailBody, $this->GetHeaderString());        
        $sent = mail($email, $subject, $mailBody, $this->GetHeaderString(), '-f ' . YbForumConfig::MAIL_FROM);
        $logger = new Logger();
        if($sent)
        {
            $logger->LogMessage(Logger::LOG_MAIL_SENT, 'Mail sent to: ' . $email);
        }
        else
        {
            $logger->LogMessage(Logger::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $email);
        }
        return $sent;
    }
        
    /**
     * Format some string holding some reasonable email header values.
     * @return string
     */
    private function GetHeaderString()
    {
        $headers = array(
            'From' => $this->m_mailFrom,
            'Return-Path' => $this->m_returnPath,
            'MIME-Version' => '1.0',
            'Content-Type' => $this->m_contentType,
            'Date' => date('r')             
        );
        
        $str = '';
        foreach($headers as $key => $item) 
        {
            $str .= $key . ': ' . $item . PHP_EOL;
        }
        rtrim($str, PHP_EOL);
        return $str;
    }
    
    private $m_mailFrom;
    private $m_returnPath;
    private $m_contentType;
}
