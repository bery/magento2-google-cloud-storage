<?php
namespace Beecom\GooglecloudStorage\Model\MediaStorage\Config\Source\Storage\Media\Storage;

class Plugin
{
    public function afterToOptionArray($subject, $result)
    {
        $result[] = [
            'value' => \Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage::STORAGE_MEDIA_GCS,
            'label' => __('Google Cloud Storage')
        ];
        return $result;
    }
}
