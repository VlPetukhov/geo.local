<?php
/**
 * Created by PhpStorm.
 * User: Владимир
 * Date: 17.02.2016
 * Time: 18:10
 */

namespace app\components\kmlZctaParser;


use Yii;

class ZipGeoData {

    public $zipCode;
    protected $geoArea = [];


    /**
     * Add new polygon to Geo-area
     */
    public function addNewPolygon( $data )
    {
            $this->geoArea[] = $this->coordProcess($data);
    }

    /**
     * Process comma separated coordinates string to coordinates array
     * @param $data
     * @return array
     */
    protected function coordProcess( $data )
    {
        if ( false === strpos($data , ',') ) {
            return $data;
        }

        $dataArray = explode(' ', $data);

        $result = [];

        foreach ($dataArray as $coord ) {
            if (false === strpos($coord, ',') ) {
                continue;
            }

            $coord = explode(',', $coord);

            $buffer = [];
            if ( isset($coord[0], $coord[1]) ) {
                $buffer['lat'] = $coord[0];
                $buffer['lon'] = $coord[1];

                $result[] = $buffer;
            }
        }

        return $result;
    }

    /**
     * Procedure that saves data via AR models.
     * @throws \yii\db\Exception
     */
    protected function saveWithAr()
    {
        $transaction = Yii::$app->db->beginTransaction();

        foreach ( $this->geoArea as $areaPolygon ) {

            $polygon = new Polygon(['scenario' => 'create']);
            $polygon->zip = $this->zipCode;
            $polygon->type = 'outer';

            if ( ! $polygon->save() ) {
                $transaction->rollBack();
                echo "Zip code: {$this->zipCode} FAILED! Can't save polygon.\n";

                return;
            }

            $firstRecord = true;

            foreach ($areaPolygon as $point) {

                $lat = $point['lat'];
                $lon = $point['lon'];

                //Initial values
                if ( $firstRecord ) {
                    $polygon->minLat = $lat;
                    $polygon->maxLat = $lat;

                    $polygon->minLon = $lon;
                    $polygon->maxLon = $lon;

                    $firstRecord = false;
                }

                if ( $polygon->maxLat < $lat ) {
                     $polygon->maxLat = $lat;
                }

                if ( $polygon->minLat >= $lat ) {
                     $polygon->minLat = $lat;
                }


                if ( $polygon->maxLon < $lon ) {
                     $polygon->maxLon = $lon;
                }

                if ( $polygon->minLon >= $lon ) {
                     $polygon->minLon = $lon;
                }

                $pointCoords = new PolygonCoord(['scenario' => 'create']);
                $pointCoords->polygon_id = $polygon->id;
                $pointCoords->lat = $lat;
                $pointCoords->lon = $lon;

                if ( ! $pointCoords->save() ) {
                    $transaction->rollBack();
                    echo "Zip code: {$this->zipCode} FAILED! Can't save point.\n";

                    return;
                }
            }

            $polygon->save();
        }

        $transaction->commit();
        echo "Zip code: {$this->zipCode} saved.\n";
    }

    /**
     * Procedure that saves data direct in DB. Slightly better performance
     * @throws \yii\db\Exception
     */
    protected function directDbSave()
    {
        $polygonDbName = Polygon::tableName();
        $coordsDbName = PolygonCoord::tableName();

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        foreach ( $this->geoArea as $areaPolygon ) {
            $sql = "INSERT INTO {$polygonDbName} (zip, type) VALUES (:zip, :type)";
            $db->createCommand($sql)
                ->bindValues([
                    ':zip' => $this->zipCode,
                    ':type' => 'outer'
                ])
                ->execute();

            $polygonId = $db->getLastInsertID();

            $sql = "INSERT INTO {$coordsDbName} (polygon_id, lat, lon) VALUES (:polygon_id, :lat, :lon)";
            $stmnt = $db->createCommand($sql);

            $firstRecord = true;

            foreach ( $areaPolygon as $point ) {

                $lat = $point['lat'];
                $lon = $point['lon'];

                //Initial values
                if ( $firstRecord ) {
                    $minLat = $lat;
                    $maxLat = $lat;

                    $minLon = $lon;
                    $maxLon = $lon;

                    $firstRecord = false;
                }

                if ( $maxLat < $lat ) {
                    $maxLat = $lat;
                }

                if ( $minLat >= $lat ) {
                    $minLat = $lat;
                }


                if ( $maxLon < $lon ) {
                    $maxLon = $lon;
                }

                if ( $minLon >= $lon ) {
                    $minLon = $lon;
                }

                $stmnt->bindValues([
                    ':polygon_id' => $polygonId,
                    ':lat' => (float)$point['lat'],
                    ':lon' => (float)$point['lon'],
                ])->execute();
            }
        }

        $sql = "UPDATE {$polygonDbName}
                  SET maxLon = {$maxLon},
                  SET maxLat = {$maxLat},
                  SET minLon = {$minLon},
                  SET minLat = {$minLat}
                WHERE id = {$polygonId}";
        $db->createCommand($sql)
            ->bindValues([
                ':zip' => $this->zipCode,
                ':type' => 'outer'
            ])
            ->execute();

        $transaction->commit();
    }

    /**
     * @param string $with
     */
    public function save( $with = 'ar') {

        if ( 'ar' === $with ) {
            $this->saveWithAr();
        } else {
            $this->directDbSave();
        }

        echo "Zip: {$this->zipCode} is saved.\n";

        $this->wipe();
    }

    /**
     * Wipes all model data
     */
    protected function wipe()
    {
        $this->zipCode = null;
        $this->geoArea = [];
    }
} 