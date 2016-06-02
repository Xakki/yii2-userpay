<?php
/* @var $this yii\web\View */
/* @var $userPay \app\modules\UserPay\models\UserPay */

$this->title = \Yii::t('app', 'Payment info');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('app', 'Payment history'), 'url' => \yii\helpers\Url::to(['/userpay/default/history'])];
$this->params['breadcrumbs'][] = $this->title;

?>
<h2><?=\Yii::t('app', 'Payment')?> #<?=$userPay->getPrimaryKey()?></h2>

<table class="table table-striped payinfo">
    <tbody>
    <tr>
        <td><?=\Yii::t('app', 'Amount')?>:</td><td><?=$userPay->up_amount?> <?=$userPay->getCurrency()?></td>
    </tr>
    <tr>
        <td><?=\Yii::t('app', 'Buy likes')?>:</td><td><?=$userPay->up_likes?></td>
    </tr>
    <tr>
        <td><?=\Yii::t('app', 'Bonus likes')?>:</td><td><?=$userPay->up_likes_bonus?></td>
    </tr>
    <tr class="<?=($userPay->isStatusOk() ? 'success' : ($userPay->isStatusError() ? 'danger' : ''))?>">
        <td><?=\Yii::t('app', 'Status')?>:</td><td><?=$userPay->getStatus() ?></td>
    </tr>
    <tr>
        <td><?=\Yii::t('app', 'Pay system')?>:</td><td><?= $userPay->getPaySystemName() ?></td>
    </tr>
    <tr>
        <td><?=\Yii::t('app', 'Time create')?>:</td><td><?= Yii::$app->formatter->asDatetime($userPay->up_time_cr) ?></td>
    </tr>
    </tbody>
</table>

<? if ($userPay->up_status == $userPay::STATUS_INIT): ?>
    <a href="/userpay/init/<?=$userPay->getPrimaryKey()?>" class="btn btn-primary m-10" style="color: white; text-decoration: none;"><?=\Yii::t('app', 'Paying in')?> <?=$userPay->getPaySystemName()?></a>
    <a href="/userpay/success/<?=$userPay->getPrimaryKey()?>" class="btn btn-success m-10" style="color: white; text-decoration: none;"><?=\Yii::t('app', 'Already paying, check it.')?></a>
<? endif; ?>

<style>
    .payinfo {
        width: 500px;
    }
</style>
