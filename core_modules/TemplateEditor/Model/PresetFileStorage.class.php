<?php
/**
 * @copyright   Comvation AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     contrexx
 */

namespace Cx\Core_Modules\TemplateEditor\Model;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\ParserException;
use Symfony\Component\Yaml\Yaml;

class PresetFileStorage implements Storable
{
    /**
     * @var String
     */
    protected $path;


    /**
     * @param String $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param String $name
     *
     * @return array
     * @throws ParserException
     * @throws PresetRepositoryException
     */
    public function retrieve($name)
    {
        if (!file_exists(
            \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()
                ->getFilePath(
                    $this->path . '/options/presets/' . $name . '.yml'
                )
            )
        ) {
            throw new PresetRepositoryException(
                'Preset ' . $name . ' not found.'
            );
        }
        $file = file_get_contents(
            \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()
                ->getFilePath(
                    $this->path . '/options/presets/' . $name . '.yml'
                )
        );
        if ($file) {
            try {
                $yaml = new Parser();
                return $yaml->parse($file);
            } catch (ParserException $e) {
                preg_match(
                    "/line (?P<line>[0-9]+)/", $e->getMessage(), $matches
                );
                throw new ParserException($e->getMessage(), $matches['line']);
            }
        } else {
            throw new ParserException("File not found");
        }
    }

    /**
     * @param                  $name
     * @param YamlSerializable $data
     *
     * @return bool
     */
    public function persist($name, YamlSerializable $data)
    {
        mkdir($this->path);
        mkdir($this->path . '/options/');
        mkdir($this->path . '/options/presets');
        return file_put_contents(
            $this->path . '/options/presets/' . $name . '.yml',
            Yaml::dump($data->yamlSerialize(), 5)
        );
    }

    /**
     * @return array
     */
    public function getList()
    {
        $list = $this->getPresetList($this->path);
        $list = $this->mergePreset(
            $list,
            $this->getPresetList(
                \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getCodeBaseThemesPath() . '/' . array_reverse(
                    explode('/', $this->path)
                )[0]
            )
        );
        // Move Default to first place
        $key = array_search('Default', $list);
        $new_value = $list[$key];
        unset($list[$key]);
        array_unshift($list, $new_value);
        return $list;
    }

    public function getPresetList($path)
    {
        return array_filter(
            array_filter(glob($path . '/options/presets/*'), 'is_file'),
            function (&$name) {
                return $name = pathinfo($name, PATHINFO_FILENAME);
            }
        );
    }

    /**
     * @param $name
     */
    public function remove($name)
    {
        \Cx\Lib\FileSystem\FileSystem::delete_file(
            $this->path . '/options/presets/' . $name . '.yml'
        );
    }

    /**
     * @param $list
     * @param $getPresetList
     *
     * @return array
     */
    private function mergePreset($list, $getPresetList)
    {
        $finalArray = $getPresetList;
        foreach ($list as $entry) {
            if (!in_array($entry, $getPresetList)) {
                $finalArray[] = $entry;
            }
        }
        return $finalArray;
    }
}

class PresetRepositoryException extends \Exception
{

}