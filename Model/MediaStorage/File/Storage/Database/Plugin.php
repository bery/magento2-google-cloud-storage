<?php
namespace Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage\Database;

class Plugin
{
    private $helper;

    private $storageModel;

    public function __construct(
        \Beecom\GooglecloudStorage\Helper\Data $helper,
        \Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage\Gcs $storageModel
    ) {
        $this->helper = $helper;
        $this->storageModel = $storageModel;
    }

    public function aroundGetDirectoryFiles($subject, $proceed, $directory)
    {
        if ($this->helper->checkGCSUsage()) {
            return $this->storageModel->getDirectoryFiles($directory);
        }
        return $proceed($directory);
    }
}
