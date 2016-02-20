<?php
/**
 * @class OuterPolygonCoords
 * @namespace app\components\kmlZctaParser
 */

namespace app\components\kmlZctaParser;


use yii\db\ActiveRecord;

class PolygonCoord extends ActiveRecord {

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%polygon_coord}}';
    }

    public function rules()
    {
        return [
            [['polygon_id', 'lat', 'lon'], 'required', 'on' => ['create', 'update']],
            [['polygon_id'], 'integer', 'min' => 0, 'on' => ['create', 'update']],
            [['lat', 'lon'], 'double', 'on' => ['create', 'update']],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPolygon()
    {
        return $this->hasOne(Polygon::className(), ['id' => 'polygon_id'])->inverseOf('getCoordinates');
    }
} 