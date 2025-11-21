<?php
namespace App\Console;


use Bs\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;

class Test extends Console
{

    protected function configure(): void
    {
        $this->setName('test')
            ->setAliases(['t'])
            ->setDescription('This is a test script');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!Config::isDev()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }



}
