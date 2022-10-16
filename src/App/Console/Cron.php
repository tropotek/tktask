<?php
namespace App\Console;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Console\Console;
use Tk\Traits\SystemTrait;


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
    use SystemTrait;

    /**
     *
     */
    protected function configure()
    {
        $path = getcwd();
        $this->setName('cron')
            ->setDescription('The site cron script. crontab line: */1 *  * * *   ' . $path . '/bin/cmd cron > /dev/null 2>&1');
        $this->lock('cron', true);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getConfig()->get('site.maintenance.enabled')) {
            return Console::FAILURE;
        }
        for($i = 0; $i < 599999999; $i++) {
            echo '';
        }
        $this->writeComment('Completed!!!');
        return Console::SUCCESS;
    }

}
