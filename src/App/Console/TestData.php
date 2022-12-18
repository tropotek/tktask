<?php
namespace App\Console;

use App\Db\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Tropotek <https://www.tropotek.com/>
 */
class TestData extends \Tk\Console\Command\TestData
{

    protected function configure()
    {
        $this->setName('testData')
            ->setAliases(array('td'))
            ->setDescription('Fill the database with test data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

        $db = $this->getFactory()->getDb();

        $db->exec('DELETE FROM `user` WHERE `notes` = \'***\' ');
        for($i = 0; $i < 250; $i++) {
            $obj = new \App\Db\User();
            $obj->setFirstName($this->createName());
            $obj->setLastName($this->createName());
            do {
                $obj->setUsername(strtolower($this->createName()) . '.' . rand(1000, 10000000));
            } while(\App\Db\UserMap::create()->findByUsername($obj->getUsername()) != null);
            $obj->setPassword('password');
            $obj->setEmail($this->createUniqueEmail($obj->getUsername()));
            $obj->setType((rand(1, 10) <= 5) ? User::TYPE_ADMIN : User::TYPE_MEMBER);
            $obj->setNotes('***');
            $obj->save();
        }

        return self::FAILURE;
    }

}
