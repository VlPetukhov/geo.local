<?php
/**
 * @class Polygon
 * @namespace app\components\kmlZctaParser
 *
 * @property int $id
 * @property int $zip
 * @property string $type
 * @property float $maxLon
 * @property float $maxLat
 * @property float $minLon
 * @property float $minLat
 */

namespace app\components\kmlZctaParser;


use yii\db\ActiveRecord;

class Polygon extends ActiveRecord{

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%polygon}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['zip', 'type'], 'required', 'on' => ['create', 'update']],
            [['zip'], 'integer', 'min' => 0, 'on' => ['create', 'update']],
            [['type'], 'default', 'value' => 'outer', 'on' => ['create', 'update']],
            [['type'], 'in', 'range' => ['inner', 'outer'], 'on' => ['create', 'update']],
            [['maxLat','minLat','maxLon','minLon'], 'double', 'on' => ['create', 'update']],
            [['maxLat','minLat','maxLon','minLon'], 'default', 'value' => 0.0, 'on' => ['create', 'update']],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoordinates()
    {
        return $this->hasMany(PolygonCoord::className(), ['polygon_id' => 'id'])->inverseOf('getPolygon');
    }

    /**
     * Finds ZIP codes that contain given GEO - point
     *
     * @param float $lat
     * @param float $lon
     * @return array
     */
    public static function getZipByLocation ( $lat, $lon )
    {
        $foundZips = [];

        $polygons = static::find()
            ->where(
                'minLat < :lat AND maxLat >= :lat AND minLon < :lon AND maxLon >= :lon',
                [':lat' => $lat, ':lon' => $lon]
            )
            ->all();


        foreach ( $polygons as $polygon ) {
            $pointsCoords = PolygonCoord::find()
                ->select(['lat', 'lon'])
                ->where('polygon_id = :polygon_id', [':polygon_id' => $polygon->id])
                ->asArray()
                ->all();

            foreach ($pointsCoords as &$pointCoords ) {
                $pointCoords['lat'] = floatval($pointCoords['lat']);
                $pointCoords['lon'] = floatval($pointCoords['lon']);
            }

            $result = false;
            $pintsCnt = count($pointsCoords);
            for ($i = 0, $j = $pintsCnt - 1; $i < $pintsCnt; $j = $i++) {
               if ((
                    $pointsCoords[$i]['lon'] < $pointsCoords[$j]['lon'] &&
                    $pointsCoords[$i]['lon'] <= $lon &&
                    $lon <= $pointsCoords[$j]['lon'] &&
                    (
                        ($pointsCoords[$j]['lon'] - $pointsCoords[$i]['lon']) * ($lat - $pointsCoords[$i]['lat'])
                        >
                        ($pointsCoords[$j]['lat'] - $pointsCoords[$i]['lat']) * ($lon - $pointsCoords[$i]['lon'])
                    )
                   )
                   ||
                   (
                        $pointsCoords[$i]['lon']>$pointsCoords[$j]['lon'] &&
                        $pointsCoords[$j]['lon']<=$lon &&
                        $lon<=$pointsCoords[$i]['lon'] &&
                        (
                            ($pointsCoords[$j]['lon'] - $pointsCoords[$i]['lon']) * ($lat - $pointsCoords[$i]['lat'])
                            <
                            ($pointsCoords[$j]['lat'] - $pointsCoords[$i]['lat']) * ($lon - $pointsCoords[$i]['lon'])
                        )
                    )
               ) {
                   $result = !$result;
               }
            }

            if ( $result ) {
                $foundZips[] = $polygon->zip;
            }
        }

        return $foundZips;
    }
} 