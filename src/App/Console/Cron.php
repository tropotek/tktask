<?php
namespace App\Console;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;

/**
 * Cron job to be run nightly
 *
 * # run Nightly site cron job
 *   * /5  *  *   *   *      php /home/user/public_html/bin/cmd cron > /dev/null 2>&1
 *
 */
class Cron extends Console
{
    use LockableTrait;

    protected function configure(): void
    {
        $path = getcwd();
        $this->setName('cron')
            ->setDescription('The site cron script. crontab line: */1 *  * * *   ' . $path . '/bin/cmd cron > /dev/null 2>&1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return self::SUCCESS;
        }



        $this->writeComment('Completed!!!');
        $this->release();   // release lock
        return self::SUCCESS;
    }

}
