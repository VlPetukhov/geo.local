<?php

use yii\db\Migration;

class m160218_073801_polygon_coords_index extends Migration
{
    public function up()
    {
        $this->createIndex('polygon_coord_idx', '{{%polygon_coord}}', ['lat', 'lon']);
    }

    public function down()
    {
        echo "m160218_073801_polygon_coords_index cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
