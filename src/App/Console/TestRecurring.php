<?php
namespace App\Console;

use App\Db\Invoice;
use Bs\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;
use Tk\Date;

/**
 * This script can be run with a date to test what recurring products would be invoiced.
 *
 */
class TestRecurring extends Console
{

    protected function configure(): void
    {
        $this->setName('testRecurring')
            ->setAliases(['rec'])
            ->addArgument('date', InputArgument::REQUIRED, 'A valid date in the format \'yyyy-mm-dd\'.')
            ->addOption('dryrun', 'd', InputOption::VALUE_NEGATABLE, 'dryrun, do not send or issue any invoices', true)
            ->setDescription('Test recurring invoice items with a selected date.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // invoice recurring items
        $this->invoiceRecurring();

        return self::SUCCESS;
    }

    /**
     * find all due recurring items and add them to any open invoices
     */
    private function invoiceRecurring(): void
    {
        $dryrun = $this->input->getOption('dryrun');
        $datestr = $this->input->getArgument('date');
        $date = Date::create($datestr);

        if (!($dryrun || Config::isDev())) throw new \Exception('This command can only issue invoices in dev mode.');

        $this->writeComment(' - Testing Recurring Items');

        $items = \App\Db\Recurring::findFiltered([
            'isDue' => $date
        ]);

        $invoiceList = [];
        // only takes 1 recurring item to be set to `issue`, to enable automatic invoice issue
        $doIssue = [];

        foreach ($items as $recurring) {
            if ($dryrun) {
                $this->writeComment('   - [' . $recurring->getId() . '] Added Invoice Item: ' . $recurring->description . ' - ' . $recurring->getCompany()->name);
                if ($recurring->issue) {
                    $doIssue[$recurring->companyId] = $recurring->getCompany()->name;
                }
            } else {
                $invoice = $recurring->invoice();
                if ($invoice instanceof Invoice) {
                    $this->writeComment('   - [' .$recurring->getId(). '] Added Invoice Item: ' . $recurring->description . ' - ' . $recurring->getCompany()->name);
                    $invoiceList[$invoice->invoiceId] = $invoice;
                    if ($recurring->issue) {
                        $doIssue[$invoice->invoiceId] = $invoice;
                    }
                }
            }
        }

        // Issue invoices - done here to ensure only
        // one invoice is issued for multiple items.
        foreach ($doIssue as $invoice) {
            if ($dryrun) {
                $this->writeComment('   - Invoice issued for company: ' . $invoice);
            } else {
                if ($invoice instanceof Invoice) {
                    $this->writeComment('   - Invoice issued for company: ' . $invoice->getCompany()->name);
                    $invoice->doIssue();
                }
            }
        }

    }

}
