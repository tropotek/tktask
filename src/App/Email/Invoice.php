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
        $message = Factory::instance()->createMessage();
        $message->addTo($company->email);
        $message->setFrom($siteCompany->email);
        $message->setSubject($siteCompany->name . ' - Invoice ' . $invoice->invoiceId);
        $message->set('company.name', $company->name);

        // get unpaid email template
        $content = Registry::instance()->get('site.email.invoice.unpaid');
        $message->setContent($content);

        // TODO: implement invoice PDF
//        $ren = \App\Ui\PdfInvoice::create($invoice);
//        $attach = $ren->getPdfAttachment();
//        $filename = 'invoice-' . $invoice->invoiceId.'.pdf';
//        if ($invoice->issuedOn instanceof \DateTime) {
//            $filename = $invoice->issuedOn->format('Y-m-d') . '_' . $filename;
//        }
//        $message->addStringAttachment($attach, $filename);

        // TODO Add a PDF of all tasks completed not just project ones.

//        $items = $invoice->getItemList();
//        $projects = [];
//        foreach ($items as $item) {
//            $task = $item->findModelByCode();
//            if (!$task instanceof \App\Db\Task) continue;
//            if ($task->getProject()) {
//                $projects[$task->getProject()->getId()] = $task->getProject();
//            }
//        }
//        // TODO: just show project name on task list
//        foreach ($projects as $project) {
//            $pdf = new \App\Ui\PdfTaskList($items, $project);
//            $filename = str_replace([' ', '/', '\\'], '', $project->getName() . '-TaskList.pdf');
//            $message->addStringAttachment($pdf->getPdfAttachment($filename), $filename);
//        }

        return Factory::instance()->getMailGateway()->send($message);
    }

    public static function sendPaymentReceipt(\App\Db\Payment $payment): bool
    {
        $siteCompany = Factory::instance()->getOwnerCompany();
        $invoice = $payment->getInvoice();
        if (!($invoice instanceof \App\Db\Invoice)) return false;
        $company = $payment->getInvoice()->getCompany();
        if (!($company instanceof Company)) return false;


        $message = Factory::instance()->createMessage();
        $message->addTo($company->email);
        $message->setFrom($siteCompany->email);
        $message->setSubject('Payment Receipt: ' . $siteCompany->name . ' - Invoice ' . $payment->invoiceId);
        $data = array(
            'company.name' => $company->name,
            'payment.id' => $payment->paymentId,
            // TODO: get account id from method/view
            'account.id' => 'CM-0000000' . $invoice->fid,
            'payment.amount' => $payment->amount->toString(),
            'payment.received' => $payment->receivedAt->format(\Tk\Date::FORMAT_SHORT_DATETIME),
            'payment.method' => Payment::METHOD_LIST[$payment->method],
        );
        $message->replace($data);

        $content = Registry::instance()->get('site.email.payment.cleared');
        $message->setContent($content);

        // TODO implement receipt invoice
//        $ren = \App\Ui\PdfInvoice::create($payment->getInvoice());
//        $attach = $ren->getPdfAttachment();
//        $filename = 'invoice-' . $payment->getId().'.pdf';
//        if ($payment->getInvoice()->issuedOn) {
//            $filename = $payment->getInvoice()->issuedOn->format('Y-m-d') . '_' . $filename;
//        }
//        $message->addStringAttachment($attach, $filename);

        return Factory::instance()->getMailGateway()->send($message);
    }
}