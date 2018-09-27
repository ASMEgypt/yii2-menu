<?php

namespace execut\menu\models;

use execut\crudFields\Behavior;
use execut\crudFields\BehaviorStub;
use execut\crudFields\fields\HasOneSelect2;
use execut\crudFields\ModelsHelperTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "menu_items".
 */
class Item extends ActiveRecord
{
    const MODEL_NAME = '{n,plural,=0{Items} =1{Item} other{Items}}';
    use BehaviorStub, ModelsHelperTrait;
//    public function init()
//    {
//        parent::init(); // TODO: Change the autogenerated stub
////        $this->visible = true;
//        $defaultId = Menu::getDefaultId();
//        if ($defaultId) {
//            $this->menu_menu_id = $defaultId;
//        }
//    }

    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'fields' => [
                    'class' => Behavior::class,
                    'plugins' => \yii::$app->getModule('menu')->getItemFieldsPlugins(),
                    'fields' => $this->getStandardFields(null, [
                        [
                            'class' => HasOneSelect2::class,
                            'attribute' => 'menu_menu_id',
                            'relation' => 'menuMenu',
                            'url' => [
                                '/menu/menus'
                            ],
                            'defaultValue' => Menu::getDefaultId(),
                        ],
                        [
                            'required' => true,
                            'attribute' => 'sort',
                        ],
                        [
                            'class' => HasOneSelect2::class,
                            'attribute' => 'menu_item_id',
                            'relation' => 'menuItem',
                            'url' => [
                                '/menu/items'
                            ],
                        ],
                    ]),
                ],
                [
                    'class' => TimestampBehavior::className(),
                    'createdAtAttribute' => 'created',
                    'updatedAtAttribute' => 'updated',
                    'value' => new Expression('NOW()'),
                ],
                # custom behaviors
            ]
        );
    }

    public static function getMenuItems($position) {
        $q = self::find()->isVisible()->orderBySort()->byPositionKey($position);
        \yii::$app->getModule('menu')->applyItemsScopes($q);
        $items = $q->all();

        $result = self::getItemItems($items);

        return $result;
    }

    public function getUrl() {
        $plugins = \yii::$app->getModule('menu')->getPlugins();
        foreach ($plugins as $plugin) {
            $url = $plugin->getUrlByItem($this);
            if ($url) {
                return $url;
            }
        }

        return false;
    }

    public static function getItemItems(&$items, $parentId = null) {
        $result = [];
        foreach ($items as $key => $item) {
            if ($item->menu_item_id === $parentId) {
                unset($items[$key]);
                $result[] = [
                    'label' => $item->name,
                    'url' => $item->getUrl(),
                    'items' => self::getItemItems($items, $item->id),
                    'sort' => $item->sort,
                ];
            }
        }

        if ($parentId !== null) {
            uasort($result, function ($a, $b) {
                return $a['sort'] > $b['sort'];
            });
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'menu_items';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuItem()
    {
        return $this->hasOne(\execut\menu\models\Item::className(), ['id' => 'menu_item_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(\execut\menu\models\Item::className(), ['menu_item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuMenu()
    {
        return $this->hasOne(\execut\menu\models\Menu::className(), ['id' => 'menu_menu_id']);
    }


    /**
     * @inheritdoc
     * @return \execut\menu\models\queries\ItemQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \execut\menu\models\queries\ItemQuery(get_called_class());
    }
}
