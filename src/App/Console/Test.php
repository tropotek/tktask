<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;

class Test extends Console
{

    protected function configure(): void
    {
        $this->setName('test')
            ->setDescription('This is a test script');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!Config::isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

//        $orderBy = 'FIELD(`status`, "open", "pending", "hold", "closed", "cancelled"), -created';
//        //preg_match('/,\(?!\d+\))/gm', $orderBy, $regs);
//
//        $regs = preg_split('/(,)|([([{])|[)\]}]/', $orderBy);
//        vd($orderBy, $regs);

        return self::SUCCESS;
    }



}
