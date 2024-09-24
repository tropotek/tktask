<?php
namespace App\Console;

use Bs\Db\GuestToken;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;
use Tk\Uri;

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

        Uri::$SITE_HOSTNAME = 'godar.ttek.org';
        Uri::$BASE_URL = '/Projects/tk8base';

        $gt = GuestToken::create([
            Uri::create('/login')->getPath()
        ],
        [
            'hash' => md5('test'),
            'fooId' => 22,
            'text' => 'Just a blank message'
        ], 15);

        return self::SUCCESS;
    }

    public function testUrl()
    {

        // TODO
        Uri::$BASE_URL = '/Projects/tk8base';
        $list = [
            Uri::create(),
            Uri::create('/'),
            Uri::create('mailto:test@example.com'),
            Uri::create('/Projects/tk8base/registerActivate?t=w22Vb7LUapRsg8uGn6mgZpSdUseSnJWamGRpamRnl2nHbJmbaMdplmTDlJiVmclnmcVghGyrapRxUq1Zb5+eYpllmW5ulGeVZ5zg'),
            Uri::create('/registerActivate'),
            Uri::create('/registerActivate.html'),
            Uri::create('/registerActivate.html#test'),
            Uri::create('https://godar.ttek.org/Projects/tk8base/user/memberEdit?userId=21'),
            Uri::create('https://godar.ttek.org/'),
            Uri::create('http://godar.ttek.org:8080/'),
            Uri::create('https://user:pass@godar.ttek.org/?test=another+test'),
            Uri::create('/register', ['queryparam' => 'test']),
        ];
        foreach ($list as $url) {
            $this->output->writeln($url->__toString());
        }

    }


}
