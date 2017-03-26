<?php

namespace Nicolasliu\Laravelfilemanager\Events;

class FileWasRenamed
{
    private $file_id;
    private $new_name;

    public function __construct($file_id, $new_name)
    {
        $this->$file_id = $file_id;
        $this->$new_name = $new_name;
    }

}
