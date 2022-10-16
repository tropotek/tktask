<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Console\Console;


/**
 * Cron job to be run nightly
 *
 * # run Nightly site cron job
 *   * /5  *  *   *   *      php /home/user/public_html/bin/cmd cron > /dev/null 2>&1
 *
 *
 * @author tropotek <info@tropotek.com>
 */
class Cron extends Console
{
    use LockableTrait;


    /**
     *
     */
    protected function configure()
    {
        $path = getcwd();
        $this->setName('cron')
            ->setDescription('The site cron script. crontab line: */1 *  * * *   ' . $path . '/bin/cmd cron > /dev/null 2>&1');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return Command::SUCCESS;
        }


//        for($i = 0; $i < 599999999; $i++) {
//            echo '';
//        }
        $this->writeComment('Completed!!!');


        $this->release();
        return Command::SUCCESS;
    }

}
