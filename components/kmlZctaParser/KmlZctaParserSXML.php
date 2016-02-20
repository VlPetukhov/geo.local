<?php
/**
 * Created by PhpStorm.
 * User: Владимир
 * Date: 18.02.2016
 * Time: 15:56
 */

namespace app\components\kmlZctaParser;


use Yii;
use yii\base\InvalidParamException;

class KmlZctaParserSXML {

    protected $_xmlFileName;
    /** @var  \app\components\kmlZctaParser\ZipGeoData */
    protected $_model;

    /**
     * Constructor
     * @param string $kmlFileName
     * @param \app\components\kmlZctaParser\ZipGeoData $model
     */
    public function __construct( $kmlFileName,  \app\components\kmlZctaParser\ZipGeoData $model )
    {
        ini_set('max_execution_time', 28800); //8hrs

        $this->_model = $model;

        if ( !file_exists($kmlFileName) ) {
            throw new InvalidParamException('KML ' . $kmlFileName . ' file not found.');
        }

        $this->_xmlFileName = $kmlFileName;
    }

    /**
     * Kml file processing
     */
    public function parse()
    {
        $xml = simplexml_load_file($this->_xmlFileName);

        $polygonCoordTableName = PolygonCoord::tableName();
        $polygonTableName = Polygon::tableName();
        $sql = "SELECT zip FROM {$polygonTableName}";
        $passedZips = array_map('intval', Yii::$app->db->createCommand($sql)->queryColumn());

        $sql = "ALTER TABLE {$polygonTableName} DISABLE KEYS";
        Yii::$app->db->createCommand($sql)->execute();

        $sql = "ALTER TABLE {$polygonCoordTableName} DISABLE KEYS";
        Yii::$app->db->createCommand($sql)->execute();

        foreach ($xml->Document->Folder->Placemark as $place) {
            $zip = (int)$place->ExtendedData->SchemaData->SimpleData[0];

            if ( in_array( $zip , $passedZips ) ) {
                echo "Zip: {$zip} was already processed. Skipped!\n";
                continue;
            }

            $this->_model->zipCode = $zip;

            if ( isset($place->MultiGeometry) ) {
                foreach ($place->MultiGeometry->Polygon as $polygon) {
                    $this->_model->addNewPolygon($polygon->outerBoundaryIs->LinearRing->coordinates);
                }
            } else {
                $this->_model->addNewPolygon($place->Polygon->outerBoundaryIs->LinearRing->coordinates);
            }

            $this->_model->save();
        }

        $sql = "ALTER TABLE {$polygonCoordTableName} ENABLE KEYS";
        Yii::$app->db->createCommand($sql)->execute();


        $sql = "ALTER TABLE {$polygonTableName} ENABLE KEYS";
        Yii::$app->db->createCommand($sql)->execute();

        echo "All done.\n";
    }
} 