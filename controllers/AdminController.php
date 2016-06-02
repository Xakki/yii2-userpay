<?php

namespace app\modules\UserPay\controllers;

use \app\controllers\Controller;
use \app\modules\UserPay\models\UserPay;
use \Yii;
use yii\helpers\Url;

/**
 * Class AdminController
 * @package app\modules\UserPay\controllers
 * @property \app\modules\UserPay\Module $module
 */
class AdminController extends Controller
{

    public function actionIndex()
    {
        $data = [
        ];
        return $this->render('index', $data);
    }

}
