<?php

namespace App\Email;

use App\Db\Company;
use App\Db\Payment;
use App\Factory;
use Bs\Registry;

class Invoice
{

    public static function sendIssueInvoice(\App\Db\Invoice $invoice): bool
    {
        $company = $invoice->getCompany();
        if (!($company instanceof Company)) return false;

        $siteCompany = Factory::instance()->getOwnerCompany();

        // Email client the new invoice
        $message = Factory::instance()->createMailMessage();
        $message->addTo($company->email);
        if ($company->accountsEmail) {
            $message->addCc($company->accountsEmail);
        }
        $message->setFrom($siteCompany->email);
        $message->setSubject($siteCompany->name . ' - Invoice ' . $invoice->invoiceId);
        $message->set('company.name', $company->name);
        $message->set('payment.text', Registry::instance()->get('site.invoice.payment', ''));

        // get unpaid email template
        $content = Registry::instance()->get('site.email.invoice.unpaid', '');
        $message->setContent($content);

        $ren = new \App\Pdf\PdfInvoice($invoice);
        $attach = $ren->getPdfAttachment();
        $filename = 'invoice-' . $invoice->invoiceId.'.pdf';
        if ($invoice->issuedOn instanceof \DateTime) {
            $filename = $invoice->issuedOn->format('Y-m-d') . '_' . $filename;
        }
        $message->addStringAttachment($attach, $filename);

        $items = $invoice->getItemList();
        $tasks = [];
        foreach ($items as $item) {
            $task = $item->getModel();
            if (!$task instanceof \App\Db\Task) continue;
            $tasks[] = $task;
        }
        if (count($tasks)) {
            $pdf = new \App\Pdf\PdfTaskList($tasks);
            $filename = str_replace([' ', '/', '\\'], '', $invoice->invoiceId . '-TaskList.pdf');
            $message->addStringAttachment($pdf->getPdfAttachment($filename), $filename);
        }

        return Factory::instance()->getMailGateway()->send($message);
    }

    public static function sendPaymentReceipt(\App\Db\Payment $payment): bool
    {
        $siteCompany = Factory::instance()->getOwnerCompany();
        $invoice = $payment->getInvoice();
        if (!($invoice instanceof \App\Db\Invoice)) return false;
        $company = $payment->getInvoice()->getCompany();
        if (!($company instanceof Company)) return false;

        $message = Factory::instance()->createMailMessage();
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
            'payment.received' => $payment->receivedAt->format(\Tk\Date::FORMAT_SHORT_DATETIME),
            'payment.method' => Payment::METHOD_LIST[$payment->method],
        );
        $message->replace($data);

        $content = Registry::instance()->get('site.email.payment.cleared');
        $message->setContent($content);

        $ren = new \App\Pdf\PdfInvoice($invoice);
        $attach = $ren->getPdfAttachment();
        $filename = 'invoice-' . $invoice->invoiceId.'.pdf';
        if ($invoice->issuedOn instanceof \DateTime) {
            $filename = $invoice->issuedOn->format('Y-m-d') . '_' . $filename;
        }
        $message->addStringAttachment($attach, $filename);

        return Factory::instance()->getMailGateway()->send($message);
    }
}