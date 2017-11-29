<?php
namespace Beecom\GooglecloudStorage\Console\Command;

use Google\Cloud\Storage\StorageClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class StorageSyncCommand extends \Symfony\Component\Console\Command\Command
{
    private $configFactory;

    private $state;

    private $helper;

    private $client;

    private $bucket;

    private $coreFileStorage;

    private $storageHelper;

    private $filesystem;

    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Beecom\GooglecloudStorage\Helper\Data $helper,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->coreFileStorage = $coreFileStorage;
        $this->helper = $helper;
        $this->storageHelper = $storageHelper;
        $this->filesystem = $filesystem;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcs:storage:sync')
            ->setDescription('Sync all of your media files over to GCS.')
            ->setDefinition($this->getOptionsList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = $this->validate($input);
        if ($errors) {
            $output->writeln('<error>' . implode('</error>' . PHP_EOL .  '<error>', $errors) . '</error>');
            return;
        }

        try {
            $this->client =new StorageClient([
                'projectId' => $this->helper->getProject(),
                'keyFile' => $this->helper->getAccessKey()
            ]);
            $this->bucket = $this->client->bucket($this->helper->getBucket());
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return;
        }
        if (!$this->bucket->exists()) {
            $output->writeln('<error>The GCS credentials you provided did not work. Please review your details and try again. You can do so using our config script.</error>');
            return;
        }

        $output->writeln(sprintf('Uploading files to use GCS.'));
        if ($this->coreFileStorage->getCurrentStorageCode() == \Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage::STORAGE_MEDIA_FILE_SYSTEM) {

            try {

                $this->uploadDirectory(
                    $this->storageHelper->getMediaBaseDir(),
                    false,
                    "publicRead"
                );

            }
            catch (\Exception $e) {

                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            }

        }
        else {

            $sourceModel = $this->coreFileStorage->getStorageModel();
            $destinationModel = $this->coreFileStorage->getStorageModel(\Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage::STORAGE_MEDIA_GCS);
            $offset = 0;
            while (($files = $sourceModel->exportFiles($offset, 1)) !== false) {

                foreach ($files as $file) {

                    $output->writeln(sprintf('Uploading %s to use GCS.', $file['directory'] . '/' . $file['filename']));

                }
                $destinationModel->importFiles($files);
                $offset += count($files);

            }

        }
        $output->writeln(sprintf('Finished uploading files to use GCS.'));

        if ($input->getOption('enable')) {
            $output->writeln('Updating configuration to use GCS.');
            $this->state->setAreaCode('adminhtml');
            $config = $this->configFactory->create();
            $config->setDataByPath('system/media_storage_configuration/media_storage', \Beecom\GooglecloudStorage\Model\MediaStorage\File\Storage::STORAGE_MEDIA_GCS);
            $config->save();
            $output->writeln(sprintf('<info>Magento now uses GCS for its file backend storage.</info>'));
        }
    }

    public function getOptionsList()
    {
        return [
            new InputOption('enable', null, InputOption::VALUE_NONE, 'use GCS as Magento file storage backend'),
        ];
    }

    public function validate(InputInterface $input)
    {
        $errors = [];

        if (is_null($this->helper->getAccessKey())) {
            $errors[] = 'You have not provided an GCS access key ID. You can do so using our config script.';
        }
        if (is_null($this->helper->getProject())) {
            $errors[] = 'You have not provided an GCS Project Number. You can do so using our config script.';
        }
        if (is_null($this->helper->getBucket())) {
            $errors[] = 'You have not provided an GCS bucket. You can do so using our config script.';
        }
        if (is_null($this->helper->getRegion())) {
            $errors[] = 'You have not provided an GCS region. You can do so using our config script.';
        }

        return $errors;
    }

    public function uploadDirectory($source, $use_validation = false, $permissions = "private", $recursive = true){
        $result = null;
        if( is_dir( $source ) ) {

            $recursiveIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($recursiveIterator as $fileItem) {
                /** @var $fileItem \SplFileInfo */
                if ($recursive && $fileItem->isDir() && strpos($fileItem->getBasename(),"cache") === false) {
                    $this->uploadDirectory( $fileItem->getRealPath() );
                }
                else {
                    try {
                        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                        $relativePath = $mediaDirectory->getRelativePath($fileItem->getRealPath());
                        $options = [
                            'name' => $relativePath
                        ];
                        $this->object = $this->bucket->upload( fopen($fileItem->getRealPath(), 'r'), $options );
                    }
                    catch( \Exception $e ) {
                        print $e;
                    }
                }

            }

        }
        else {

            $result = 'This is not a directory.';
            $this->errors[$this->error_count] = $e->getServiceException()->getMessage();
            $this->error_count++;

        }
        return $result;
    }
}
