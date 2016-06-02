<?php
/**
 * Created by PhpStorm.
 * User: xakki
 * Date: 26.12.15
 * Time: 14:26
 */
/* @var $this yii\web\View */
/* @var $UserPayModule \app\modules\UserPay\Module */
/* @var $likes int */

$this->title = Yii::t('app', 'Payment');
$this->params['breadcrumbs'][] = $this->title;

$UserPayModule = \Yii::$app->getModule('userpay');
$bonusPercent = $UserPayModule->getBonusPercent($likes);
$likesBonus = $UserPayModule->getBonusLikes($likes);
$likesCost = $UserPayModule->getLikesCost($likes);


if ($likesCost>=$UserPayModule->minCost):
    ?>
    <h4>Ваш заказ:</h4>
    <blockquote style="font-size: 16px;">
        <div>Вы покупаете: <?=$likes?> лайков </div>
        <div>Вы получите бонусом: +<?= $likesBonus ?> лайков (<?= $bonusPercent ?>%)</div>
        <div><b>Итого к оплате: <?=$likesCost?>руб. за <?= ($likesBonus + $likes)?> лайков </b></div>
    </blockquote>

    <h4>Выберите платежную систему:</h4>
    <p class="text-center payment-btn">
        <? foreach ($UserPayModule->paySystems as $ps => $psName):?>
            <a target="_blank" href="/createpay/<?=$ps?>/<?=$likes?>" class="btn btn-primary" onclick="redirectToPayInfo('/createpay/<?=$ps?>/<?=$likes?>?redirect=1')">&nbsp;<b>Купить лайки через <?=$psName?> </b>&nbsp;</a>
        <? endforeach; ?>
    </p>
    <p class="text-left">
        <a href="/dashboard/buy" class="btn btn-default" style="text-decoration: none;">&nbsp;<b>Отмена</b>&nbsp;</a>
        <a href="/userpay/history" class="btn btn-default" style="text-decoration: none;">&nbsp;<b>История платежей</b>&nbsp;</a>
    </p>
<? else : ?>
    <p class="bg-danger">Минимальный заказ должен быть на сумму не меньше  <?=$UserPayModule->minCost?> <?=$UserPayModule->currency?> или <?=($UserPayModule->minCost * $UserPayModule->curs)?> лайков</p>
<? endif; ?>
<script>
function redirectToPayInfo(url) {
    setTimeout(function(){location.href=url;}, 2000);
}
</script>
