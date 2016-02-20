<?php
/**
 * @class FunctionController
 * @namespace app\controllers
 */

namespace app\controllers;


use app\components\kmlZctaParser\Polygon;
use Yii;
use yii\web\Controller;

class FunctionController extends Controller {

    /**
     * @param string $lat
     * @param string $lon
     */
    public function actionGetZip( $lat, $lon )
   {
       $timeMark = microtime();

       $foundZips = Polygon::getZipByLocation( $lat, $lon );

       $timeMark = microtime() - $timeMark;

       if ( !empty($foundZips) ) {
           $zipStr = implode(', ', $foundZips);
           echo "Found ZIP codes: {$zipStr}.";
       } else {
           echo "Nothing was found.";
       }

       echo " (Search time: {$timeMark} seconds)";
   }
} 