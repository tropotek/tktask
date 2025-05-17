<?php

namespace App\Email;

use App\Db\Company;
use App\Db\Payment;
use App\Factory;
use App\Pdf\PdfInterface;
use Bs\Registry;
use Tk\Db\Session;
use Tk\Log;
use Tk\Mail\Mailer;
use Tk\Uri;

class Invoice
{

    public static function sendIssueInvoice(\App\Db\Invoice $invoice): bool
    {
        $company = $invoice->getCompany();
        if (!($company instanceof Company)) return false;

        $siteCompany = Factory::instance()->getOwnerCompany();

        // get unpaid email template
        $content = Registry::getValue('site.email.invoice.unpaid');
        if (empty($content)) {
            Log::error("invalid email template for site.email.invoice.unpaid");
            return false;
        }

        // Email client the new invoice
        $message = Factory::instance()->createMailMessage($content);
        $message->addTo($company->email);
        if ($company->accountsEmail) {
            $message->addCc($company->accountsEmail);
        }
        $message->setFrom($siteCompany->email);
        $message->setSubject($siteCompany->name . ' - Invoice ' . $invoice->invoiceId);
        $message->set('company.name', $company->name);

        $paymentText = Registry::getValue('site.invoice.payment');
        if (empty($paymentText)) {
            Log::warning("empty invoice payment text description: site.invoice.payment");
        }
        $message->set('payment.text', $paymentText);

        $url = Uri::create('/pdf/invoice', [
            'invoiceId' => $invoice->invoiceId,
            'o' => PdfInterface::OUTPUT_ATTACH,
        ]);
        $attach = (string)file_get_contents($url);

        $filename = 'invoice-' . $invoice->invoiceId.'.pdf';
        $message->addStringAttachment($attach, $filename);

        $outstanding = $invoice->getOutstanding();
        foreach ($outstanding as $inv) {
            $url = Uri::create('/pdf/invoice', [
                'invoiceId' => $inv->invoiceId,
                'o' => PdfInterface::OUTPUT_ATTACH,
            ]);
            $a = (string)file_get_contents($url);

            $filename = 'outstanding-invoice-' . $inv->invoiceId.'.pdf';
            $message->addStringAttachment($a, $filename);
        }

        $items = $invoice->getItemList();
        $tasks = [];
        foreach ($items as $item) {
            $task = $item->getModel();
            if (!$task instanceof \App\Db\Task) continue;
            $tasks[] = $task;
        }
        if (count($tasks)) {
            // todo: not sure if the session is the best way to send tasks to the pdf, will do for now!
            Session::instance()->set('pdf.tasks', $tasks);
            $url = Uri::create('/pdf/taskList', [
                'o' => PdfInterface::OUTPUT_ATTACH,
                'ses' => 1,
            ]);
            // Allow session in request
            $opts = null;
            if ($_SERVER['HTTP_COOKIE'] ?? '') {
                $opts = [
                    'http' => array('header'=> 'Cookie: '.$_SERVER['HTTP_COOKIE']."\r\n"),
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ];
            }
            $context = stream_context_create($opts);
            session_write_close(); // unlock the file
            $attach = (string)file_get_contents($url, false, $context);
            session_start(); // Lock the file

            $filename = str_replace([' ', '/', '\\'], '', $invoice->invoiceId . '-TaskList.pdf');
            $message->addStringAttachment($attach, $filename);
        }

        return Mailer::instance()->send($message);
    }

    public static function sendPaymentReceipt(\App\Db\Payment $payment): bool
    {
        $siteCompany = Factory::instance()->getOwnerCompany();
        $invoice = $payment->getInvoice();
        if (!($invoice instanceof \App\Db\Invoice)) return false;
        $company = $payment->getInvoice()->getCompany();
        if (!($company instanceof Company)) return false;

        $content = Registry::getValue('site.email.payment.cleared', '');
        if (empty($content)) {
            Log::error("invalid email template for site.email.payment.cleared");
            return false;
        }

        $message = Factory::instance()->createMailMessage($content);
        $message->addTo($company->email);
        if ($company->accountsEmail) {
            $message->addCc($company->accountsEmail);
        }
        $message->setFrom($siteCompany->email);
        $message->setSubject('Payment Receipt: ' . $siteCompany->name . ' - Invoice ' . $payment->invoiceId);
        $data = array(
            'site.name' => $siteCompany->name,
            'company.name' => $company->name,
            'company.contact' => $company->contact,
            'payment.id' => $payment->paymentId,
            'account.id' => $company->accountId,
            'payment.amount' => $payment->amount->toString(),
            'payment.received' => $payment->receivedAt->format(\Tk\Date::FORMAT_AU_DATETIME),
            'payment.method' => Payment::METHOD_LIST[$payment->method],
        );
        $message->replace($data);

        $url = Uri::create('/pdf/invoice', [
            'invoiceId' => $invoice->invoiceId,
            'o' => PdfInterface::OUTPUT_ATTACH,
        ]);
        $attach = (string)file_get_contents($url);

        $filename = 'invoice-' . $invoice->invoiceId.'.pdf';
        if ($invoice->issuedOn instanceof \DateTime) {
            $filename = $invoice->issuedOn->format('Y-m-d') . '_' . $filename;
        }
        $message->addStringAttachment($attach, $filename);

        return Mailer::instance()->send($message);
    }
}