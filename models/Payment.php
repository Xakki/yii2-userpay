<?php

namespace app\modules\UserPay\models;

use Yii;

/**
 * This is the model class for table "payment".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $pay_system
 * @property integer $status
 * @property integer $amount
 * @property integer $amount_sys
 * @property string $cur
 * @property integer $time_cr
 * @property integer $time_up
 * @property integer $time_end
 */
class Payment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'pay_system', 'status', 'amount', 'cur', 'time_cr'], 'required'],
            [['user_id', 'status', 'amount', 'amount_sys', 'time_cr', 'time_up', 'time_end'], 'integer'],
            [['pay_system', 'cur'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app/model', 'ID'),
            'user_id' => Yii::t('app/model', 'User ID'),
            'pay_system' => Yii::t('app/model', 'Платежная система'),
            'status' => Yii::t('app/model', 'Статус'),
            'amount' => Yii::t('app/model', 'Amount'),
            'amount_sys' => Yii::t('app/model', 'Начисляемые единицы'),
            'cur' => Yii::t('app/model', 'Валюта'),
            'time_cr' => Yii::t('app/model', 'Время создания'),
            'time_up' => Yii::t('app/model', 'Время обновления'),
            'time_end' => Yii::t('app/model', 'Время завершения'),
        ];
    }
}
