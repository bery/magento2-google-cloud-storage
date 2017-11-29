<?php
namespace Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage;

// use Aws\S3\Exception\S3Exception;
use Magento\Framework\DataObject;
use Google\Cloud\Storage\StorageClient;

class Gcs extends DataObject
{
    /**
     * Store media base directory path
     *
     * @var string
     */
    protected $mediaBaseDirectory = null;

    private $client;

    private $helper;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    private $storageHelper;

    /**
     * @var \Magento\MediaStorage\Helper\File\Media
     */
    private $mediaHelper;

    /**
     * Collect errors during sync process
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

	private $storageFile;

    private $bucket;

    private $objects = [];

    public function __construct(
        \Beecom\GooglecloudStorage\Helper\Data $helper,
        \Magento\MediaStorage\Helper\File\Media $mediaHelper,
        \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper,
        \Psr\Log\LoggerInterface $logger,
		\Magento\MediaStorage\Model\File\Storage\File $storageFile
    ) {
        parent::__construct();

        $this->helper = $helper;
        $this->mediaHelper = $mediaHelper;
        $this->storageHelper = $storageHelper;
		$this->storageFile = $storageFile;
        $this->logger = $logger;

        $this->client =new StorageClient([
            'projectId' => $this->helper->getProject(),
            'keyFile' => $this->helper->getAccessKey()
        ]);
        $this->bucket = $this->client->bucket($this->helper->getBucket());
    }

    /**
     * Initialisation
     *
     * @return $this
     */
    public function init() {
    
        return $this;
    
    }

    /**
     * Return storage name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStorageName() {
    
        return __('Google Cloud Storage');
    
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function loadByFilename($filename) {
        try {
        	$object_is = $this->bucket->exists( $filename );
        	if( $object_is ) {
        	
				$object = $this->bucket->object( $filename )->downloadToFile($location);
            
            }
            else {
            
                $fail = true;
                
            }
            $contents = file_get_contents( $location );
            if ($object['Body']) {
                $this->setData('id', $filename);
                $this->setData('filename', $filename);
                $this->setData('content', (string) $contents);
            } else {
                $fail = true;
            }
        }
        catch (\Exception $e) {
        
            $fail = true;
        
        }

        if ($fail) {
        
            $this->unsetData();
       
       }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors() {
    
        return !empty($this->errors);
    
    }

    public function clear() {

        return $this;
    
    }

    public function exportDirectories($offset = 0, $count = 100) {
    
        return false;
    
    }

    public function importDirectories(array $dirs = []) {
    
        return $this;
    
    }

    /**
     * Retrieve connection name
     *
     * @return null
     */
    public function getConnectionName() {
    
        return null;
    
    }

    public function exportFiles($offset = 0, $count = 100) {
		
        $files = $this->storageFile->exportFiles($offset,$count);

        return $files;
    }

    public function importFiles( array $files = [] )
    {
        foreach( $files as $file ) {
            try {
				$mediaPath = $file['directory'].'/'.$file['filename'];
                $options = [
                    'name' => $mediaPath
                ];
                $this->object = $this->bucket->upload( fopen($mediaPath, 'r'), $options );
            } 
            catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                $this->logger->critical($e);
            }
        }
        return $this;
    }

    public function saveFile($filename)
    {
        $file = $this->mediaHelper->collectFileInfo($this->getMediaBaseDirectory(), $filename);

        return $this;
    }

    public function fileExists($filename)
    {
         return $this->bucket->object($filename)->exists();
    }

    public function copyFile($oldFilePath, $newFilePath)
    {
        return $this->bucket->object($oldFilePath)->copy($newFilePath);
    }

    public function renameFile($oldFilePath, $newFilePath)
    {
        return $this->bucket->object($oldFilePath)->rename($newFilePath);
    }

    /**
     * Delete file from Amazon S3
     *
     * @param string $path
     * @return $this
     */
    public function deleteFile($filePath)
    {
        return $this->bucket->delete($filePath);
    }

    public function getSubdirectories($path)
    {
        $subdirectories = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

        return $subdirectories;
    }

    public function getDirectoryFiles($path)
    {
        $files = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

        return $files;
    }

    public function deleteDirectory($path)
    {
        $mediaRelativePath = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($mediaRelativePath, '/') . '/';

//         $this->client->deleteMatchingObjects($this->getBucket(), $prefix);

        return $this;
    }

    protected function getBucket()
    {
        return $this->helper->getBucket();
    }

    /**
     * Retrieve media base directory path
     *
     * @return string
     */
    public function getMediaBaseDirectory()
    {
        if (is_null($this->mediaBaseDirectory)) {
            $this->mediaBaseDirectory = $this->storageHelper->getMediaBaseDir();
        }
        return $this->mediaBaseDirectory;
    }
}
