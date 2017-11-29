<?php
namespace Beecom\GooglecloudStorage\Model\Config\Source;

class Region implements \Magento\Framework\Option\ArrayInterface
{
    private $helper;

    public function __construct(\Beecom\GooglecloudStorage\Helper\Gcs $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Return list of available Google Cloud Storage regions
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->helper->getRegions();
    }
}
