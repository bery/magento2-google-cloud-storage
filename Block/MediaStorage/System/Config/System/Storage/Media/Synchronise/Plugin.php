<?php
namespace Beecom\GooglecloudStorage\Block\MediaStorage\System\Config\System\Storage\Media\Synchronise;

class Plugin
{
    public function aroundGetTemplate()
    {
        return 'Google_Cloud::system/config/system/storage/media/synchronise.phtml';
    }
}
