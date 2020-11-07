<?php

namespace App\Traits;

use Illuminate\Support\Facades\File as FileInstance;
use Illuminate\Support\Facades\Storage;

trait File
{
    /**
     * File directory.
     * @var string
     */
    private $directory;

    /**
     * Upload the file.
     * @param $file
     * @return array
     */
    public function upload($file)
    {
        $directory = $this->getDirectory();
        if ($this->isFile($file)) {
            if ($path = $file->store($directory)) {
                return ['success' => true, 'name' => $this->getFileName($directory, $path)];
            }
        }
        return ['success' => false, 'name' => ''];
    }

    /**
     * Delete the file.
     * @param $fileLink
     * @return array
     */
    public function delete($fileLink)
    {
        if (Storage::exists($fileLink)) {
            Storage::delete($fileLink);

            return ['success' => true, 'fileLink' => $fileLink];
        }
        return ['success' => false, 'fileLink' => $fileLink];
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param $file
     * @return string
     */
    private function isFile($file)
    {
        return FileInstance::isFile($file);
    }

    /**
     * @param $directory
     * @param $path
     * @return string
     */
    private function getFileName($directory, $path)
    {
        return str_replace($directory, '', $path);
    }
}
