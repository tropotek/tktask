<?php
namespace App\Dom\Modifier;

use App\Db\User;
use Dom\Mvc\Modifier\FilterInterface;
use Tk\Traits\SystemTrait;

/**
 * This object checks for any app attribute tags in a template and modifies a node as
 * required by the app tag.
 * Available attributes available for nodes are:
 *
 * - app-is-type="User::TYPE_STAFF": remove a node if the current user is not of user type
 * - app-has-perm="User::PERM_SYSADMIN":  remove a node if the current user does not have the permissions
 *
 * @experimental
 */
class AppAttributes extends FilterInterface
{
    use SystemTrait;

    const APP_IS_USER   = 'app-is-user';
    const APP_IS_TYPE   = 'app-is-type';
    const APP_HAS_PERM  = 'app-has-perm';


    public function __construct() { }

    /**
     * pre init the Filter
     */
    public function init(\DOMDocument $doc) { }

    /**
     * Call this method to traverse a document
     */
    public function executeNode(\DOMElement $node)
    {
        $isUser = is_object($this->getFactory()->getAuthUser());
        $user = $this->getFactory()->getAuthUser();
        $reflect = new \ReflectionClass('App\Db\User');
        $userConsts = $reflect->getConstants();
        try {

            if ($node->hasAttribute(self::APP_IS_USER)) {
                $val = trim($node->getAttribute(self::APP_IS_USER));
                $showNode = preg_match('/(yes|true|1)/i', $val);
                if (($isUser && !$showNode) || (!$isUser && $showNode)) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

            if ($node->hasAttribute(self::APP_IS_TYPE)) {
                $type = $node->getAttribute(self::APP_IS_TYPE);
                if (!$user || !$user->isType($userConsts[$type])) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

            if ($node->hasAttribute(self::APP_HAS_PERM)) {
                $perms = explode('|', $node->getAttribute(self::APP_HAS_PERM));
                $perms = array_map('trim', $perms);
                $perm = array_sum(array_filter($userConsts, function($k) use($perms) { return in_array($k, $perms); }, ARRAY_FILTER_USE_KEY));
                if (!$user || !$user->hasPermission($perm)) {
                    $this->getDomModifier()->removeNode($node);
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * called after DOM tree is traversed
     */
    public function postTraverse(\DOMDocument $doc)
    {

    }
}
