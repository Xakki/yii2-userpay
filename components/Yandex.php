<?php
/**
 * User: xakki
 * Date: 25.12.15
 * @see https://ishop.qiwi.com/options/rest.action
 */

namespace app\modules\UserPay\components;

class Yandex extends Payment
{
    // https://money.yandex.ru/doc.xml?id=526991
    public $name = 'Yandex Money';
    public $access_token;
    public $expire_token;
    public $client_id;
    public $client_secret;
    public $method;
    public $account;
    public $password;
    public $url_new_token;
    public $form_desc;
    public $commision = 0.03; // так то 0.02 , но навсякий
    public $labelPrefix = 'A'; // Если в яндексе принимаете денги с разных сайтов, а тк оповещения идут только на один адрес, нужно както отличать сервисы
    public $orderName = 'Order #'; // Сообщение получателю

    public function getUrlForm()
    {
        if ($this->getTestMode()) {
            return 'https://demomoney.yandex.ru/quickpay/confirm.xml';
        } else {
            return 'https://money.yandex.ru/quickpay/confirm.xml';
        }
    }

    public function getUrlApi()
    {
        if ($this->getTestMode()) {
            return 'https://demomoney.yandex.ru/api';
        } else {
            return 'https://money.yandex.ru/api';
        }
    }

    public function getUrlAuth()
    {
        if ($this->getTestMode()) {
            return 'https://demomoney.yandex.ru/oauth/authorize';
        } else {
            return 'https://money.yandex.ru/oauth/authorize';
        }
    }

    public function getUrlToken()
    {
        if ($this->getTestMode()) {
            return 'https://demomoney.yandex.ru/oauth/token';
        } else {
            return 'https://money.yandex.ru/oauth/token';
        }
    }

    /**
     * Если счет частично оплачен
     * @return int
     */
    public function getAmount() {
        $amount = $this->getResponseAmount();
        if ($amount > 0) {
            $resAmount = ceil($this->amount - $amount);
            if ($resAmount < $this->getMinPayment()) {
                $resAmount = $this->getMinPayment();
            }
            return $resAmount;
        }
        return $this->amount;
    }

    public function getAccessToken() {
        if (!$this->access_token) {
            $this->getNewAccessToken();
        }
        $expire = strtotime($this->getExpireToken());
        if ($expire< (time() - 2592000)) { // before 30 day
            $this->mailNotify('Expire Yandex Token', 'Soon expire Yandex Token , you need <a href="'.\Yii::$app->getUrlManager()->createAbsoluteUrl($this->getUrlNewToken()).'">get new token</a>');
            return false;
        }
        return $this->access_token;
    }

    public function getExpireToken() {
        return $this->expire_token;
    }

    public function getClientId() {
        return $this->client_id;
    }

    public function getClientSecret() {
        return $this->client_secret;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getAccount() {
        return $this->account;
    }
    public function getPassword() {
        return $this->password;
    }
    public function getUrlNewToken() {
        return $this->url_new_token;
    }
    public function getFormDescription() {
        return ($this->form_desc ? $this->form_desc : $this->getDescription());
    }


    public function send() {
        $this->check();
//        if(!$this->isSuccessful() && !$this->isRedirect()) {
//        }
        return $this;
    }

    public function check() {
        $this->getApiRequest('operation-history', ['label' => $this->labelPrefix.$this->getOrderId(),'type' => 'deposition']);
        return $this;
    }

    public function getAllHistory($limit = 10)
    {
        return $this->getApiRequest('operation-history', ['records' => $limit, 'type' => 'deposition']);
    }

    private function getApiRequest($method, $post) {

        $param = [
            'HTTPHEADER' => [
                'Authorization: Bearer '.$this->getAccessToken(),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'POST' => $post
        ];
        $param['SSL'] = true;
        $param['FORBID'] = true;
        $param['USERAGENT'] = \Yii::$app->name;
        $url = $this->getUrlApi().'/'.$method;

        \Yii::trace('Start');
        $response = self::_http($url, $param, $getInfo);
        $this->response = json_decode($response, true);
        return json_decode($response);
    }

    protected function getNewAccessToken() {
        $redirectUrl = \Yii::$app->getUrlManager()->createAbsoluteUrl($_SERVER['DOCUMENT_URI']);
        echo '<h1>Need access_token for Yandex.</h1>';
        if (!isset($_GET['code'])) {
            $SCOPE = array('account-info', 'operation-history', 'operation-details');
            echo '<form method="post" action="'.$this->getUrlAuth().'" enctype="application/x-www-form-urlencoded">
            <input type="hidden" name="client_id"/ value="'.$this->getClientId().'">
            <input type="hidden" name="response_type"/ value="code">
            <input type="hidden" name="scope"/ value="' . implode(' ', $SCOPE) . '">
            <input type="hidden" name="redirect_uri"/ value="'.$redirectUrl.'">
            <input type="submit" value="Get Auth"/>
            </form>';
        }
        else {
            echo '<form method="post" action="'.$this->getUrlToken().'" enctype="application/x-www-form-urlencoded">
            <input type="hidden" name="client_id" value="'.$this->getClientId().'"/>
            <input type="hidden" name="client_secret" value="'.$this->getClientSecret().'"/>
            <input type="hidden" name="code" value="'.$_GET['code'].'"/>
            <input type="hidden" name="grant_type" value="authorization_code"/>
            <input type="hidden" name="redirect_uri" value="'.$redirectUrl.'"/>
            <input type="submit" value="Get Token"/>
            </form>';
        }
        die();

    }


    /**
     * Уведомления от ПС
     * @return bool
     */
    public function notify() {
        if ($this->checkSign()) {
            $this->orderId = (int) str_replace($this->labelPrefix, '', $_POST['label']);
            return ($this->orderId);
        }
        return false;
    }

    protected function checkSign()
    {
        $string = $_POST['notification_type'] . '&'
            . $_POST['operation_id'] . '&'
            . $_POST['amount'] . '&'
            . $_POST['currency'] . '&'
            . $_POST['datetime'] . '&'
            . $_POST['sender'] . '&'
            . $_POST['codepro'] . '&'
            . $this->getPassword() . '&'
            . $_POST['label'];
        return (sha1($string) === $_POST['sha1_hash']);
    }

    public function notifyResponse() {
        if (!$this->getOrderId()) {
            \Yii::$app->response->setStatusCode(424);
            $this->mailNotify('Notify problem', 'Wrong get OrderId');
            return 'Error';
        }
        else {
            return 'OK';
        }
    }

    public function isSuccessful() {
        $amount = $this->getResponseAmount();
        $res = false;
        if ($amount > ($this->getAmount() * (1 - $this->commision))) { // проверка суммы с учетом комиссии
            $res = true;
        }
        return $res;
    }

    public function getOrderName() {
        // (до 150 символов) — назначение платежа.
        return $this->orderName . $this->getOrderId();
    }

    public function isRedirect() {
        // todo errr
//        print_r('<pre>');
//        print_r($this->response);
//        $this->getAllHistory();
//        print_r($this->response);
//        exit();
        if (!$this->redirect) {
            $data = array();
            $data['URL'] = $this->getUrlForm();
            $data['receiver'] = $this->getAccount();

            // //  название перевода в истории отправителя.
            // Удобнее всего формировать его из названий магазина и товара. Например, «Мой магазин: валенки белые».
            $data['formcomment'] = $this->getAppName().': '.$this->getFormDescription(); // max 50 len

            // название перевода на странице подтверждения. Рекомендуем делать его таким же, как formcomment.
            $data['short-dest'] = $data['formcomment'];

            $data['writable-targets'] = 'false';
            $data['label'] = $this->getOrderId(); // до 64 символов
            $data['quickpay-form'] = 'shop';
            $data['targets'] = $this->getOrderName();
            $data['sum'] = $this->getAmount();
            $data['comment-needed'] = 'true';
            $data['comment'] = $this->getDescription(); // поле, в котором можно передать комментарий отправителя перевода.
            $data['need-fio'] = 'yes';
            $data['need-email'] = 'yes';
            $data['need-phone'] = 'false';
            $data['need-address'] = 'false';
            $data['paymentType'] = $this->getMethod();
            $data['successURL'] = $this->getReturnUrl();
            $data['failURL'] = $this->getCancelUrl();
            $this->redirect = $data;
            return true;
        }
        return ($this->redirect);
    }


    private function getResponseAmount() {
        $amount = 0;

        if (!empty($this->response['operations'])) {
            foreach ($this->response['operations'] as $operation) {
                if (!empty($operation) && $operation['direction'] == 'in' && $operation['status'] == 'success' && $operation['label'] == $this->labelPrefix.$this->getOrderId()) {
                    $amount += $operation['amount'];
                }
            }
        }
        return $amount;
    }

    public function getMessage() {
        $amount = $this->getResponseAmount();
        $mess = '';
        if ($amount>0 && $amount < ($this->getAmount() * (1 - $this->commision))) {
            $mess = \Yii::t('app', 'You paid less than the prescribed sum: Pay extra {0}', [ ceil($this->getAmount() - $amount) ]);
        }
        elseif (empty($this->response['operations']) || !count($this->response['operations'])) {
            $mess = \Yii::t('app', 'Bill was not found');
        }
        elseif (count($this->response['operations']) && $this->response['operations'][0]['status'] != 'success') {
            $mess = \Yii::t('app', 'Bill was not yet success');
        }
        elseif (count($this->response['operations'])) {
            $mess = \Yii::t('app', 'Bill was not correct');
        }
        return $mess;
    }

    public function getTransactionId() {
        exit('TODO getTransactionId');
        return null;
    }

}