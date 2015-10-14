<?php

namespace yii\multiparser;

use Yii;

class ParserHandler
{

    const DEFAULT_MODE = 'web';
    /** @var string */
    protected $filePath;

    /** @var string */
    protected $configuration;

    /** @var instance of SplFileObject */
    protected $fileObject;

    /** @var string - extension of file $filePath */
    protected $extension;

    /** @var string - extension of file $filePath */
    protected $mode;

    /** @var string - extension of file $filePath */
    protected $options;

    /**
     * @param string first line in file for parsing
     */
    public function __construct($filePath,  $options = [])
    {
        $this->filePath = $filePath;
        if (isset($options['mode'])) {

            $this->mode = $options['mode'];
            unset($options['mode']);

        } else {

            $this->mode = self::DEFAULT_MODE;

        }

        $this->options = $options;

        try {
            $this->fileObject = new \SplFileObject($this->filePath, 'r');
        } catch (\ErrorException $e) {
            //  Yii::warning("Ошибка открытия файла {$this->filePath}");
            echo "Ошибка открытия файла {$this->filePath}";
            return [];
        }

        $options['file'] = $this->fileObject;
        $this->extension = $this->fileObject->getExtension();

        try {
            $this->configuration = ParserConfigurator::getConfiguration($this->extension, $this->mode);
            $this->configuration = array_merge($this->configuration, $options);

        } catch (\ErrorException $e) {
            echo $e->getMessage();
            return [];
        }

    }

    public function run()
    {

        $result = [];
        // @todo - rewrite to universal manner
       // \common\components\CustomVarDamp::dumpAndDie($this);
        if (count($this->configuration)) {
            $parser = Yii::createObject($this->configuration);

            try {

                $parser->setup();
                $result = $parser->read();

            } catch (\ErrorException $e) {

                echo $e->getMessage();

            }

        }

        return $result;
    }
}

