<?php
/**
 * @class KmlParseController
 * @namespace app\commands
 */

namespace app\commands;

use app\components\kmlZctaParser\KmlZctaParserSXML;
use app\components\kmlZctaParser\ZipGeoData;
use Yii;
use yii\console\Controller;


class KmlParseController extends Controller
{
    /**
     * This command parses KML File
     */
    public function actionIndex( $fileName )
    {
        //$fileName = realpath( Yii::getAlias('@app/GEOdata') . '/cb_2014_us_zcta510_500k.kml');
        $fileName = realpath( $fileName );

        if ( !file_exists($fileName) ) {
            echo "File {$fileName} not found!\n";
            echo "Command syntax: kml-parse <fileName>,\n";
            echo "where <fileName> - parsing file path\n";
            return;
        }


        $parser = new KmlZctaParserSXML($fileName, new ZipGeoData());
        $parser->parse();
    }
}