<?php
namespace App\Console;

use Bs\Db\GuestToken;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;
use Tk\Uri;
use Tk\Url;

class Test extends Console
{

    protected function configure()
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


//        $gt = GuestToken::create([
//            Uri::create('/login')->getPath()
//        ],
//        [
//            'hash' => md5('test'),
//            'fooId' => 22,
//            'text' => 'Just a blank message'
//        ], 15);

        return self::SUCCESS;
    }



}
