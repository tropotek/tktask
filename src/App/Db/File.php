<?php
namespace App\Db;

use Bs\Auth;
use Bs\Traits\CreatedTrait;
use Bs\Traits\ForeignModelTrait;
use Bs\Traits\SystemTrait;
use Tk\Config;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

class File extends Model
{
    use SystemTrait;
    use ForeignModelTrait;
    use CreatedTrait;

    public int        $fileId   = 0;
    public int        $userId   = 0;
    public string     $fkey     = '';
    public int        $fid      = 0;
    public string     $path     = '';
    public int        $bytes    = 0;
    public string     $mime     = '';
    public string     $label    = '';
    public string     $notes    = '';
    public bool       $selected = false;
    public string     $hash     = '';
    public ?\DateTime $created  = null;


    public function __construct()
    {
        $this->_CreatedTrait();
    }

    /**
     * Create a File object form an existing file path
     * Only the relative path from the system data path is stored
     *
     * @param string $file Full/Relative data path to a valid file
     */
    public static function create(string $file, ?Model $model = null, int $userId = 0): static
    {
        if (empty($file)) {
            throw new Exception('Invalid file path.');
        }

        $obj = new static();

        $obj->path = $file;
        $dataPath = Config::makePath(Config::getDataPath());
        if (str_starts_with($file, $dataPath)) {
            $obj->path = str_replace($dataPath, '', $file);
        }

        $obj->label = \Tk\FileUtil::removeExtension(basename($file));
        if ($model) {
            $obj->setDbModel($model);
        }
        if (!$userId) {
            if ($model && property_exists($model, 'userId')) {
                $userId = $model->userId;
            } else if (Auth::getAuthUser()) {
                $userId = Auth::getAuthUser()->fid;
            }
        }
        $obj->userId = $userId;

        if (is_file($obj->getFullPath())) {
            $obj->bytes = filesize($obj->getFullPath());
            $obj->mime = \Tk\FileUtil::getMimeType($obj->getFullPath());
        }

        return $obj;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->fileId) {
            $values['file_id'] = $this->fileId;
            Db::update('file', 'file_id', $values);
        } else {
            unset($values['file_id']);
            Db::insert('file', $values);
            $this->fileId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        if (is_file($this->getFullPath())) {
            unlink($this->getFullPath());
            Log::alert('File deleted: ' . $this->path);
        }
        return (false !== Db::delete('file', ['file_id' => $this->fileId]));
    }

    public function getFullPath(): string
    {
        return Config::makePath(Config::getDataPath() . $this->path);
    }

    public function getUrl(): Uri
    {
        return Uri::create(Config::makeUrl(Config::getDataPath() . $this->path));
    }

    public function isImage(): bool
    {
        return preg_match('/^image\//', $this->mime);
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->path) {
            $errors['path'] = 'Please enter a valid path';
        }
        if (!$this->bytes) {
            $errors['bytes'] = 'Please enter a file size';
        }
        if (!$this->mime) {
            $errors['mime'] = 'Please enter a file type';
        }

        $hashed = self::findByHash($this->hash);
        if ($hashed && $hashed->fileId != $this->fileId) {
            $errors['duplicate'] = 'Cannot overwrite an existing file. [ID: ' . $hashed->fileId . ']';
        }

        return $errors;
    }

    public static function find(int $fileId): ?static
    {
        return Db::queryOne("
            SELECT *
            FROM v_file
            WHERE file_id = :fileId",
            compact('fileId'),
            self::class
        );
    }

    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_file",
            [],
            self::class
        );
    }

    public static function findByHash(string $hash): ?static
    {
        return self::findFiltered(['hash' => $hash])[0] ?? null;
    }

    public static function findByPath(string $path): ?static
    {
        return self::findFiltered(['path' => $path])[0] ?? null;
    }

    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.file_id) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.path) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.mime) LIKE LOWER(:search) OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['fileId'] = $filter['id'];
        }
        if (!empty($filter['fileId'])) {
            if (!is_array($filter['fileId'])) $filter['fileId'] = [$filter['fileId']];
            $filter->appendWhere('a.file_id IN :fileId AND ', $filter['fileId']);
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.file_id NOT IN %s AND ', $filter['exclude']);
        }

        if (isset($filter['label'])) {
            if (!is_array($filter['label'])) $filter['label'] = [$filter['label']];
            $filter->appendWhere('a.label IN :label AND ', $filter['label']);
        }
        if (isset($filter['mime'])) {
            if (!is_array($filter['mime'])) $filter['mime'] = [$filter['mime']];
            $filter->appendWhere('a.mime IN :mime AND ', $filter['mime']);
        }

        if (is_bool($filter['selected'])) {
            $filter->appendWhere('a.selected = :selected AND ');
        }

        if (!empty($filter['path'])) {
            $filter->appendWhere('a.path = :path AND ');
        }
        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = self::getDbModelId($filter['model']);
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }

        return Db::query("
            SELECT *
            FROM v_file a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public static function getIcon(string $filename): string
    {
        $ext = \Tk\FileUtil::getExtension($filename);
        switch ($ext) {
            case 'zip':
            case 'gz':
            case 'tar':
            case 'tar.gz':
            case 'gtz':
            case 'rar':
            case '7zip':
            case 'jar':
            case 'pkg':
            case 'deb':
                return 'fa fa-file-archive-o';
            case 'h':
            case 'c':
            case 'php':
            case 'js':
            case 'css':
            case 'less':
            case 'txt':
            case 'xml':
            case 'xslt':
            case 'json':
                return 'fa fa-file-code-o';
            case 'ods':
            case 'sdc':
            case 'sxc':
            case 'xls':
            case 'xlsm':
            case 'xlsx':
            case 'csv':
                return 'fa fa-file-excel-o';
            case 'bmp':
            case 'emf':
            case 'gif':
            case 'ico':
            case 'icon':
            case 'jpeg':
            case 'jpg':
            case 'pcx':
            case 'pic':
            case 'png':
            case 'psd':
            case 'raw':
            case 'tga':
            case 'tif':
            case 'tiff':
            case 'swf':
            case 'drw':
            case 'svg':
            case 'svgz':
            case 'ai':
                return 'fa fa-file-image-o';
            case 'aiff':
            case 'cda':
            case 'dvf':
            case 'flac':
            case 'm4a':
            case 'm4b':
            case 'midi':
            case 'mp3':
            case 'ogg':
            case 'pcm':
            case 'snd':
            case 'wav':
                return 'fa fa-file-audio-o';
            case 'avi':
            case 'mov':
            case 'mp4':
            case 'mpg':
            case 'mpeg':
            case 'mkv':
            case 'ogv':
            case 'flv':
            case 'webm':
            case 'wmv':
            case 'asx':
                return 'fa fa-file-video-o';
            case 'pdf':
                return 'fa fa-file-pdf-o';
            case 'ppt':
            case 'pot':
            case 'potx':
            case 'pps':
            case 'ppsx':
            case 'pptx':
            case 'pptm':
                return 'fa fa-file-powerpoint-o';
            case 'doc':
            case 'docm':
            case 'dotm':
            case 'dotx':
            case 'docx':
            case 'dot':
            case 'wri':
            case 'wps':
                return 'fa fa-file-word-o';
        }
        return 'fa fa-file-o';
    }
}