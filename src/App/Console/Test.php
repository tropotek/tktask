<?php
namespace App\Console;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Bs\Ui\Breadcrumbs;
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


//        $crumbs = new Breadcrumbs();
//
//
//        $uri1 = Uri::create('/invoiceManager');
//        $uri2 = Uri::create('/invoiceEdit', ['invoiceId' => 33]);
//        $uri3 = Uri::create('/invoiceView', ['invoiceId' => 33]);
//        $uri4 = Uri::create('/invoiceItemEdit', ['invoiceItemId' => 99]);
//
//
//        $crumbs->addCrumb($uri1, 'Invoice Manager');
//        $crumbs->addCrumb($uri2, 'Invoice Edit');
//        $crumbs->addCrumb($uri3, 'Invoice View');
//        $crumbs->addCrumb($uri4, 'Invoice Item Edit');
//
//        vd($crumbs);
//
//        $crumbs->addCrumb($uri2, 'Invoice Edit');
//
//        vd($crumbs);
//
//
//
//        vd($crumbs->getBackUrl()->toRelativeString());



        return self::SUCCESS;
    }



}
