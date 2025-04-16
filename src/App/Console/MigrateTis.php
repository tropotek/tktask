<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;

class MigrateTis extends Console
{
    protected string $oldDsn       = '';
    protected string $srcDataPath  = '';
    protected string $pathCasePath = '/pathCase';
    protected string $mediaPath    = '/media';

    protected function configure(): void
    {
        $this->setName('migrateTis')
            ->setAliases(['tis'])
            ->setDescription('Migrate the TkTis db to this TkTask');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        if (!Config::isDebug()) {
//            $this->writeError('Error: Only run this command in a debug environment.');
//            return self::FAILURE;
//        }



        return self::SUCCESS;
    }



}
