<?php
namespace App\Console;

use App\Db\Example;
use Bs\Db\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tt\Db;

class TestData extends \Tk\Console\Command\TestData
{

    protected function configure(): void
    {
        $this->setName('testData')
            ->setAliases(['td'])
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear all test data')
            ->setDescription('Fill the database with test data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return self::FAILURE;
        }

        $this->clearData();
        if ($input->getOption('clear')) return self::SUCCESS;

        $this->createUsers();

        $this->createExamples();

        return self::SUCCESS;
    }

    public function createExamples(): void
    {
        for($i = 0; $i < 73; $i++) {
            $obj = new Example();
            $obj->setName($this->createName());
            $obj->setImage('***');
            $obj->setActive((bool)rand(0,1));
            $obj->save();
        }
    }

    public function createUsers(): void
    {
        // Generate new users
        for($i = 0; $i < 50; $i++) {
            $obj = $this->getFactory()->createUser();
            $obj->setUid('***');
            $obj->setType((rand(1, 10) <= 5) ? User::TYPE_STAFF : User::TYPE_MEMBER);

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
                        $perm |= User::PERM_MANAGE_MEMBER;
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

    }

    private function clearData(): void
    {
        $db = Db::getPdo();
        $db->exec("DELETE FROM user WHERE uid = '***'");
        $db->exec("DELETE FROM example WHERE image = '***'");
    }

}
