<?php
namespace App\Console;

use App\Db\User;
use Bs\Auth;
use Bs\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Tk\Config;

class CreateAdmin extends Console
{

    protected function configure()
    {
        $this->setName('create-admin')
            ->setAliases(['adm'])
            ->addArgument('username', InputArgument::REQUIRED, 'A valid username.')
            ->setDescription('Create a new admin user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        $user = Auth::findByUsername($username);
        if ($user instanceof Auth) {
            $this->writeError('Error: User with that username already exists.');
            return self::FAILURE;
        }

        $user = new User();
        $user->givenName = $username;
        $user->type = User::TYPE_STAFF;
        $user->country = 'AU';
        $user->save();

        $auth = Auth::create($user);
        $auth->username = $username;
        $auth->email = $username . '@' . Config::getHostname();
        $auth->permissions = Auth::PERM_ADMIN;

        $email = '';
        $pass  = '';
        $first = true;
        do {
            if (!$first) {
                $this->writeError("Invalid Email: \n");
            }
            $q = new Question('Enter user email['.$auth->email.']: ', $auth->email);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $email = $this->getHelper('question')->ask($input, $output, $q);
            $first = false;
        } while(!filter_var($email, FILTER_VALIDATE_EMAIL));

        $errors = [];
        do {
            if (count($errors)) {
                $this->writeError("Invalid Password: \n  - " . implode("\n  - ", $errors));
            }
            $q = new Question('Enter the new password: ', '');
            $q->setHidden(true);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $pass = $this->getHelper('question')->ask($input, $output, $q);
        } while($errors = Auth::validatePassword($pass));

        do {
            if (count($errors)) {
                $this->writeError("Passwords do not match.\n");
            }
            $q = new Question('Confirm new password: ', '');
            $q->setHidden(true);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $passConf = $this->getHelper('question')->ask($input, $output, $q);
        } while($pass != $passConf);

        $auth->email = $email;
        $auth->password = Auth::hashPassword($pass);
        $auth->save();

        $this->writeGreen('New admin user created.');

        return self::SUCCESS;
    }

}
