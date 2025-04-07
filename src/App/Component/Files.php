<?php
namespace App\Component;

use App\Db\File;
use App\Db\User;
use Bs\Mvc\Form;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Config;
use Tk\Db;
use Tk\Exception;
use Tk\FileUtil;
use Tk\Form\Action\ActionInterface;
use Tk\Form\Action\Submit;
use Tk\Log;
use Tk\Table\Cell;
use Tk\Uri;

class Files extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Table     $table;
    protected Form      $form;
    protected ?Db\Model $model = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $fid = (int)($_POST['fid'] ?? $_GET['fid'] ?? 0);
        $fkey = trim($_POST['fkey'] ?? $_GET['fkey'] ?? '');

        if (!class_exists($fkey)) {
            Log::error("failed to find model {$fkey}");
            return null;
        }

        $this->model = $fkey::findDbModel($fid);
        if (!$this->model) {
            Log::error("failed to find model {$fkey} with id {$fid}");
            return null;
        }

        if (($_GET['act'] ?? '') == 'file-del') {
            $this->doDelete();
        }

        // file upload form
        $this->form = new Form(null, 'upload');
        $this->form->removeAttr('action');

        $field = $this->form->appendField(new \Tk\Form\Field\File('file'))
            ->addFieldCss('mt-0')
            ->setAttr('accept', '.pdf,.jpg,.png,.gif')
            ->setLabel('Upload File');

        $save = $this->form->appendField(new Submit('save', [$this, 'onSubmit']))->addCss('d-none');

        $field->setAttr('hx-post', Uri::create('/component/files', [
            'fkey' => $this->model::class,
            'fid' => $this->model->getId(),
        ]));
        $field->setAttr('hx-encoding', "multipart/form-data");
        $field->setAttr('hx-swap', 'outerHTML');
        $field->setAttr('hx-target', "#files-container");
        $field->setAttr('hx-select', "#files-container");
        $field->setAttr('hx-vals', json_encode([$save->getId() => $save->getName()]));

        $this->form->execute($_POST);


        // files table
        $this->table = new Table('files-comp');
        $this->table->setOrderBy('-created');
        $this->table->setLimit(10);
        $this->table->addCss('tk-table-sm');
        $this->table->hideReset();

        $this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\File $obj, Cell $cell) {
                $url = Uri::create('/component/files', [
                    'fkey' => $this->model::class,
                    'fid' => $this->model->getId(),
                    'act' => 'file-del',
                    'fileId' => $obj->fileId,
                ]);
                return <<<HTML
                    <a class="btn btn-outline-danger" title="Delete File"
                        hx-delete="{$url}"
                        hx-swap="outerHTML"
                        hx-target="#files-container"
                        hx-select="#files-container"
                        hx-confirm="Confirm you want to delete this file?"><i class="fas fa-fw fa-trash"></i></a>
                HTML;
            });

        $this->table->appendCell('filename')
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\File $obj, Cell $cell) {
                $filename = basename($obj->filename);
                $url = $obj->getUrl();
                return <<<HTML
                    <a href="$url" title="View File" target="_blank">{$filename}</a>
                HTML;
            });

        $this->table->appendCell('bytes')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\File $obj, Cell $cell) {
                return FileUtil::bytes2String($obj->bytes);
            });;

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter['model'] = $this->model;
        $rows = File::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return $this->show();
    }

    public function doDelete(): void
    {
        $fileId = intval($_GET['fileId'] ?? 0);
        $file = File::find($fileId);
        if ($file instanceof File) {
            $file->delete();
        }
    }

    public function onSubmit(Form $form, ActionInterface $action): void
    {
        /** @var \Tk\Form\Field\File $fileField */
        $fileField = $form->getField('file');
        if (!count($fileField->getUploads())) {
            $form->addFieldError('file', "No file uploaded");
        }

        $upload = $fileField->getUploaded();
        if (!is_array($upload)) {
            $form->addFieldError('file', "File upload error");
        }

        if ($form->hasErrors()) {
            return;
        }

        $dataPath = Config::makePath(Config::getDataPath() . '/' . $this->model->getDataPath());
        if ($fileField->move($dataPath)) {
            $path = $dataPath . '/' . $upload['name'];
            $file = File::create($path, $this->model, User::getAuthUser()->userId);
            $file->save();
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->table->getRenderer()->setMaxPages(3);
        $template->appendTemplate('content', $this->table->htmxShow());

        $this->form->addCss('mt-4');
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-fw fa-file"></i> <span var="title">Files</span></div>
    <div class="card-body" id="files-container" var="content">
    </div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
