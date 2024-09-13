<?php
namespace App\Console;

use App\Db\Example;
use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Db;

class TestData extends \Bs\Console\Command\TestData
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
            $obj->name = $this->createName();
            $obj->image = '***';
            $obj->active = (bool)rand(0,1);
            $obj->save();
        }
    }

    public function createUsers(): void
    {
        // Generate new users
        for($i = 0; $i < 50; $i++) {
            $obj = new User::$USER_CLASS();
            $obj->uid ='***';
            $obj->type = (rand(1, 10) <= 5 ? User::TYPE_STAFF : User::TYPE_MEMBER);

            // Add permissions
            if ($obj->isType(User::TYPE_STAFF)) {
                $perm = 0;
                if (rand(1, 10) <= 5) {
                    $perm = Permissions::PERM_ADMIN;
                } else {
                    if (rand(1, 10) <= 5) {
                        $perm |= Permissions::PERM_SYSADMIN;
                    }
                    if (rand(1, 10) <= 5) {
                        $perm |= Permissions::PERM_MANAGE_STAFF;
                    }
                    if (rand(1, 10) <= 5) {
                        $perm |= Permissions::PERM_MANAGE_MEMBERS;
                    }
                }
                $obj->permissions = $perm;
            }
            $obj->nameFirst = $this->createName();
            $obj->nameLast = $this->createName();
            do {
                $obj->username = strtolower($this->createName()) . '.' . rand(1000, 10000000);
            } while(User::findByUsername($obj->username) != null);
            $obj->password = User::hashPassword('password');
            $obj->email = $this->createUniqueEmail($obj->username);
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
