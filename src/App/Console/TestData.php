<?php
namespace App\Console;

use Bs\Db\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestData extends \Tk\Console\Command\TestData
{

    protected function configure()
    {
        $this->setName('testData')
            ->setAliases(['td'])
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear all test data')
            ->setDescription('Fill the database with test data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

        $db = $this->getFactory()->getDb();

        $this->clearData();
        if ($input->getOption('clear')) return self::SUCCESS;

        // Generate new users
        for($i = 0; $i < 150; $i++) {
            $obj = $this->getFactory()->createUser();
            $obj->setUid('***');
            $obj->setType((rand(1, 10) <= 5) ? User::TYPE_STAFF : User::TYPE_USER);

            // Add permissions
            if ($obj->isType(User::TYPE_STAFF)) {
                $perm = 0;
                if (rand(1, 10) <= 5) {
                    $perm = User::PERM_ADMIN;
                } else {
                    if (rand(1, 10) <= 5) {
                        $perm |= User::PERM_SYSADMIN;
                    }
                    if (rand(1, 10) <= 5) {
                        $perm |= User::PERM_MANAGE_STAFF;
                    }
                    if (rand(1, 10) <= 5) {
                        $perm |= User::PERM_MANAGE_USER;
                    }
                }
                $obj->setPermissions($perm);
            }
            $obj->setName($this->createName() . ' ' . $this->createName());
            do {
                $obj->setUsername(strtolower($this->createName()) . '.' . rand(1000, 10000000));
            } while(\Bs\Db\UserMap::create()->findByUsername($obj->getUsername()) != null);
            $obj->setPassword(\Bs\Db\User::hashPassword('password'));
            $obj->setEmail($this->createUniqueEmail($obj->getUsername()));
            $obj->save();
        }

        return self::SUCCESS;
    }

    private function clearData()
    {
        $db = $this->getFactory()->getDb();

        $db->exec('DELETE FROM `user` WHERE `uid` = \'***\' ');
    }


}
