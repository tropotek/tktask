<?php
namespace App\Db;

use Bs\Db\File;
use Bs\Db\Traits\TimestampTrait;
use Tk\Db\Mapper\Model;

class Example extends Model
{
    use TimestampTrait;

    public int $exampleId = 0;

    public string $name = '';

    //public ?string $nick = null;

    public string $image = '';

    //public string $content = '';

    //public string $notes = '';

    public bool $active = true;

    public ?\DateTime $modified = null;

    public ?\DateTime $created = null;


    public function __construct()
    {
        $this->_TimestampTrait();
    }

    public function getFileList(array $filter = []): array
    {
        $filter += ['model' => $this];
        return File::findFiltered($filter);
    }

    public function getDataPath(): string
    {
        return sprintf('/exampleFiles/%s', $this->getVolatileId());
    }


    public function getExampleId(): int
    {
        return $this->exampleId;
    }

    public function setExampleId(int $exampleId): Example
    {
        $this->exampleId = $exampleId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

//    public function getNick(): ?string
//    {
//        return $this->nick;
//    }
//
//    public function setNick(?string $nick): static
//    {
//        $this->nick = $nick;
//        return $this;
//    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image = ''): static
    {
        $this->image = $image;
        return $this;
    }

//    public function getContent(): string
//    {
//        return $this->content;
//    }
//
//    public function setContent(string $content): static
//    {
//        $this->content = $content;
//        return $this;
//    }
//
//    public function getNotes(): string
//    {
//        return $this->notes;
//    }
//
//    public function setNotes(string $notes): static
//    {
//        $this->notes = $notes;
//        return $this;
//    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->getName()) {
            $errors['name'] = 'Invalid field value';
        }
        return $errors;
    }

}
