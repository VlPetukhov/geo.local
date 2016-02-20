<?php
/**
 * Created by PhpStorm.
 * User: Владимир
 * Date: 17.02.2016
 * Time: 17:03
 */

namespace app\components\kmlZctaParser;

use yii\base\Exception;
use yii\base\InvalidParamException;

class KmlZctaParser {

    protected $_kmlFileName;
    protected $_parser;
    /** @var  \app\components\kmlZctaParser\ZipGeoData */
    protected $_model;
    protected $_currentAttr;
    protected $_attrFlag; //inner boundary or out boundary

    /**
     * Constructor
     * @param string $kmlFileName
     * @param \app\components\kmlZctaParser\ZipGeoData $model
     */
    public function __construct( $kmlFileName,  \app\components\kmlZctaParser\ZipGeoData $model )
    {
        $this->_model = $model;

        if ( !file_exists($kmlFileName) ) {
            throw new InvalidParamException('KML ' . $kmlFileName . ' file not found.');
        }

        $this->_kmlFileName = $kmlFileName;

        $this->_parser = xml_parser_create('UTF-8');
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, 'openTagHandler', 'closeTagHandler');
        xml_set_character_data_handler($this->_parser, 'characterHandler');
    }

    /**
     * XML Element open tag handler
     */
    public function openTagHandler($parser, $name, $attribs)
    {
        $name = strtolower($name);

        if ( 'placemark' === $name ) {
            $this->_model->wipe();
            return;
        }

        if ( 'polygon' === $name ) {
            $this->_model->addNewPolygon();
            return;
        }

        if ( 'outerboundaryis' === $name ) {
            $this->_attrFlag = 'outer';
            return;
        }


        if ( 'innerboundaryis' === $name ) {
            $this->_attrFlag = 'inner';
            return;
        }

        if ( 'simpledata' === $name &&
             array_key_exists( 'NAME', $attribs) &&
            'ZCTA5CE10' === $attribs['NAME']
        ) {
            $this->_currentAttr = 'zipCode';
            return;
        }

        if ( 'coordinates' === $name ) {

            if ( 'outer' === $this->_attrFlag ) {
                $this->_currentAttr = 'outerBoundary';
            }

            if ( 'inner' === $this->_attrFlag ) {
                $this->_currentAttr = 'innerBoundary';
            }

            return;
        }
    }

    /**
     * XML Element close tag handler
     */
    public function closeTagHandler($parser, $name)
    {
        $name = strtolower($name);

        if ( 'placemark' === $name ) {
            $this->_model->save();
            return;
        }

        if ( 'outerboundaryis' === $name ) {
            $this->_attrFlag = null;
            return;
        }


        if ( 'innerboundaryis' === $name ) {
            $this->_attrFlag = null;
            return;
        }

        if ( 'simpledata' === $name ) {
            $this->_currentAttr = null;
        }

        if ( 'coordinates' === $name ) {
            $this->_currentAttr = null;
        }

    }

    public function characterHandler( $parser , $data)
    {
        if ( isset( $this->_currentAttr ) ) {
            $this->_model->{$this->_currentAttr} = $data;
            $this->_currentAttr = null;
        }
    }

    /**
     * Reads data from the file and parse them
     */
    public function parse()
    {
        $fileHandler = fopen($this->_kmlFileName, 'r');

        if ( !$fileHandler ) {
            throw new Exception("Can't open file: {$this->_kmlFileName}.");
        }

        $count = 10;

        while ( ! feof( $fileHandler ) ) {
            $buffer = fread($fileHandler, 32768); //32k buffer
            xml_parse($this->_parser, $buffer, feof($fileHandler));
            if ( 0 > $count--) {
                break;
            }
        }

        fclose( $fileHandler);
    }
} 