<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Statistics
 *
 * @author eli
 */
class Statistics
{
    public function renderHtmlDiv(ForumDb $db): string
    {
        $htmlStr = '<div>Total Threads: ' . $db->getThreadCount() . '</div>';
        $htmlStr .= '<div>Total Posts: ' . $db->getPostCount() . '</div>';
        $htmlStr .= '<div>';
        $htmlStr .= '<p style="margin: 0;">Total Stammposter: ' . $db->getUserCount() . '</p>';
        $htmlStr .= '<p style="text-indent: 1em; margin: 0;">Aktive: ' . $db->getActiveUserCount() . '</p>';
        $htmlStr .= '<p style="text-indent: 1em; margin: 0;">Migration benötigt: ' . $db->getNeedMigrationUserCount() . '</p>';
        $htmlStr .= '<p style="text-indent: 1em; margin: 0;">Dummies: ' . $db->getDummyUserCount() . '</p>';
        $htmlStr .= '<p style="text-indent: 1em; margin: 0;">Von Admin deaktivierte: ' . $db->getFromAdminDeactivatedUserCount() . '</p>';
        $htmlStr .= '<p style="text-indent: 1em; margin: 0;">Wartend auf Freischaltung durch Admin: ' . $db->getPendingAdminApprovalUserCount() . '</p>';
        $htmlStr .= '</div>';
        return $htmlStr;
    }
}
