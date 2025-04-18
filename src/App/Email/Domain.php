<?php

namespace App\Email;

use App\Factory;
use Tk\FileUtil;
use Tk\Mail\Mailer;

class Domain
{

    public static function sendServerOfflineNotice(\App\Db\Domain $domain): bool
    {
        $siteCompany = Factory::instance()->getOwnerCompany();

        // Email client the new invoice
        $message = Factory::instance()->createMailMessage();
        $message->addTo($siteCompany->email);
        $message->setFrom($siteCompany->email);
        $message->setSubject('Monitored Site Offline: ' . $domain->companyName);
        $message->set('domain.companyName', $domain->companyName);
        $message->set('domain.url', $domain->url);

        // get unpaid email template
        $content = <<<HTML
            <h2>Monitored Site Offline</h2>
            <ul>
                <li>Client: {domain.companyName}</li>
                <li>URL: {domain.url}</li>
            </ul>
        HTML;
        $message->setContent($content);
        return Mailer::instance()->send($message);
    }

}