<?php

if(!function_exists('array_count_before_key'))
{
    /**
     * Counts items before given key.
     *
     * @param $array
     * @param $key
     * @return int
     */
    function array_count_before_key(&$array, $key)
    {
        $count = 0;

        foreach($array as $index => $item)
        {
            if($index < $key)
            {
                $count++;
            }
            else
            {
                return $count;
            }
        }

        return $count;
    }
}