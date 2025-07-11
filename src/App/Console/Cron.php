<?php
namespace App\Console;

use App\Db\Domain;
use App\Db\Invoice;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Log;

/**
 * Cron job to invoice recurring products and send ay invoices
 *
 * # run site cron job
 *   * 7  *  *   *   *      php /home/user/public_html/bin/cmd cron > /dev/null 2>&1
 *
 */
class Cron extends Console
{
    use LockableTrait;

    protected function configure(): void
    {
        $path = getcwd();
        $this->setName('cron')
            ->setDescription('The site cron script. crontab line: * 7 * * *   ' . $path . '/bin/cmd cron > /dev/null 2>&1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.', OutputInterface::VERBOSITY_VERBOSE);
            return self::SUCCESS;
        }

        Log::info('tktask: Cron script running');

        // invoice recurring items
        $this->invoiceRecurring();

        // send reminder invoices every 7 days after overdue.
        //$this->sendInvoiceReminders();

        // Closing Expired Recurring Items
        $this->closeExpiredRecurring();

        // Ping all monitored domains
        Domain::pingAllDomains();

        $this->release();   // release lock

        return self::SUCCESS;
    }

    /**
     * find all due recurring items and add them to any open invoices,
     * issue any invoices where recurring issue is true
     */
    private function invoiceRecurring(): void
    {
        $this->writeComment(' - Invoicing Recurring Items', OutputInterface::VERBOSITY_VERBOSE);

        $items = \App\Db\Recurring::findFiltered([
            'isDue' => true
        ]);

        $invoiceList = [];
        // only takes 1 recurring item to be set to `issue`, to enable automatic invoice issue
        $doIssue = [];

        foreach ($items as $recurring) {
            $invoice = $recurring->invoice();

            if ($invoice instanceof Invoice) {
                $this->writeComment('   - [' .$recurring->getId(). '] Added Invoice Item: ' . $recurring->description . ' - ' . $recurring->getCompany()->name);
                $invoiceList[$invoice->invoiceId] = $invoice;
                if ($recurring->issue) {
                    $doIssue[$invoice->invoiceId] = $invoice;
                }
            }
        }

        // Issue invoices - done here to ensure only
        // one invoice is issued for multiple items.
        foreach ($doIssue as $invoice) {
            $invoice->doIssue();
        }

    }

//    private function sendInvoiceReminders(): void
//    {
//        $this->writeComment(' - Send invoice overdue reminder emails', OutputInterface::VERBOSITY_VERBOSE);
//    }

    private function closeExpiredRecurring(): void
    {
        $this->writeComment(' - Closing Expired Recurring Items', OutputInterface::VERBOSITY_VERBOSE);
        \App\Db\Recurring::closeExpired();
    }

}
