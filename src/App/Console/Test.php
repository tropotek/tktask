<?php
namespace App\Console;


use App\Db\Domain;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;
use Tk\FileUtil;
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
        if (!Config::isDev()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

//        $this->write("Pining All Domains");
//        Domain::pingAllDomains();
//        $this->write("Finished");


//        $url = Uri::create('https://godar.ttek.org/Projects/tktask/tkping');
//        $opts = [
//            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
//        ];
//        $context = stream_context_create($opts);
//        $data = file_get_contents($url->toString(), false, $context);
//        if ($data === false) {
//            vd('404 Not Found');
//            return self::FAILURE;
//        } else {
//
//            if (basename($url->getPath()) == 'tkping') {
//                // has tk site data
//                $data = json_decode($data, true);
//                vd(FileUtil::bytes2String($data['bytes'] ?? 0));
//            } else {
//                // standard host with no data
//                ;
//            }
//
//        }

        return self::SUCCESS;
    }



}
