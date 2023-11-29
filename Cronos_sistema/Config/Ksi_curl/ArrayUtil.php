<?php declare(strict_types=1);

namespace Cronos_sistema\Config\Ksi_curl;

use Cronos_sistema\Config\Ksi_curl\CaseInsensitiveArray;

class ArrayUtil
{
    /**
     * É Array Assoc
     *
     * @acesso público
     * @param $array
     *
     * @return booleano
     */
    public static function isArrayAssoc($array)
    {
        return (
            $array instanceof CaseInsensitiveArray ||
            (bool)count(array_filter(array_keys($array), 'is_string'))
        );
    }

    /**
     * É Array Assoc
     *
     * @deprecated Use ArrayUtil::isArrayAssoc().
     * @acesso público
     * @param $array
     *
     * @return booleano
     */
    public static function is_array_assoc($array)
    {
        return static::isArrayAssoc($array);
    }

    /**
     * É Array Multidim
     *
     * @acesso público
     * @param $array
     *
     * @return booleano
     */
    public static function isArrayMultidim($array)
    {
        if (!is_array($array)) {
            return false;
        }

        return (bool)count(array_filter($array, 'is_array'));
    }

    /**
     * É Array Multidim
     *
     * @deprecated Use ArrayUtil::isArrayMultidim().
     * @acesso público
     * @param $array
     *
     * @return booleano
     */
    public static function is_array_multidim($array)
    {
        return static::isArrayMultidim($array);
    }

    /**
     * Array Flatten Multidim
     *
     * @acesso público
     * @param $array
     * @param $prefixo
     *
     * matriz @return
     */
    public static function arrayFlattenMultidim($array, $prefix = false)
    {
        $return = [];
        if (is_array($array) || is_object($array)) {
            if (empty($array)) {
                $return[$prefix] = '';
            } else {
                foreach ($array as $key => $value) {
                    if (is_scalar($value)) {
                        if ($prefix) {
                            $return[$prefix . '[' . $key . ']'] = $value;
                        } else {
                            $return[$key] = $value;
                        }
                    } else {
                        if ($value instanceof \CURLFile) {
                            $return[$key] = $value;
                        } else {
                            $return = array_merge(
                                $return,
                                self::arrayFlattenMultidim(
                                    $value,
                                    $prefix ? $prefix . '[' . $key . ']' : $key
                                )
                            );
                        }
                    }
                }
            }
        } elseif ($array === null) {
            $return[$prefix] = $array;
        }
        return $return;
    }

    /**
     * Array Flatten Multidim
     *
     * @deprecated Use ArrayUtil::arrayFlattenMultidim().
     * @acesso público
     * @param $array
     * @param $prefixo
     *
     * matriz @return
     */
    public static function array_flatten_multidim($array, $prefix = false)
    {
        return static::arrayFlattenMultidim($array, $prefix);
    }

   /**
     * Array aleatória
     *
     * @acesso público
     * @param $array
     *
     * @return misto
     */
    public static function arrayRandom($array)
    {
        return $array[static::arrayRandomIndex($array)];
    }

    /**
     * Array Índice Aleatório
     *
     * @acesso público
     * @param $array
     *
     * @return inteiro
     */
    public static function arrayRandomIndex($array)
    {
        return mt_rand(0, count($array) - 1);
    }

    /**
     * Array aleatória
     *
     * @deprecated Use ArrayUtil::arrayRandom().
     * @acesso público
     * @param $array
     *
     * @return misto
     */
    public static function array_random($array)
    {
        return static::arrayRandom($array);
    }
}