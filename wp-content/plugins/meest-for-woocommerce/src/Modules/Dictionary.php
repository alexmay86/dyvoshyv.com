<?php

namespace MeestShipping\Modules;

use Exception;
use MeestShipping\Models\{Branch, District, Region, City, Street};
use ZipArchive;

class Dictionary implements \MeestShipping\Contracts\Module
{
    const MAX_ROWS = 100;

    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function init()
    {
        ini_set('memory_limit','-1');
        ini_set('max_execution_time', '0');

        $upload_dir = wp_upload_dir();
        $uploads_path = $upload_dir['basedir'];
        $dictionaryDir = $uploads_path . DIRECTORY_SEPARATOR . 'meest_for-woocommerce';
        $dictionaryPath = $dictionaryDir . DIRECTORY_SEPARATOR . 'dictionary.zip';

        if (!file_exists($dictionaryDir) && !wp_mkdir_p($dictionaryDir)) {
            error_log('Failed to create directory: ' . $dictionaryDir);

            throw new Exception('Failed to create directory: ' . $dictionaryDir);
        }

        $this->download($dictionaryPath, $dictionaryDir);

        foreach ($this->options['dictionary']['files'] as $dictionary => $fileName) {
            $filePath = $dictionaryDir . DIRECTORY_SEPARATOR . $dictionary.'.csv';
            if (file_exists($filePath)) {
                $this->parse($filePath, $dictionary);
            }
            unlink($filePath);
        }

        return true;
    }

    private function download(string $dictionaryPath, string $dictionaryDir)
    {
        $dictionaryData = file_get_contents($this->options['dictionary_url']);
        if ($dictionaryData === false) {
            error_log('Failed to load url: ' . $this->options['dictionary_url']);

            throw new Exception('Failed to load url: ' . $this->options['dictionary_url']);
        }

        file_put_contents($dictionaryPath, $dictionaryData);

        $zip = new ZipArchive();
        if ($zip->open($dictionaryPath) === true) {
            foreach ($this->options['dictionary']['files'] as $dictionary => $fileName) {
                $fileContent = $zip->getFromName($fileName);
                file_put_contents($dictionaryDir . DS . "$dictionary.csv" , $fileContent);
            }
            $zip->close();
        } else {
            error_log('Failed to open or extract the zip file.');

            throw new Exception('Failed to open or extract the zip file.');
        }
    }

    private function parse(string $filePath, string $type)
    {
        $countryUuid = $this->options['country_id']['ua'];
        $class = null;
        $columns = [];
        $values = [];

        switch ($type) {
            case 'region':
                $class = Region::class;
                $columns = ['region_uuid', 'country_uuid', 'name_uk', 'name_ru'];
                $values = function ($data) use ($countryUuid) {
                    return [$data[0], $countryUuid, self::decode($data[1]), self::decode($data[2])];
                };
                break;
            case 'district':
                $class = District::class;
                $columns = ['district_uuid', 'region_uuid', 'name_uk', 'name_ru'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[1], ['---', '*', '***']) ? [] : [$data[0], $data[3], self::decode($data[1]), self::decode($data[2])];
                };
                break;
            case 'city':
                $class = City::class;
                $columns = ['city_uuid', 'district_uuid', 'region_uuid', 'country_uuid', 'type_id', 'name_uk', 'name_ru', 'delivery_zone'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[1], ['---', '*', '***']) ? [] : [$data[0], $data[4], $data[5], $countryUuid, 1, self::decode($data[1]?: $data[2]), self::decode($data[2]?: $data[1]), $data[7]];
                };
                break;
            case 'street':
                $class = Street::class;
                $columns = ['street_uuid', 'city_uuid', 'type_id', 'postcode', 'name_uk', 'name_ru', 'type_uk', 'type_ru'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[3], ['---', '*', '***']) ? [] : [$data[0], $data[5], 1, $data[12] ?? null, self::decode($data[3] ?: $data[4]), self::decode($data[4] ?: $data[3]), self::decode($data[1] ?: $data[2]), self::decode($data[2] ?: $data[1])];
                };
                break;
            case 'branch':
                $class = Branch::class;
                $columns = ['branch_uuid', 'city_uuid', 'name_uk', 'description_uk'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[1], ['---', '*', '***']) ? [] : [$data[0], $data[3], self::decode($data[1] ?: $data[2]), self::decode($data[2]?: $data[1])];
                };
                break;
        }

        if ($class !== null) {
            self::insert($filePath, $class, $columns, $values);
        }
    }

    private static function insert(string $filePath, string $class, array $columns, \Closure $values)
    {
        if (false !== $handle = fopen($filePath, 'r')) {
            $class::truncate();

            $rowNumber = 0;
            $rows = [];
            while (false !== $data = fgetcsv($handle, 1000, ';')) {
                $data = $values($data);
                if (empty($data)) {
                    continue;
                }

                $rowNumber++;
                $rows[] = $data;

                if ($rowNumber === self::MAX_ROWS) {
                    $class::insert($columns, $rows);
                    $rowNumber = 0;
                    $rows = [];
                }
            }

            if (!empty($rows)) {
                $class::insert($columns, $rows);
            }

            fclose($handle);
        }
    }

    private static function decode(string $str): string
    {
        return mb_convert_encoding($str, 'UTF-8', 'Windows-1251');
    }
}
