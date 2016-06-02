<?php

namespace app\modules\UserPay\models;

use app\models\User;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * Class UserPay
 * @package app\models
 * @property integer $up_id
 * @property string  $up_paysystem
 * @property integer $up_time_cr
 * @property integer $up_time_up
 * @property integer $up_status
 * @property integer $up_amount
 * @property integer $up_likes
 * @property integer $up_likes_bonus
 * @property integer $up_user_id
 * @property string $up_psid
 */
class UserPay extends ActiveRecord
{
    const STATUS_INIT = 0;
    const STATUS_ERROR = 4;
    const STATUS_OK = 5;
    public static $status_enum = [
        self::STATUS_INIT => 'Initiated payment',
        self::STATUS_ERROR => 'Error payment',
        self::STATUS_OK => 'Success payment',
    ];

    public static function tableName()
    {
        return 'user_pay';
    }

    public function attributeLabels()
    {
        return [
            'up_id'          => 'Id',
            'up_time_cr'             => Yii::t('app/model', 'Date create'),
            'up_time_up'   => Yii::t('app/model', 'Date update'),
            'up_user_id' => Yii::t('app/model', 'User'),
            'up_status'          => Yii::t('app/model', 'Status'),
            'up_likes'        => Yii::t('app/model', 'Likes'),
            'up_likes_bonus'      => Yii::t('app/model', 'Bonus'),
            'up_paysystem'      => Yii::t('app/model', 'Pay system'),
            'up_amount'      => Yii::t('app/model', 'Amount'),
        ];
    }


    /**
     * @return User
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'up_user_id']);
    }

    public function getPaySystemName()
    {
        /* @var $UserPayModule \app\modules\UserPay\Module */
        $UserPayModule = \Yii::$app->getModule('userpay');
        return $UserPayModule->paySystems[$this->up_paysystem];
    }

    public function getCurrency()
    {
        /* @var $payComponent \Omnipay\Qiwi\Gateway */
        $payComponent = Yii::$app->get($this->up_paysystem);
        return $payComponent->getCurrency();
    }

    public static function findInitPayment($ps, $likes)
    {
        /* @var $UserPayModule \app\modules\UserPay\Module */
        $UserPayModule = \Yii::$app->getModule('userpay');
        $costLikes = $UserPayModule->getLikesCost($likes);
        $bonusLikes = $UserPayModule->getBonusLikes($likes);

        return self::findOne([
            'up_status' => self::STATUS_INIT,
            'up_paysystem' => $ps,
            'up_amount' => $costLikes,
            'up_likes' => $likes,
            'up_likes_bonus' => $bonusLikes,
            'up_user_id' => Yii::$app->user->id,
        ]);

    }


    public static function create($ps, $likes)
    {
        /* @var $UserPayModule \app\modules\UserPay\Module */
        $UserPayModule = \Yii::$app->getModule('userpay');
        $costLikes = $UserPayModule->getLikesCost($likes);
        $bonusLikes = $UserPayModule->getBonusLikes($likes);
        $model = new self();
        $model->up_status = self::STATUS_INIT;
        $model->up_paysystem = $ps;
        $model->up_amount = $costLikes;
        $model->up_likes =  $likes;
        $model->up_likes_bonus =  $bonusLikes;
        $model->up_time_cr = time();
        $model->up_user_id = Yii::$app->user->id;
        if ($model->save()) {
            return $model;
        }
        return false;
    }

    public function getStatus() {
        return \Yii::t('app', self::$status_enum[$this->up_status]);
    }

    public function isStatusInit() {
        return $this->up_status==self::STATUS_INIT;
    }

    public function isStatusOk() {
        return $this->up_status==self::STATUS_OK;
    }

    public function isStatusError() {
        return $this->up_status==self::STATUS_ERROR;
    }

    public function setFailPayment() {
        return $this->updateAttributes(['up_status' => self::STATUS_ERROR, 'up_time_up' => time()]);
    }

    public function setOkPayment() {
        return $this->updateAttributes(['up_status' => self::STATUS_OK, 'up_time_up' => time()]);
    }

    public static function completePayment($id) {
        /* @var $user self */
        $model = self::findOne((int) $id);
        if ($model && !$model->isStatusOk()) {
            $model->setSuccessPayment();
        }
    }

    private function setSuccessPayment() {
        $this->setOkPayment();
        /* @var $user User */
        $user = User::findOne($this->up_user_id);
        return $user->buyLikes($this->up_likes, $this->up_likes_bonus, $this->getPrimaryKey());
    }
//
//    public function checkSuccessPayment() {
//        if ($this->isStatusOk()) {
//            return true;
//        }
//        /* @var $payComponent \Omnipay\Qiwi\Gateway */
//        $payComponent = Yii::$app->get($this->up_paysystem);
//        if($this->up_paysystem == 'payyandex') {
//            /* @var $payComponent \yandexmoney\YandexMoney\GatewayIndividual */
//            $response = $payComponent->getOperationHistory($this->getPrimaryKey());
//            if (!empty($response->operations)) {
//                if (count($response->operations)>1) {
//                    trigger_error('Yandex Money: double pay : '.$this->getPrimaryKey(), E_USER_WARNING);
//                }
//                $operation = $response->operations[0];
//                if ($operation->amount>($this->up_amount * 0.95)) { // проверка суммы с учетом комиссии
//                    return $this->setSuccessPayment();
//                }
//                else {
//                    trigger_error('Yandex Money: wrong amount ['.$operation->amount.'], need ['.$this->up_amount.'] ^ '.$this->getPrimaryKey(), E_USER_WARNING);
//                }
//
//            }
////            print_r('<pre>');
////            print_r($response);
////            exit();
////            $response = $payComponent->authorize($_POST)->getData();
////            Yii::trace(json_encode($response));
////            if ($response['code']==0) {
////                UserPay::completePayment($response['orderNumber']);
////            }
//        }
//        elseif($this->up_paysystem == 'payqiwi') {
//            // TODO
//        }
//        return false;
//    }
}
