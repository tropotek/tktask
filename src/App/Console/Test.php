<?php
namespace App\Console;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;
use Tk\Uri;

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

        //$url = Uri::create('http://php.net');
        //$url = Uri::create('https://tropotek.com.au');
        $url = Uri::create('https://godar.ttek.org/Projects/tktask/tkping');
        $opts = [
            'http' => [
                //'header' => 'Cookie: '.$_SERVER['HTTP_COOKIE']."\r\n",
                'method' => 'HEAD'
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ];
        $context = stream_context_create($opts);
        $data = file_get_contents($url, false, $context);
        if ($data === false) {
            vd('404 Not Found');
        } else {
            vd($data, json_encode($data));
        }

//        $fd = fopen($url, 'rb', false, $context);
//        if ($fd === false) {
//            vd('404 Not Found');
//        } else {
//            vd(stream_get_meta_data($fd));
//            fclose($fd);
//        }






        return self::SUCCESS;
    }



}
