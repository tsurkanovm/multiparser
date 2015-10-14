<?php
/**

 */
namespace yii\multiparser;
use common\components\CustomVarDamp;


/**
 * Class CsvParser
 * @package yii\multiparser
 */
class CsvParser implements ParserInterface
{


    /** @var bool
    имеет ли файл заголовок который будет установлен ключами возвращемого массива*/
    public $hasHeaderRow = false;
    /** @var array - массив с заголовком,
     * если не указан и установлено свойство $hasHeaderRow - будет определен автоматически */
    public $keys;

    /** @var экземляр SplFileObject читаемого файла */
    public $file;

    /** @var int - первая строка с которой начинать парсить */
    public $first_line = 0;

    /** @var int - последняя строка до которой  парсить
     * если не указана, то парсинг происходит до конца файла*/
    public $last_line = 0;

    /** @var int - первая колонка файла с которой начнется парсинг */
    public $first_column = 0;

    /** @var string - разделитель csv */
    public $delimiter = ';';

    /** @var bool
    нужно ли искать автоматически первоую значисмую строку (не пустая строка)
     * иначе первая строка будет взята из аттрибута $first_line */
    public $auto_detect_first_line = false;

    /** @var int - количество значимых колонок, что бы определить первую значимую строку
     * используется при автоопределении первой строки*/
    public $min_column_quantity = 5;

    /** @var array - конфигурация конвертера значений */
    public $converter_conf = [];
    /** @var array - конвертер созданный по конфигурации */
    public $converter = NULL;
    /** @var int - текущая строка */
    private $current_line = 0;


    /**
     * метод устанвливает нужные настройки объекта SplFileObject, для работы с csv
     */
    public function setup()
    {

        $this->file->setCsvControl($this->delimiter);
        $this->file->setFlags(\SplFileObject::READ_CSV);
        $this->file->setFlags(\SplFileObject::SKIP_EMPTY);

        if ($this->auto_detect_first_line) {
            $this->shiftToFirstValuableLine();
        }
        $this->setupConverter();

    }

    /**
     * устанавливает конвертер значений согласно конфигурационным настройкам
     */
    public function setupConverter()
    {
        if (!count($this->converter_conf)) {
            $this->converter = new Converter();
            if ($this->hasHeaderRow) {
                // если у файла есть заголовок, то в результате имеем ассоциативный массив
                $this->converter_conf['hasKey'] = 1;
            }
            //$this->converter->configuration = $this->converter_conf;

        }
    }


    /**
     * определяет первую значимую строку,
     * считывается файл пока в нем не встретится строка с непустыми колонками
     * в количестве указанном в атрибуте min_column_quantity
     * в результате выполнения курсор ресурса будет находится на последней незначимой строке
     */
    protected function shiftToFirstValuableLine()
    {

        $finish = false;
        while (!$finish ) {
            $this->current_line ++;

            $j = 0;
            $row = $this->file->fgetcsv();;
            if ($row === false) {
                continue;
            }

            for ($i = 1; $i <= count($row); $i++) {
           //     CustomVarDamp::dump($row[$i]);

                if ($row[$i - 1] <> '') {
                    $j++;
                }

                if ($j >= $this->min_column_quantity) {
                    break 2;
                }
            }
        }
        // @todo - сделать опционально
        // код для того что бы парсить первую строку, закомментировано как предполагается что первая значимая строка это заголовок
 //       $this->current_line --;
//        $this->file->seek( $this->current_line );
    }

    /**
     * @return array - итоговый двумерный массив с результатом парсинга
     * метод считывает с открытого файла данные построчно
     */
    public function read()
    {

        $return = [];

        // будем считать количество пустых строк подряд - при трех подряд - считаем что это конец файла и выходим
        $empty_lines = 0;
        while ( $empty_lines < 3 ) {
            // прочтем строку из файла. Если там есть значения - то в ней массив, иначе - false
            $row = $this->readRow(  );

            if ($row === false) {
                //счетчик пустых строк
                $empty_lines++;
                continue;
            }
            // строка не пустая, имеем прочитанный массив значений
            $this->current_line++;
            if ($this->hasHeaderRow) {
                // в файле есть заголовок, но он еще не назначен, назначим
                if ($this->keys === NULL) {
                    $this->keys = array_values($row);
                }
            }
            // если у нас установлен лимит, при  его достижении прекращаем парсинг
            if (($this->last_line) && ($this->current_line > $this->last_line)) {
                break;
            }
            // обнуляем счетчик, так как считаюся пустые строки ПОДРЯД
            $empty_lines = 0;

            $return[] = $row;
        }

        $this->closeHandler();
        return $return;
    }


    protected function closeHandler()
    {
        $this->file = NULL;
    }

    /**
     * @return array - одномерный массив результата парсинга строки
     */
    protected function readRow(  )
    {
        $row = $this->file->fgetcsv();
        // уберем нулевые колонки
        $row = array_filter($row, function($val){
            return $val <> '';
        });
        if (is_array($row)) {
            // если есть заголовок, то перед конвертацией его нужно назначить
            if ($this->hasHeaderRow && $this->keys !== NULL) {

                if (count($this->keys) !== count($row)) {
                    throw new \ErrorException("Ошибка парсинга файла в строке # {$this->current_line}. Не соответсвие числа ключевых колонок (заголовка) - числу колонок с данными", 0, 1, $this->file->getBasename(), $this->current_line);
                }

                $row = array_combine($this->keys, $row);
            }
            // попытаемся конвертировать прочитанные значения согласно конфигурации котнвертера значений
            $row = $this->convert($row);
            // обрежем массив к первой значимой колонке
            if ( $this->first_column ) {

                $row = array_slice($row, $this->first_column);

            }
        }
        if (is_null($row))
            $row = false;

        return $row;

    }

    /**
     * @param $arr
     * @return mixed
     * преобразовует значения прочитанного массива в нужные типы, согласно конфигурации конвертера
     */
    protected function convert($arr)
    {
        $result = $arr;
        $converter = $this->converter;

        if (!is_null($converter)) {

            $result = $converter->convertByConfiguration( $arr, $this->converter_conf );

        }

        return $result;

    }


}