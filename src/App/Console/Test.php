<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Console\Console;

class Test extends Console
{

    protected function configure()
    {
        $this->setName('test')
            ->setDescription('This is a test script');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }



        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display LIMIT 5 OFFSET 5
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display LIMIT 5 
            OFFSET 5
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display 
                               LIMIT 10 
                                   OFFSET 5
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display LIMIT 10
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display LIMIT 10, 0
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display LIMIT 50
        SQL;
        $this->sqlTest($sql);

        $sql = <<<SQL
            SELECT * FROM user ORDER BY name_display
        SQL;
        $this->sqlTest($sql);

        $output->writeln('Complete!!!');
        return self::SUCCESS;
    }


    private function sqlTest($sql)
    {

//        if (preg_match('/(.*)?(LIMIT([0-9]+)+(, ?)([0-9]+)+)/is', $sql, $match)) {
//        if (preg_match('/(.*)+(LIMIT ([0-9]+)(( )?,?( )?(OFFSET )?([0-9]+))?)?/i', $sql, $match)) {
        //if (preg_match('/(.*)?(LIMIT\s([0-9]+)((\s+OFFSET\s)?|(,\s?)?)([0-9]+)?)+$/is', trim($sql), $match)) {
        if (preg_match('/(.*)?(LIMIT\s([0-9]+)((\s+OFFSET\s)?|(,\s?)?)([0-9]+)?)+$/is', trim($sql), $match)) {
            vd($sql, $match);
            //vd($sql, $match[1], 'Limit: ' . $match[3], 'Offset: ' . $match[7]);
        }

        return $sql;
    }


}
