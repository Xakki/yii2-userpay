<?php

namespace app\modules\UserPay\controllers;

use \app\controllers\Controller;
use \app\modules\UserPay\models\UserPay;
use \Yii;
use yii\helpers\Url;

/**
 * Class DefaultController
 * @package app\modules\UserPay\controllers
 * @property \app\modules\UserPay\Module $module
 */
class DefaultController extends Controller
{
    public $layout = '/dashboard';

    public function actionIndex($likes)
    {
        $data = [
            'likes' => $likes,
        ];
        return $this->render('index', $data);
    }

    public function actionCreate($ps, $likes)
    {
        if (!$this->module->hasPaySystem($ps)) {
            return 'Error pay system';
        }

        $model = $this->module->getExistPayment($ps, $likes);

        if (!$model) {
            $model = $this->module->getNewPayment($ps, $likes);
            if (!$model) {
                return $this->render('error', ['mess' => 'Cant create payment']);
            }
        }

        $id = $model->getPrimaryKey();

        if (isset($_GET['redirect'])) {
            return $this->redirect($this->module->getUrlPaymentInfo($id));
        }

        $mess = $this->module->initPayment($model);
        if (is_string($mess)) {
            // Payment failed
            Yii::$app->session->setFlash('danger', $mess);
            return $this->redirect($this->module->getUrlPaymentInfo($id));
        }
        elseif (is_object($mess)) {
            return $mess;
        }
        else {
            return $this->redirect($this->module->getUrlPaymentInfo($id));
        }
    }


    public function actionInit($id)
    {
        /* @var $userPay UserPay */
        $userPay = UserPay::findOne($id);
        if ($userPay) {
            if ($userPay->isStatusOk()) {
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
            $mess = $this->module->initPayment($userPay);
            if (is_string($mess)) {
                // Payment failed
                Yii::$app->session->setFlash('danger', $mess);
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
            elseif (is_object($mess)) {
                return $mess;
            }
            else {
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
        }
        else {
            $this->render('error', ['mess' => 'Not exists payment']);
        }
    }

    public function actionSuccess($id)
    {
        /* @var $userPay UserPay */
        $userPay = UserPay::findOne($id);
        if ($userPay) {
            if ($userPay->isStatusOk()) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Success payment'));
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
            $mess = $this->module->initPayment($userPay, true);
            if (is_string($mess)) {
                // Payment failed
                Yii::$app->session->setFlash('danger', $mess);
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
            elseif (is_object($mess)) {
                return $mess;
            }
            else {
                Yii::$app->session->setFlash('info', Yii::t('app', 'Not paid yet'));
                return $this->redirect($this->module->getUrlPaymentInfo($id));
            }
        }
        else {
            $this->render('error', ['mess' => 'Not exists payment']);
        }
    }

    public function actionHistory()
    {
        /* @var $userPay UserPay */
        $data = UserPay::findAll(['up_user_id' => Yii::$app->user->id]);
        return $this->render('history', ['data' => $data]);
    }

    public function actionInfo($id)
    {
        /* @var $userPay UserPay */
        $userPay = UserPay::findOne($id);
        $res = $this->checkPaymentPrm($userPay);
        if($res!==true) {
            return $res;
        }
        // check status each 1 min
        return $this->render('info', ['userPay' => $userPay]);
    }

    public function actionFail($id)
    {
        /* @var $userPay UserPay */
        $userPay = UserPay::findOne($id);
        $res = $this->checkPaymentPrm($userPay);
        if($res!==true) {
            return $res;
        }

        if ($userPay->isStatusInit()) {
            $userPay->setFailPayment();
        }
        if (!$userPay->isStatusOk()) {
            Yii::$app->session->setFlash('danger', 'Error payment');
        }
        return $this->redirect($this->module->getUrlPaymentInfo($id));
    }

    private function checkPaymentPrm(UserPay $userPay) {
        if ($userPay->up_user_id!=Yii::$app->user->id) {
            return $this->render('error', ['mess' => 'Access denied']);
        }
        return true;
    }
}
