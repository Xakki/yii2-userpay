<?php

namespace app\modules\UserPay\controllers;

use \app\controllers\Controller;
use \app\modules\UserPay\models\UserPay;
use \Yii;

/**
 * Class IncomeController
 * @package app\modules\UserPay\controllers
 * @property \app\modules\UserPay\Module $module
 */
class IncomeController extends Controller
{
    public $enableCsrfValidation = false;


    public function actionConfirm($ps)
    {
        if (!$this->module->hasPaySystem($ps)) {
            return 'Error pay system';
        }
        return $this->module->notifyPayment($ps);
    }


    public function actionNewTokenYandex()
    {
        if (!$this->module->hasPaySystem('payyandex')) {
            return 'Error pay system';
        }
        /* @var $payComponent \yandexmoney\YandexMoney\GatewayIndividual */
        $payComponent = Yii::$app->get('payyandex');
        $payComponent->getNewAccessToken();
    }
}
