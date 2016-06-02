<?php
/**
 * User: xakki
 * Date: 25.12.15
 */

namespace app\modules\UserPay\components;

use yii\base\Exception;
use yii\web\Response;

abstract class Payment
{
    public $name; // Name Pay system
    public $app_name; // Name this app
    public $currency = 'RUB'; // currency

    public $amount; // params
    public $returnUrl; // returnUrl
    public $cancelUrl; // cancelUrl
    public $orderId; // site orderId
    public $description; // order description
    public $commision = 0; // pay system commision (from 0 to 1)
    public $isTest = false; // test mode
    public $min_payment = 10; // Обычно минимальный платеж 10 руб

    public $request; // params
    public $response; // params

    public $redirect = false;
    public $notifyStatus = false;

    public function setParams($params) {
        foreach ($params as $k=>$r) {
            if (property_exists($this, $k)) {
                $this->{$k} = $r;
            }
        }
    }

    public function getName() {
        return $this->name;
    }
    public function getAppName() {
        return ($this->app_name ? $this->app_name : \Yii::$app->name);
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getReturnUrl() {
        return $this->returnUrl;
    }

    public function getCancelUrl() {
        return $this->cancelUrl;
    }

    public function getTestMode() {
        return $this->isTest;
    }

    public function getMinPayment() {
        return $this->min_payment;
    }

    /**
     * Ввод данных
     * @param $params
     * @return $this
     */
    public function purchase($params) {
        //validate
        $this->setParams($params);
        return $this;
    }

    /**
     * Создание платежа
     * @return self
     */
    abstract public function send();

    /**
     * Проверка платежа
     * @return self
     */
    abstract public function check();

    /**
     * Входящие нотификации от ПС
     */
    public function notify() {
        return false;
    }
    public function notifyResponse() {
        return false;
    }

    public function isRedirect() {
        return $this->redirect;
    }

    final public function redirect() {
        if (!$this->redirect) return;
        if (is_string($this->redirect)) {
            return \Yii::$app->controller->redirect($this->redirect);
        }
        elseif (is_array($this->redirect)) {
            if (empty($this->redirect['URL'])) {
                throw new Exception('Payment: need URL for redirect');
            }
            $hiddenFields = '';
            $redirecturl = array_shift($this->redirect);
            foreach ($this->redirect as $key => $value) {
                $hiddenFields .= sprintf(
                        '<input type="hidden" name="%1$s" value="%2$s" />',
                        htmlentities($key, ENT_QUOTES, 'UTF-8', false),
                        htmlentities($value, ENT_QUOTES, 'UTF-8', false)
                    )."\n";
            }

            $output = '<!DOCTYPE html>
<html>
    <head>
        <title>Redirecting...</title>
    </head>
    <body onload="document.forms[0].submit();">
        <form action="%1$s" method="post">
            <p>%3$s</p>
            <p>
                %2$s
                <input type="submit" value="%4$s" />
            </p>
        </form>
    </body>
</html>';
            $output = sprintf(
                $output,
                htmlentities($redirecturl, ENT_QUOTES, 'UTF-8', false),
                $hiddenFields,
                \Yii::t('app', 'Wait. You will be automatically redirecting to payment page...'),
                \Yii::t('app', 'Continue')
            );

            return $output;
        }
        throw new Exception('Payment: redirect wrong format');
    }
    /**
     * Возврат платежей
     */
    public function refund() {
        return false;
    }

    abstract public function isSuccessful();

    abstract public function getMessage();

    abstract public function getTransactionId();

    public function mailNotify($title, $text) {
        \Yii::$app->mailer->compose()
            ->setHtmlBody($text.'<hr>GET:<br/>'.json_encode($_GET).'Post:<br/>'.json_encode($_POST))
            ->setTo(\Yii::$app->params['adminEmail'])->setSubject($this->getName().':'.$title)->send();
    }

    public static function _http($link, $param = array(), &$PageInfo = [])
    {
        $default = array(
            'proxy' => false,
            'proxyList' => array(),
            'HTTPHEADER' => array('Content-Type' => 'text/xml; encoding=utf-8'),
            'redirect' => false,
            'USERAGENT' => \Yii::$app->name,
            'TIMEOUT' => 30,
            'REFERER' => false,
            'POST' => false,
            'SSL' => false,
            'FORBID' => false, //TRUE для принудительного закрытия соединения после завершения его обработки так, чтобы его нельзя было использовать повторно.
        );
        $param = array_merge($default, $param);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link); //задаём url

        if (isset($param['COOKIE'])) {
            if (is_array($param['COOKIE'])) {
                foreach ($param['COOKIE'] as $cookie) {
                    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
                }
            } else {
                curl_setopt($ch, CURLOPT_COOKIE, $param['COOKIE']);
            }
        }

        if (isset($param['COOKIEFILE'])) // Считываем из фаила
            curl_setopt($ch, CURLOPT_COOKIEFILE, $param['COOKIEFILE']);

        if (isset($param['COOKIEJAR'])) // Записываем куки в фаил
            curl_setopt($ch, CURLOPT_COOKIEJAR, $param['COOKIEJAR']);

        curl_setopt($ch, CURLOPT_USERAGENT, $param['USERAGENT']); //подделываем юзер-агента

        if ($param['redirect']) {
            //переходить по редиректам, инициируемым сервером, пока не будет достигнуто CURLOPT_MAXREDIRS (если есть)
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($param['REFERER']) {
            if ($param['REFERER'] === true) $param['REFERER'] = $link;
            curl_setopt($ch, CURLOPT_REFERER, $param['REFERER']);
        }

//        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        if ($param['SSL']) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if ($param['SSL']!==true) {
                curl_setopt($ch, CURLOPT_CAINFO, $param['SSL']);
            }
            // curl_setopt ($ch, CURLOPT_SSLCERT, "("/src/openssl-0.9.6/demos/sign/key.pem");
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if ($param['HTTPHEADER']) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $param['HTTPHEADER']);
        }

        if ($param['FORBID']) {
            curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        }

        if ($param['POST']) {
            if (is_array($param['POST'])) {
                $param['POST'] = http_build_query($param['POST']);
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param['POST']);
        }

        if (!empty($param['AUTH_BASIC'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $param['AUTH_BASIC']);
        }

        //не включать заголовки ответа сервера в вывод
        curl_setopt($ch, CURLOPT_HEADER, false);
        //вернуть ответ сервера в виде строки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, $param['TIMEOUT']);

        if (defined('YII_DEBUG') && YII_DEBUG) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }

        // ПРОКСИ
        if ($param['proxy']) {
            $c = count($param['proxyList']) - 1;
            $prox = $param['proxyList'][rand(0, $c)];
            // указываем адрес
//            $CURLOPT_PROXY = '';
//            $CURLOPT_PROXYUSERPWD = '';
            if (is_array($prox)) {
                $CURLOPT_PROXY = $prox[0];
//                $CURLOPT_PROXYUSERPWD = $prox[1];
            } else {
                $CURLOPT_PROXY = $prox;
            }

            curl_setopt($ch, CURLOPT_PROXY, $CURLOPT_PROXY);

            if (isset($param['CURLOPT_PROXYTYPE'])) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, $param['CURLOPT_PROXYTYPE']);
            }
//            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
//			if ($CURLOPT_PROXYUSERPWD) {
            // если необходимо предоставить имя пользователя и пароль
            //curl_setopt($ch, CURLOPT_PROXYUSERPWD,$CURLOPT_PROXYUSERPWD);
//			}
        }

        $text = curl_exec($ch);

        $PageInfo = curl_getinfo($ch);
        $errMess = '';
        if ($err = curl_errno($ch)) {
            $flag = false;
            $errMess = curl_error($ch);
        } elseif ($PageInfo['http_code'] == 200) {
            $flag = true;
        } else $flag = false;

        curl_close($ch);

        $PageInfo['err'] = $err;
        $PageInfo['flag'] = $flag;
        $PageInfo['errMess'] = $errMess;
        \Yii::trace($text);
        \Yii::trace(json_encode($PageInfo));
        return $text;
    }
}