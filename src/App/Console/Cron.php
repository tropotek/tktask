<?php
namespace App\Console;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;

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

        // invoice recurring items
        $this->invoiceRecurring();

        // send reminder invoices every 7 days after overdue.
        //$this->sendInvoiceReminders();

        // Closing Expired Recurring Items
        $this->closeExpired();

        $this->release();   // release lock

        return self::SUCCESS;
    }

    /**
     * find all due recurring items and add them to any open invoices,
     * issue any invoices where recurring.issue is true
     */
    private function invoiceRecurring(): void
    {
        $this->writeComment(' - Invoicing Recurring Items', OutputInterface::VERBOSITY_VERBOSE);

        $now = true;
//        if ($this->getConfig()->isDebug()) {
//            $now = \Tk\Date::floor(\Tk\Date::create('2025-05-01'));
//        }

        $items = \App\Db\Recurring::findFiltered([
            'isDue' => $now
        ]);

        $invoiceList = [];
        // list of invoices to not issue
        // only takes 1 recurring item to be set to `not issue` to stop invoice from being issued
        $noIssue = [];

        foreach ($items as $recurring) {
            $invoice = $recurring->invoice($now);
            if ($invoice) {
                $this->writeComment('   - [' .$recurring->getId(). '] ' . $recurring->description . ' - ' . $recurring->getCompany()->name, OutputInterface::VERBOSITY_VERBOSE);
                $invoiceList[$invoice->invoiceId] = $invoice;
                if (!$recurring->issue) {
                    $noIssue[] = $invoice->invoiceId;
                }
            }
        }

        // Issue invoices - done here to ensure only
        // one invoice is issued for multiple items.
        foreach ($invoiceList as $invoice) {
            if (in_array($invoice->invoiceId, $noIssue)) continue;
            $invoice->doIssue();
        }

    }

    private function sendInvoiceReminders(): void
    {
        $this->writeComment(' - Send invoice overdue reminder emails', OutputInterface::VERBOSITY_VERBOSE);

        // todo ...

    }

    private function closeExpired(): void
    {
        $this->writeComment(' - Closing Expired Recurring Items', OutputInterface::VERBOSITY_VERBOSE);
        \App\Db\Recurring::closeExpired();
    }

}
