<?php
namespace App\Util;

use App\Db\User;
use App\Db\UserMap;
use Bs\Db\UserInterface;
use Bs\Factory;
use Tk\Traits\SystemTrait;

class Masquerade
{
    use SystemTrait;

    /**
     * Session ID
     */
    const SID = '__msq__';

    /**
     * Query string to initiate masquerading
     */
    const QUERY_MSQ = 'msq';


    /**
     * Masquerade as another user
     * return true on success, remember to redirect to the required page on success
     */
    public static function masqueradeLogin(UserInterface $user, UserInterface $msqUser): bool
    {
        if (!self::canMasqueradeAs($user, $msqUser)) return false;
        $factory = Factory::instance();

        // Get the masquerade queue from the session
        $msqArr = $factory->getSession()->get(static::SID);
        if (!is_array($msqArr)) $msqArr = [];

        // Save the current user and url to the session, to allow logout
        $userData = array(
            'userId' => $user->getUsername(),
            'url' => \Tk\Uri::create()->toString()
        );
        array_push($msqArr, $userData);
        // Save the updated masquerade queue
        $factory->getSession()->set(static::SID, $msqArr);
        // Simulates an AuthAdapter authenticate() method
        $factory->getAuthController()->getStorage()->write($msqUser->getUsername());

        return true;
    }

    /**
     * Log out of the current masquerading user
     * Redirects to the url the user was last on
     */
    public static function masqueradeLogout(): bool
    {
        $factory = Factory::instance();
        if (!self::isMasquerading()) return false;
        if (!$factory->getAuthController()->hasIdentity()) return false;
        $msqArr = $factory->getSession()->get(static::SID);
        if (!is_array($msqArr) || !count($msqArr)) return false;

        $userData = array_pop($msqArr);
        if (empty($userData['userId']) || empty($userData['url'])) return false;

        // Save the updated masquerade queue
        $factory->getSession()->set(static::SID, $msqArr);
        $factory->getAuthController()->getStorage()->write($userData['userId']);

        \Tk\Uri::create($userData['url'])->remove(self::QUERY_MSQ)->redirect();
        return true;
    }

    /**
     * Check if this user can masquerade as the supplied msqUser
     */
    public static function canMasqueradeAs(UserInterface $user, UserInterface $msqUser): bool
    {
        $factory = Factory::instance();
        if (!$msqUser->isActive()) return false;
        if ($user->getId() == $msqUser->getId()) return false;

        $msqArr = $factory->getSession()->get(static::SID);
        if (is_array($msqArr)) {    // Check if we are already masquerading as this user in the queue
            foreach ($msqArr as $data) {
                if ($data['userId'] == $msqUser->getId()) return false;
            }
        }
        return $user->canMasqueradeAs($msqUser);
    }

    /**
     * Get the user who is masquerading, ignoring any nested masqueraded users
     */
    public static function getMasqueradingUser(): ?UserInterface
    {
        $session = Factory::instance()->getSession();
        $user = null;
        if ($session->has(static::SID)) {
            $msqArr = current($session->get(static::SID));
            /** @var User $user */
            $user = UserMap::create()->find($msqArr['userId']);
        }
        return $user;
    }

    /**
     * Is this user currently masquerading
     */
    public static function isMasquerading(): bool
    {
        return (self::getNestings() > 0);
    }

    /**
     * Return the total masquerading nesting's (if any)
     *
     * 0 if not masquerading
     * >0 The masquerading total (for nested masquerading)
     *
     */
    public static function getNestings(): int
    {
        $session = Factory::instance()->getSession();
        if (!$session->has(static::SID)) return 0;
        $msqArr = $session->get(static::SID);
        return count($msqArr);
    }

    /**
     * logout of all masquerading users
     */
    public static function clearAll()
    {
        Factory::instance()->getSession()->remove(static::SID);
    }
}
