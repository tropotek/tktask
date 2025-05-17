<?php
namespace App\Form\Field;

use Tk\Config;
use Tk\Db\Model;
use Tk\FileUtil;
use Tk\Path;

/**
 * Use this field in conjunction with the \App\Db\File object
 */
class File extends \Tk\Form\Field\File
{
    /**
     * The file owner object that will be used as the fkey and fid for the file records
     */
    protected Model $model;

    protected bool $enableSelect = false;


    public function __construct(string $name, ?Model $model = null)
    {
        parent::__construct($name);
        $this->model = $model;

        $this->setAttr('multiple', 'multiple');
        $this->setAttr('data-uploader', self::class);
        //$this->addCss('tk-multiinput');
    }

    public static function create(string $name, Model $model): self
    {
        return new self($name, $model);
    }

    /**
     * This is called only once the form has been submitted
     *   and new data loaded into the fields
     */
    public function execute(array $values = []): static
    {
        if ($this->hasFile()) {
            foreach ($this->getUploads() as $file) {
                $dest = Path::create($this->getModelDataPath() . '/' . $file['name']);
                //$dest = Config::makePath(Config::getDataPath() . $this->getModelDataPath() . '/' . $file['name']);
                FileUtil::mkdir(dirname($dest));
                //move_uploaded_file($file['tmp_name'], dirname($dest)."/".basename($dest));
                move_uploaded_file($file['tmp_name'], $dest);

                $file = \App\Db\File::create($dest, $this->getModel());
                // Remove any existing File if path matches
                $exists = \App\Db\File::findByFilename($file->filename);
                $exists?->delete();
                $file->save();
            }
        }
        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    protected function getModelDataPath(): string
    {
        $model = $this->getModel();
        if (property_exists($model, 'dataPath')) {
            /** @phpstan-ignore-next-line */
            return $model->dataPath;
        } elseif (method_exists($model, 'getDataPath')) {
            return $model->getDataPath();
        }
        return '';
    }
}