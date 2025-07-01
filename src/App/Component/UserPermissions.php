<?php
namespace App\Component;

use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Uri;

class UserPermissions extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'user-permissions';

    protected bool $canEdit = false;
    protected bool $canView = true;
    protected ?User $user = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()) return null;

        $userId = intval($_REQUEST['userId'] ?? 0);
        $this->canEdit = truefalse($_REQUEST['canEdit'] ?? false);
        $this->canView = truefalse($_REQUEST['canView'] ?? true);

        $action = trim($_REQUEST['action'] ?? '');
        $perms = $_POST['permissions'] ?? [];

        if (!$this->canView) return null;

        $this->user = User::find($userId);
        if (!($this->user instanceof User)) {
            return null;
        }

        if ($action == 'perms') {
            $auth = $this->user->getAuth();
            $perms = array_map('intval', $perms);
            $auth->permissions = array_sum($perms ?? []);
            $auth->save();
        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('container', 'id', self::CONTAINER_ID);

        foreach (User::PERMISSION_LIST as $perm => $descr) {
            $row = $template->getRepeat('row');
            $row->setText('descr', $descr);
            $row->setAttr('descr', 'for', 'cb_' . $perm);
            $row->setAttr('cb', 'name', 'permissions[]');
            $row->setAttr('cb', 'value', $perm);
            $row->setAttr('cb', 'id', 'cb_' . $perm);
            if (($this->user->permissions & $perm) != 0) {
                $row->setAttr('cb', 'checked', 'checked');
            }
            if (!$this->canEdit) {
                $row->addCss('row', 'disabled');
                $row->setAttr('cb', 'disabled', 'disabled');
            }
            if (User::PERMISSION_DESCRIPTION_LIST[$perm] ?? false) {
                $row->setText('notes', User::PERMISSION_DESCRIPTION_LIST[$perm]);
                $row->setVisible('notes');
            }
            $row->appendRepeat();
        }
        if ($this->canEdit) {
            $template->setAttr('form', 'hx-post', Uri::create());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div var="container">
    <div class="card card-edit mb-3">
        <div class="card-header"><i class="fas fa-user-shield"></i> Permissions</div>
        <div class="card-body">

            <form class="" var="form"
                hx-post=""
				hx-swap="none"
				hx-trigger="change delay:1s from:.form-check-input">
				<input type="hidden" name="action" value="perms">

                <div class="form-check form-switch mt-2" repeat="row">
                    <input class="form-check-input" type="checkbox" value="" id="" var="cb">
                    <label class="form-check-label" for="" var="descr"></label>
                    <p class="m-0 cb-notes text-muted" choice="notes"></p>
                </div>
            </form>

        </div>
    </div>
</div>
HTML;
        return Template::load($html);
    }

}
