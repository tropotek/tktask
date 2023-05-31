<?php
namespace App\Db;

use Tk\DataMap\DataMap;
use Tk\Db\Mapper\Filter;
use Tk\Db\Mapper\Mapper;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\DataMap\Table;

class UserMap extends Mapper
{
    const REMEMBER_CID = '__rmb';

    public function makeDataMaps(): void
    {
        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('id'));
            $map->addDataType(new Db\Text('uid'));
            $map->addDataType(new Db\Text('type'));
            $map->addDataType(new Db\Integer('permissions'));
            $map->addDataType(new Db\Text('username'));
            $map->addDataType(new Db\Text('password'));
            $map->addDataType(new Db\Text('email'));
            $map->addDataType(new Db\Text('name'));
            $map->addDataType(new Db\Text('notes'));
            $map->addDataType(new Db\Text('timezone'))->setNullable(true);
            $map->addDataType(new Db\Boolean('active'));
            $map->addDataType(new Db\Text('hash'));
            $map->addDataType(new Db\Date('lastLogin', 'last_login'))->setNullable(true);
            //$map->addDataType(new Db\Boolean('del'));
            $map->addDataType(new Db\Date('modified'));
            $map->addDataType(new Db\Date('created'));
//            $del = $map->addDataType(new Db\Boolean('del'));
//            $this->setDeleteType($del);
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('id'));
            $map->addDataType(new Form\Text('uid'));
            $map->addDataType(new Form\Text('type'));
            $map->addDataType(new Form\Integer('permissions'));
            $map->addDataType(new Form\Text('username'));
            $map->addDataType(new Form\Text('password'));
            $map->addDataType(new Form\Text('email'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Boolean('active'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Form\Text('timezone'))->setNullable(true);
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_TABLE)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('id'));
            $map->addDataType(new Form\Text('uid'));
            $map->addDataType(new Form\Text('type'));
            $map->addDataType(new Form\Integer('permissions'));
            $map->addDataType(new Form\Text('username'));
            $map->addDataType(new Form\Text('password'));
            $map->addDataType(new Form\Text('email'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('timezone'));
            $map->addDataType(new Table\Boolean('active'));
            $map->addDataType(new Form\Date('modified'))->setDateFormat('d/m/Y h:i:s');
            $map->addDataType(new Form\Date('created'))->setDateFormat('d/m/Y h:i:s');
            $this->addDataMap(self::DATA_MAP_TABLE, $map);
        }
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findFiltered(['username' => $username])->current();
    }

    public function findBySelector(string $selector): ?User
    {
        return $this->findFiltered(['selector' => $selector])->current();
    }

    public function findByHash(string $hash): ?User
    {
        return $this->findFiltered(['hash' => $hash])->current();
    }

    /**
     * @return Result|User[]
     */
    public function findFiltered(array|Filter $filter, ?Tool $tool = null): Result
    {
        return $this->selectFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['search'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['search']) . '%';
            $w = '';
            $w .= sprintf('a.uid LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.username LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['search'])) {
                $id = (int)$filter['search'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['userId'])) {
            $filter['id'] = $filter['userId'];
        }
        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = %s AND ', $this->quote($filter['uid']));
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = %s AND ', $this->quote($filter['hash']));
        }

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = %s AND ', $this->quote($filter['username']));
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = %s AND ', $this->quote($filter['email']));
        }

        if (is_bool($filter['active'] ?? '')) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        // Filter for any remember me saved token selectors
        if (!empty($filter['selector'])) {
            $filter->appendFrom('INNER JOIN %s z ON (z.user_id = a.id) ', $this->quoteParameter('user_tokens'));
            $filter->appendWhere('z.selector = %s AND expiry > NOW() AND ', $this->quote($filter['selector']));
        }

        return $filter;
    }


    /*
     * Functions to manage the "remember me" tokens
     * https://www.phptutorial.net/php-tutorial/php-remember-me/
     */

    /**
     * Generate a pair of random tokens called selector and validator
     */
    public function generateToken(): array
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        return [$selector, $validator, $selector . ':' . $validator];
    }

    /**
     * Split a token stored in the cookie into selector and validator
     */
    public function parseToken(string $token): ?array
    {
        $parts = explode(':', $token);

        if ($parts && count($parts) == 2) {
            return [$parts[0], $parts[1]];
        }
        return null;
    }

    /**
     * Add a new row to the user_tokens table
     */
    public function insertToken(int $user_id, string $selector, string $hashed_validator, string $expiry): bool
    {
        $sql = 'INSERT INTO user_tokens(user_id, selector, hashed_validator, expiry)
            VALUES(:user_id, :selector, :hashed_validator, :expiry)';

        $statement = $this->getDb()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':selector', $selector);
        $statement->bindValue(':hashed_validator', $hashed_validator);
        $statement->bindValue(':expiry', $expiry);

        return $statement->execute();
    }

    /**
     * Find a row in the user_tokens table by a selector.
     * It only returns the match selector if the token is not expired
     *   by comparing the expiry with the current time
     */
    public function findTokenBySelector(string $selector)
    {
        $sql = 'SELECT id, selector, hashed_validator, user_id, expiry
            FROM user_tokens
            WHERE selector = :selector
            AND expiry >= NOW()
            LIMIT 1';

        $statement = $this->getDb()->prepare($sql);
        $statement->bindValue(':selector', $selector);

        $statement->execute();

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteToken(int $user_id): bool
    {
        $sql = 'DELETE FROM user_tokens WHERE user_id = :user_id';
        $statement = $this->getDb()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);

        // Remove all expired tokens too (see sql/events.sql)
//        $sql = 'DELETE FROM user_tokens WHERE expiry < NOW()';
//        $this->getDb()->exec($sql);

        return $statement->execute();
    }
}
