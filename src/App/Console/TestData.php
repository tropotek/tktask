<?php
namespace App\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class TestData extends \Bs\Console\TestData
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('testData')
            ->setAliases(array('td'))
            ->setDescription('Fill the database with test data');
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

        $db = $this->getConfig()->getDb();
//        $db->exec('DELETE FROM `user_role` WHERE `description` = \'***\' ');
//        $db->exec('TRUNCATE `user_role_institution`');
//        for($i = 0; $i < 20; $i++) {
//            $obj = new \Uni\Db\Role();
//            do {
//                $obj->name = $this->createName() . '.' . rand(1000, 10000000);
//            } while(\Uni\Db\RoleMap::create()->findFiltered(array('name' => $obj->name))->count());
//            $obj->type = (rand(1, 10) <= 5) ? \Uni\Db\Role::TYPE_STAFF : \Uni\Db\Role::TYPE_STUDENT;
//            $obj->description = '***';
//            $obj->active = (rand(1, 10) <= 9);
//            $obj->save();
//            if ((rand(1, 10) <= 5)) {
//                \Uni\Db\RoleMap::create()->addInstitution($obj->getId(), rand(1,2));
//            }
//        }

//        $db->exec('DELETE FROM `user` WHERE `notes` = \'***\' ');
//        for($i = 0; $i < 25; $i++) {
//            $obj = new \Uni\Db\User();
//            $obj->name = $this->createName();
//            do {
//                $obj->username = strtolower($this->createName()) . '.' . rand(1000, 10000000);
//            } while(\Uni\Db\UserMap::create()->findByUsername($obj->username) != null);
//            $obj->email = $this->createUniqueEmail();
//            $obj->roleId = (rand(1, 10) <= 5) ? \Uni\Db\Role::DEFAULT_TYPE_STAFF : \Uni\Db\Role::DEFAULT_TYPE_STUDENT;
//            $obj->notes = '***';
//            $obj->save();
//            $obj->setNewPassword('password');
//            $obj->save();
//        }

    }



}
