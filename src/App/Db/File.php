<?php
namespace App\Db;

use Bs\Auth;
use Bs\Traits\ForeignModelTrait;
use Tk\Config;
use Tk\Exception;
use Tk\FileUtil;
use Tk\Log;
use Tk\Uri;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

class File extends Model
{
    use ForeignModelTrait;

    public int        $fileId   = 0;
    public ?int       $userId   = null;
    public string     $fkey     = '';
    public int        $fid      = 0;
    public string     $filename = '';
    public int        $bytes    = 0;
    public string     $mime     = '';
    public string     $label    = '';
    public string     $notes    = '';
    public bool       $selected = false;
    public string     $hash     = '';

    public \DateTimeImmutable $created;


    public function __construct()
    {
        $this->created = new \DateTimeImmutable();
    }

    /**
     * Create a File object form an existing file path
     * Only the relative path from the system data path is stored
     *
     * @param string $filename Full/Relative data path to a valid file
     */
    public static function create(string $filename, ?Model $model = null, ?int $userId = null): self
    {
        if (empty($filename)) {
            throw new Exception('Invalid file path.');
        }

        $obj = new self();
        $obj->filename = $filename;
        $obj->label = basename($filename);
        if ($model instanceof Model) {
            $obj->setDbModel($model);
        }
        if (is_null($userId) && Auth::getAuthUser()) {
            $userId = Auth::getAuthUser()->fid;
        }
        $obj->userId = $userId;

        return $obj;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        if (is_file($this->getFullPath())) {
            if (!$this->bytes) {
                $this->bytes = intval(filesize($this->getFullPath()));
            }
            if (!$this->mime) {
                $this->mime = \Tk\FileUtil::getMimeType($this->getFullPath());
            }
        }

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
        }
        return (false !== Db::delete('file', ['file_id' => $this->fileId]));
    }

    public function getFullPath(): string
    {
        return Config::makePath(Config::getDataPath() . $this->filename);
    }

    public function getExtension(): string
    {
        return FileUtil::getExtension($this->filename);
    }

    public function getUrl(): Uri
    {
        return Uri::create(Config::makeUrl(Config::getDataPath() . $this->filename));
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->filename) {
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

    public static function find(int $fileId): ?self
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

    public static function findByHash(string $hash): ?self
    {
        return self::findFiltered(['hash' => $hash])[0] ?? null;
    }

    public static function findByFilename(string $filename): ?self
    {
        return self::findFiltered(['filename' => $filename])[0] ?? null;
    }

    /**
     * @return array<int,self>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.file_id) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.filename) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.mime) LIKE LOWER(:search) OR ';
            $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
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

        if (is_bool(truefalse($filter['selected'] ?? null))) {
            $filter['selected'] = truefalse($filter['selected']);
            $filter->appendWhere('a.selected = :selected AND ');
        }

        if (!empty($filter['filename'])) {
            $filter->appendWhere('a.filename = :filename AND ');
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

    public static function optimizePdf(string $path, int $dpi = 130): bool
    {
        $temp = dirname($path).'/tmp.pdf';

        $cmd = str_replace("\n", "", sprintf('/usr/bin/gs
          -q -dNOPAUSE -dBATCH -dSAFER
          -sDEVICE=pdfwrite
          -dCompatibilityLevel=1.3
          -dPDFSETTINGS=/screen
          -dEmbedAllFonts=true
          -dSubsetFonts=true
          -dAutoRotatePages=/None
          -dColorImageDownsampleType=/Bicubic
          -dColorImageResolution=%s
          -dGrayImageDownsampleType=/Bicubic
          -dGrayImageResolution=%s
          -dMonoImageDownsampleType=/Subsample
          -dMonoImageResolution=%s
          -sOutputFile=%s
          %s', $dpi, $dpi, $dpi, escapeshellarg($temp), escapeshellarg($path) ));

        $ok = exec(escapeshellcmd($cmd));
        if ($ok === false) {
            Log::warning("Failed to optimize PDF file {$path}");
            return false;
        }

        $srcBytes  = filesize($path);
        $destBytes = filesize($temp);

        if (Config::isDebug()) {
            $fs = FileUtil::bytes2String($srcBytes);
            $fd = FileUtil::bytes2String($destBytes);
            Log::debug("- compressing: {$path} [$fs => $fd]");
        }

        // copy smaller file to dest
        if ($destBytes > 0 && $destBytes < $srcBytes) {
            rename($temp, $path);
        }

        if (is_file($temp)) {
            unlink($temp);
        }

        return true;
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