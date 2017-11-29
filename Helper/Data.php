<?php
namespace Beecom\GooglecloudStorage\Helper;

use Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $useGCS = null;

    /**
     * Check whether we are allowed to use Google Cloud buckets as our file storage backend.
     *
     * @return bool
     */
    public function checkGCSUsage()
    {
        if (is_null($this->useGCS)) {
            $currentStorage = (int)$this->scopeConfig->getValue(Storage::XML_PATH_STORAGE_MEDIA);
            if($currentStorage == 2){
                $this->useGCS = $currentStorage == 0;
            }else{
                $this->useGCS = $currentStorage == Storage::STORAGE_MEDIA_GCS;
            }
        }
        return $this->useGCS;
    }

    public function getAccessKey()
    {
        return json_decode($this->scopeConfig->getValue('google_cloud/general/access_key'),true);
    }

    public function getProject()
    {
        return $this->scopeConfig->getValue('google_cloud/general/project');
    }

    public function getRegion()
    {
        return $this->scopeConfig->getValue('google_cloud/general/region');
    }

    public function getBucket()
    {
        return $this->scopeConfig->getValue('google_cloud/general/bucket');
    }
}
