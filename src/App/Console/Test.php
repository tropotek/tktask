<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Test extends \Bs\Console\Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('test')
            ->setDescription('This is a test script only');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // required vars
        $config = \App\Config::getInstance();
        if (!$config->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return;
        }


        $sqlMigrateList = array('App Sql' => $config->getSrcPath() . '/config');
        if ($config->get('sql.migrate.list')) {
            $sqlMigrateList = $config->get('sql.migrate.list');
        }
        foreach ($sqlMigrateList as $searchPath) {
            $dirItr = new \RecursiveDirectoryIterator($searchPath, \RecursiveIteratorIterator::CHILD_FIRST);
            $itr = new \RecursiveIteratorIterator($dirItr);
            $regItr = new \RegexIterator($itr, '/\/sql\/\.$/');
            foreach ($regItr as $d) {
                $this->writeComment($d->getPath());
            }
        }

        sleep(20);

//        foreach ($sqlMigrateList as $searchPath) {
//            $this->write('Search Path: ' . $searchPath);
//            if (is_dir($searchPath)) {
//                $list = scandir($searchPath);
//                foreach ($list as $migratePath) {
//                    if (preg_match('/^(_|\.)/', $migratePath)) continue;
//                    $sqlPath = $config->getPluginPath() . '/' . $migratePath . '/sql';
//                    if (!is_dir($sqlPath)) continue;
//                    $this->writeComment('  Migrate Path: ' . $migratePath);
//                    $this->writeComment('    SQL Path: ' . $sqlPath);
////                    foreach ($migrate->migrate($sqlPath) as $f) {
////                        $io->write(self::green('  .' . $f));
////                    }
//                }
//            }
//        }


        $output->writeln('Complete!!!');

    }



}
