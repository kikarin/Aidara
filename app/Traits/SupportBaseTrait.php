<?php

namespace App\Traits;

trait SupportBaseTrait
{
    public function getControllerName()
    {
        $class     = get_class($this);
        $className = (new \ReflectionClass($class))->getShortName();
        $className = str_replace('Controller', '', $className);
        $className = $this->convertToDashSeparated($className);

        return $className;
    }

    // Method to convert controller name to "Title" format
    public function convertToTitle($string)
    {
        // Ubah huruf kapital pertama setiap kata menjadi huruf besar
        return ucwords(str_replace(['-', '_'], ' ', $string));
    }

    public function convertToDashSeparated($string)
    {
        // Ubah ke huruf kecil semua dan sisipkan tanda hubung ("-") di antara kata-kata
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $string));
    }

    public function convertToUnderscoreSeparated($string)
    {
        // Ubah ke huruf kecil semua dan sisipkan garis bawah ("_") di antara kata-kata
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $string));
    }
}
