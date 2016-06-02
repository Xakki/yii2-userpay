<?php
/* @var $this yii\web\View */
/* @var $data \app\modules\UserPay\models\UserPay[] */

$this->title = Yii::t('app', 'Payment history');
$this->params['breadcrumbs'][] = $this->title;

?>
<h2>Информация о платежах</h2>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Id</th>
            <th><?=Yii::t('app', 'Pay system')?></th>
            <th><?=Yii::t('app', 'Time create')?></th>
            <th><?=Yii::t('app', 'Time update')?></th>
            <th><?=Yii::t('app', 'Amount')?></th>
            <th><?=Yii::t('app', 'Status')?></th>
            <th><?=Yii::t('app', 'Likes')?></th>
            <th><?=Yii::t('app', 'Bonus')?></th>
            <th> </th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($data as $userPay): ?>
        <tr class="<?=($userPay->isStatusOk() ? 'success' : ($userPay->isStatusError() ? 'danger' : ''))?>">
            <td><?=$userPay->getPrimaryKey()?></td>
            <td><?=$userPay->getPaySystemName()?></td>
            <td><?=Yii::$app->formatter->asDatetime($userPay->up_time_cr)?></td>
            <td><?=($userPay->up_time_up ? Yii::$app->formatter->asDatetime($userPay->up_time_up) : '')?></td>
            <td><?=$userPay->up_amount?> <?=$userPay->getCurrency()?></td>
            <td><?=$userPay->getStatus()?></td>
            <td><?=$userPay->up_likes?></td>
            <td><?=$userPay->up_likes_bonus?></td>
            <td><a class="btn btn-sm btn-primary m-r-5" href="/userpay/info/<?=$userPay->getPrimaryKey()?>"><?=Yii::t('app', 'Info')?></a></td>
        </tr>
    </tbody>
    <? endforeach; ?>
</table>