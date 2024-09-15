<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;

class SiteTest extends Console
{

    protected function configure()
    {
        $this->setName('test')
            ->setAliases(['ts'])
            ->setDescription('Run this script to execute site test scripts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

        // TODO:
        //   - create a test site against a standard list of bot requests, see if anything weird comes up
        //     mose should be 404, list incomming...
        //





        $output->writeln('Complete!!!');
        return self::SUCCESS;
    }


}
