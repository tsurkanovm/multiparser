<?php
/**
* Created by PhpStorm.
 * User: Cibermag
* Date: 31.08.2015
* Time: 12:50
*/

namespace yii\multiparser;
use common\components\CustomVarDamp;
use yii\base\Behavior;
use yii\base\ErrorException;

// класс который содержит преобразователи значений (фильтры) используемые при парсинге
class Converter extends Behavior
{

    const METHOD_PREFIX = 'convertTo';

    //public  $configuration = [];

    public static function convertToFloat($value)
    {
        if ($value == '') {
            $value = 0;
        }
        $value = trim(str_replace(",", ".", $value));
        $value = preg_replace("/[^0-9.]+/", "", strtoupper($value));

        if ($value == '') {
            return '';
        }
        $value = round( (float)$value, 2 );

        return $value;
    }

    public static function convertToInteger($value)
    {
        if ($value == '') {
            $value = 0;
        }
        $value = trim(str_replace(",", ".", $value));
        $value = preg_replace("/[^0-9.]+/", "", strtoupper($value));
        if ($value == '') {
            return '';
        }
        $value = round((int)$value, 2);

        return $value;
    }

    public static function convertToEncode($value)
    {
        $res = $value;
        if (is_array($value)) {

            $res = Encoder::encodeArray($value);

        }elseif ( is_string($value) ) {

            $res = Encoder::encodeString($value);

        }
        return $res;
    }


    /**
     * @param $name - имя метода конвертации
     * @param $value - значение на конвертацию
     * @return mixed
     */
    public static function __callStatic( $name, $value )
    {
        $method_name =  self::METHOD_PREFIX . $name;

        if ( method_exists( static::class, $method_name ) ) {
            return static::$method_name( $value[0] );

        } else{
            // если такого метода конвертации не предусмотрено, то возвращаем не конвертируя
            return $value[0];

        }
    }

    public function __call($name, $params)
    {
        return self::__callStatic( $name, $params );
    }


    /**
     * @param $arr - массив для конвертирования
     * @param $configuration - массив конфигурация конвертирования
     * @return mixed
     * конвертирует массив по полученным настройкам, вызывая последовательно функции конвертации (указанные в конфигурации)
     */
    public static function convertByConfiguration( $arr, $configuration  )
    {
        if( $hasKey = isset( $configuration['hasKey'] ) )
            unset( $configuration['hasKey'] );

        if ( isset( $configuration['configuration'] ) ) {
            $arr_config = $configuration['configuration'];
            unset( $configuration['configuration'] );
        } else{
            throw new ErrorException('Не указан обязательный параметр конфигурационного файла - converter_conf[configuration]');
        }

            // проставим аттрибуды из конфига{}{}
        foreach ($configuration as $key_setting => $setting) {
            if( property_exists( static::class, $key_setting ) )
                static::$$key_setting = $setting;
        }

        foreach ( $arr_config as $key => $value ) {
            if ( $hasKey ){
                //  у нас ассоциативный массив, и мы можем конвертировать каждое значение в отдельности
                if ( is_array( $value ) ) {
                    //если пустой массив то конвертируем всю строку
                    if (count( $value ) === 0 ){

                        $arr = self::$key( $arr );
                        continue;
                    }
                    // иначе конвертируем каждую ячейку в отдельности
                    foreach ($value as $sub_value) {
                        if (isset($arr[$sub_value])) {
                            // конвертируем только те ячейки которые сопоставлены в прочитанном массиве с колонками в конфигурационном файле
                            $arr[$sub_value] = self::$key( $arr[$sub_value] );
                        }

                    }
                } else {

                    if (isset($arr[$value])) {
                        // конвертируем только те ячейки которые сопоставлены в прочитанном массиве с колонками в конфигурационном файле
                        $arr[$value] = self::$key( $arr[$value] );
                    //    CustomVarDamp::dump($result);
                    }

                }

            } else {
                // нет заголовка - мы можем конвертировать только строку в целом
                $arr = self::$key( $arr );
            }

        }

        return $arr;
    }



}