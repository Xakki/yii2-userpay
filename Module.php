<?php
/**
 * Couber by xakki
 */
namespace app\modules\UserPay;

use app\modules\UserPay\models\UserPay;
use Yii;
use yii\helpers\Url;
use yii\web\GroupUrlRule;

class Module extends \yii\base\Module
{
    const VERSION = '0.0.1';

    public $urlPaymentInfo = '/userpay/info/';
    public $urlPaymentSuccess = '/userpay/success/';
    public $urlPaymentFail = '/userpay/fail/';

    public $currency = 'RUB';
    public $curs = 1; // курс лайков к одному рублю
    public $minCost = 20;
    public $pack = [
        1000 => 5,  // лайки => + %бонус
        10 => 0
    ];
    public $paySystems = [];  // enable pay systems

//    public $urlPrefix = 'userpay';
//
//    public $urlRules = [
//        '/userpay/default/create/<id:\d+>'                  => 'default/create',
//        '/createpay'                       => 'default/index',
//        '<action:(login|logout)>'                => 'security/<action>',
//        '<action:(register|resend)>'             => 'registration/<action>',
//        'confirm/<id:\d+>/<code:[A-Za-z0-9_-]+>' => 'registration/confirm',
//        'forgot'                                 => 'recovery/request',
//        'recover/<id:\d+>/<code:[A-Za-z0-9_-]+>' => 'recovery/reset',
//        'settings/<action:\w+>'                  => 'settings/<action>'
//    ];

    public function init()
    {
        krsort($this->pack);
        parent::init();
    }

    public function getBonusPercent($likes) {
        $res = 0;

        foreach ($this->pack as $k=>$r) {
            $res = $r;
            if ($k<=$likes) {
                break;
            }
        }
        return $res;
    }

    public function getBonusLikes($likes) {
        return ceil($likes * $this->getBonusPercent($likes)/100);
    }

    public function getLikesCost($likes) {
        return floor($likes / $this->curs);
    }

    public function hasPaySystem($ps) {
        return isset($this->paySystems[$ps]);
    }

    /**
     * @param $ps
     * @param $likes
     * @return UserPay
     */
    public function getExistPayment($ps, $likes) {
        return UserPay::findInitPayment($ps, $likes);
    }

    /**
     * @param $ps
     * @param $likes
     * @return UserPay
     */
    public function getNewPayment($ps, $likes) {
        return UserPay::create($ps, $likes);
    }

    public function getUrlPaymentInfo($id) {
        return Yii::$app->getUrlManager()->createAbsoluteUrl($this->urlPaymentInfo.$id);
    }

    public function getUrlPaymentSuccess($id) {
        return Yii::$app->getUrlManager()->createAbsoluteUrl($this->urlPaymentSuccess.$id);
    }

    public function getUrlPaymentFail($id) {
        return Yii::$app->getUrlManager()->createAbsoluteUrl($this->urlPaymentFail.$id);
    }

    /**
     * @param $model
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function initPayment(UserPay $model, $isCheck = false) {

        $id = $model->getPrimaryKey();
        $ps = $model->up_paysystem;
        $likes = $model->up_likes;
        $bonusLikes = $model->up_likes_bonus;
        $costLikes = $model->up_amount;

        /* @var $payComponent \app\modules\UserPay\components\Payment */
        $payComponent = Yii::$app->get($ps);

        $description = $likes.($bonusLikes ? '(+'.$bonusLikes.')' : '').' Likes';
        $returnUrl = $this->getUrlPaymentSuccess($id); // Url, на который перенаправляется пользователь при успешной оплате счета
        // $infoUrl = $this->getUrlPaymentInfo($id); // Url, на который перенаправляется пользователь при успешной оплате счета
        $cancelUrl = $this->getUrlPaymentFail($id); //  Url, на который перенаправляется пользователь при неуспешной оплате счета
        $payParam = [
            'amount' => $costLikes,
            'returnUrl' =>  $returnUrl,
            'cancelUrl' =>  $cancelUrl,
            'orderId'   =>  $id,
            'description' => $description
        ];

        $response = $payComponent->purchase($payParam)->send();

        // Process response
        if ($response->isSuccessful()) {
            // Payment was successful
            UserPay::completePayment($id);
            return true;
        }
        elseif (!$isCheck && $response->isRedirect()) {
            // Redirect to offsite payment gateway
            return $response->redirect();
        }
        else {
            return $response->getMessage();
        }
    }

    /**
     * Нотификация о платеже от ПС
     * @param $ps
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function notifyPayment($ps) {
        /* @var $payComponent \app\modules\UserPay\components\Payment */
        $payComponent = Yii::$app->get($ps);
        if($payComponent->notify()) {
            UserPay::completePayment($payComponent->getOrderId());
        }
        return $payComponent->notifyResponse();
    }

}
