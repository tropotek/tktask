<?php
namespace App\Console;

use App\Db\Expense;
use App\Db\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Config;
use Tk\Db;
use Tk\FileUtil;
use Tk\Log;

class MigrateTis extends Console
{

    protected function configure(): void
    {
        $this->setName('migrateTis')
            ->setAliases(['tis'])
            ->setDescription('Migrate the TkTis db to this TkTask');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getConfig();
        if (!Config::isDev()) {
            $this->writeError('Error: cannot run this command in a production environment.');
            return self::FAILURE;
        }

        $options = $config->get('migrate.tis', []);
        $oldDsn = $options['db.mysql.src'] ?? '';
        $srcDataPath = $options['srcData'] ?? '';

        if (!$oldDsn) {
            $this->output->writeln("<error>Original APD DB DSN not found in /config.php, add an entry `\$config['migrate.tis'] = ['db.mysql.src' => 'localhost:3306/{user}/{pass}/{dbname}]'; </error>");
            return self::FAILURE;
        }
        if ($oldDsn == Db::getDsn()) {
            $this->output->writeln("<error>Trying to migrate to the same DB</error>");
            return self::FAILURE;
        }

        $confirm = $this->askConfirmation('Replace the existing database, with fresh migration of tis [N]: ', false);
        if (!$confirm) {
            $this->output->writeln("Migration terminated.");
            return self::SUCCESS;
        }

        if (!$this->migrateDB(Db::parseDsn($oldDsn)['dbName'] ?? '', Db::getDbName())) {
            $this->output->writeln('<error>ERROR migrating old DB to new DB</error>');
            return self::FAILURE;
        }

        if (!$this->migrateDataFiles($srcDataPath)) {
            $this->output->writeln("<error>ERROR migrating data files to new structure, check 'srcData' in config</error>");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * execute the migrateTis.sql
     */
    protected function migrateDB(string $srcDb, string $destDb): bool
    {
        $this->output->writeln("<comment>Migrating database [{$srcDb} -> {$destDb}]</comment>");
        try {
            $sql = strval(file_get_contents(getcwd() . '/_notes/migrateTis.sql'));

            // update the src and dest db's in the SQL file
            $sql = str_replace('dev_tktis.', $srcDb.'.', $sql);
            $sql = str_replace('dev_tktask.', $destDb.'.', $sql);

            if (false === Db::execute($sql)) {
                Log::error(implode("\n", Db::getLastStatement()->errorInfo()));
                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->__toString());
            return false;
        }

        return true;
    }

    protected function migrateDataFiles(string $src): bool
    {
        $this->output->writeln("<comment>Migrating data files</comment>");
        if (!is_dir($src)) {
            return false;
        }
        try {
            $this->output->writeln("  <comment>- Migrate media files</comment>");
            FileUtil::mkdir(Config::makeDataPath(''));
            $dest = Config::makeDataPath('');
            FileUtil::copyDir($src.'/media', $dest.'/media');
            FileUtil::copyDir($src.'/project', $dest.'/project');
            FileUtil::copyDir($src.'/task', $dest.'/task');
            FileUtil::rmdir($dest.'/task/8');

            $this->output->writeln("  <comment>- Migrate expense files</comment>");
            $dirs = array_diff(scandir($src.'/expense'), array('..', '.'));
            foreach ($dirs as $expenseId) {
                $expense = Expense::find((int)$expenseId);
                if ($expense) {
                    $destPath = Config::makeDataPath($expense->dataPath);
                    FileUtil::mkdir(dirname($destPath));
                    FileUtil::copyDir($src.'/expense/'.$expenseId, $destPath);

                    $file = File::findByModel($expense);
                    if ($file) {
                        $file->filename = $expense->dataPath . '/' . basename($file->filename);
                        $file->save();
                    }
                }
            }

            $this->output->writeln("  <comment>- Delete empty folders</comment>");
            FileUtil::removeEmptyFolders(Config::makeDataPath(''));

        } catch (\Exception $e) {
            Log::error($e->__toString());
            return false;
        }

        return true;
    }

}
