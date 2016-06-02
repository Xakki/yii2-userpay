<?php
/**
 * User: xakki
 * Date: 25.12.15
 * @see https://ishop.qiwi.com/options/rest.action
 */

namespace app\modules\UserPay\components;

class Qiwi extends Payment
{
    public $name = 'QIWI';
    public $providerId;
    public $apiId;
    public $apiKey;
    public $notifyKey;

    public function getParamProviderId() {
        return $this->providerId;
    }

    public function getParamApiKey() {
        return $this->apiKey;
    }

    public function getParamApiId() {
        return $this->apiId;
    }
    public function getParamNotifyKey() {
        return $this->notifyKey;
    }

    public function send() {
        $this->check();
        if(!$this->isSuccessful() && !$this->isRedirect()) {
//            $post = [
//                'amount' => $this->getAmount(),
//                'ccy' => $this->getCurrency(),
//                'account' => $this->getOrderId(),
//                'comment' => $this->getDescription(),
////                'extras' => 'всякое',
////                'lifetime' => date('Y-m-dTH:i:s', (time() + 3600 * 24 * 5) ),
////                'pay_source' => 'qw', // mobile
////                'prv_name' => 'SIte name',
//            ];
//            $this->getApiRequest($post);
            $get = [
                'from' => $this->getParamProviderId(),
                'summ' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'txn_id' => $this->getOrderId(),
                'comm' => $this->getDescription(),
                'successUrl' => $this->getReturnUrl(),
                'failUrl' => $this->getCancelUrl(),
                // iframe
                // to
                // target
                // lifetime
            ];
            $this->redirect = 'https://w.qiwi.com/order/external/create.action?'.http_build_query($get);
        }
        return $this;
    }

    public function check() {
        $this->getApiRequest();
        return $this;
    }

    /**
     * Уведомления от ПС
     * @return bool
     */
    public function notify() {
        $this->notifyStatus = 5;
        if (!empty($_SERVER['HTTP_X_API_SIGNATURE'])) {
            ksort($_POST);
            $Invoice_parameters_byte = implode('|', $_POST);
            $Notification_password_byte = $this->getParamNotifyKey();
            $sign = hash_hmac('sha1', $Invoice_parameters_byte, $Notification_password_byte, true);
            if (strcmp($_SERVER['HTTP_X_API_SIGNATURE'], base64_encode($sign))===0) {
                $this->notifyStatus = 0;
                $this->orderId = $_POST['bill_id'];
                return true;
            }
        }
        return false;
    }

    public function notifyResponse() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'text/xml');
        return '<?xml version="1.0"?><result><result_code>'.$this->notifyStatus.'</result_code></result>';
    }

    private function getApiRequest($post = false) {
        $url = 'https://api.qiwi.com/api/v2/prv/'.$this->getParamProviderId().
            '/bills/'.$this->getOrderId();

        $param = [
            'HTTPHEADER' => [
                'Accept: text/json',
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            ],
            'AUTH_BASIC' => $this->getParamApiId().':'.$this->getParamApiKey()
        ];
        $param['FORBID'] = true;
        if ($post) {
            $param['POST'] = $post;
        }

        $response = self::_http($url, $param);

        $response = json_decode($response, true);
        $this->response = $response['response'];
//        print_r('<pre>');
//        print_r($response);
//        exit();
    }

    protected function isTruePaid() {
        return ($this->response && !$this->response['result_code'] && !$this->response['bill']['error'] && $this->response['bill']['bill_id'] == $this->getOrderId() && $this->response['bill']['amount'] == $this->getAmount());
    }

    public function isSuccessful() {
        return ($this->isTruePaid() && $this->response['bill']['status']=='paid');
    }

    public function isRedirect() {
        if (!$this->redirect && $this->isTruePaid() && $this->response['bill']['status']=='waiting') {
            $data = [
                'shop' => $this->getParamProviderId(),
                'transaction' => $this->getOrderId(),
                'successUrl' => $this->getReturnUrl(),
                'failUrl' => $this->getCancelUrl(),
//            'iframe' => '', // URL для переадресации в случае неуспеха при создании транзакции в Visa Qiwi Wallet. Ссылка должна вести на сайт провайдера
//            'target' => '', // Флаг, показывающий, что ссылки в параметрахsuccessUrl / failUrl открываются в iframe. Если отсутствует, то считается выключенным
            ];
            $this->redirect = 'https://qiwi.com/order/external/main.action?'.http_build_query($data);
        }
        return ($this->redirect);
    }

    public function getMessage() {
        return \Yii::t('app', $this->response['description']);
    }

    public function getTransactionId() {
        return null;
    }

}