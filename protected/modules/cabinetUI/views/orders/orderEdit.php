<?php

  $fingerprintsFile = Yii::app()->basePath . DS . '..' . DS . 'app' . DS . 'fingerprints.json';
  $fps = json_decode(file_get_contents($fingerprintsFile), true);

  $cs = Yii::app()->clientScript;
  $cs->registerCssFile(cssUrl() . 'core.css' . '?' . $fps['core-css']);
  $cs->registerCssFile(cssUrl() . 'lib/selectize.css' . '?' . $fps['selectize-css']);
  $cs->registerCssFile(cssUrl() . 'lib/clndr.css' . '?' . $fps['clndr-css']);
  $cs->registerCssFile(cssUrl() . 'lib/scrllr.css' . '?' . $fps['scrllr-css']);
  $cs->registerCssFile(cssUrl() . 'lib/jirafize.css' . '?' . $fps['jirafize-css']);
  $cs->registerCssFile(cssUrl() . 'lib/featherlight.css' . '?' . $fps['featherlight-css']);
  $cs->registerCssFile(
     Yii::app()->assetManager->publish(
        Yii::getPathOfAlias('cabinetUI.assets.css').'/cui-orderEdit.css'
     ) . '?' . $fps['orderEdit-css']
  );

  $cs->registerScriptFile(jsUrl().'jquery.min.js' . '?' . $fps['framework']);
  $cs->registerScriptFile(jsUrl().'polyfills.min.js' . '?' . $fps['polyfills']);
  $cs->registerScriptFile(jsUrl().'helpers.min.js' . '?' . $fps['helpers']);
  $cs->registerScriptFile(jsUrl().'lib/ajaxform.min.js' . '?' . $fps['ajaxform-js']);
  $cs->registerScriptFile(jsUrl().'lib/mustache.min.js' . '?' . $fps['mustache-js']);
  $cs->registerScriptFile(jsUrl().'lib/selectize.min.js' . '?' . $fps['selectize-js']);
  $cs->registerScriptFile(jsUrl().'lib/clndr.min.js' . '?' . $fps['clndr-js']);
  $cs->registerScriptFile(jsUrl().'lib/scrllr.min.js' . '?' . $fps['scrllr-js']);
  $cs->registerScriptFile(jsUrl().'lib/clndrize.min.js' . '?' . $fps['clndrize-js']);
  $cs->registerScriptFile(jsUrl().'lib/jirafize.min.js' . '?' . $fps['jirafize-js']);
  $cs->registerScriptFile(jsUrl().'lib/featherlight.min.js' . '?' . $fps['featherlight-js']);

  $cs->registerScriptFile(jsUrl().'core.min.js' . '?' . $fps['core-js']);

  $jsFolder = Yii::app()->assetManager->publish(
    Yii::getPathOfAlias('cabinetUI.assets.js')
  );
  $cs->registerScriptFile($jsFolder . '/cui-orderEdit.min.js' . '?' . $fps['orderEdit-js']);

?>
<div class="top">
  <div class="inner">
    <div id="order-edit-header">
      <div class="ore-breadcrumbs">
        <a href="/">Главная</a>
        <i class="ore-breadcrumbs__delim">&gt;</i>
        <a href="/cabinetUI/orders">Список заявок</a>
        <i class="ore-breadcrumbs__delim">&gt;</i>
        <span id="order-edit-header__ordernum">Заявка</span>
      </div>
      <div class="ore-header">
        <div id="order-edit-header__info" class="ore-header-info">
          <!-- order info -->
          <div class="ore-header-info__spinner" style="display:block;">
            <img src="/app/img/common/loading.gif" alt="Загрузка">
          </div>
        </div>
        <div id="order-edit-header__controls" class="ore-header-controls">
          <!-- controles here -->
        </div>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="inner">
    <div id="tab-headers" class="tab-headers">
      <button type="button" disabled class="tab-headers__link js-tab-header" data-tab="services">
        Услуги
      </button>
      <button type="button" disabled class="tab-headers__link js-tab-header" data-tab="tourists">
        Туристы
      </button>
      <button type="button" disabled class="tab-headers__link js-tab-header" data-tab="payment">
        Оформление
      </button>
    </div>
    <!-- Services tab -->
    <div class="container-fluid content-tab content-tab--padded js-content-tab" data-tab="services">
      <div id="cab-services">
        <!-- services here -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
    <!-- Tourist tab -->
    <div class="container-fluid content-tab content-tab--padded js-content-tab" data-tab="tourists">
      <div id="order-edit-tourists">
        <!-- tourists here -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
      <div class="clearfix"></div>
      <div id="order-edit-tourists--controls" class="ore-tourist-list-controls">
        <!-- annotation & control block -->
      </div>
    </div>
    <!-- Payment tab -->
    <div class="container-fluid content-tab content-tab--padded js-content-tab" data-tab="payment">
      <div class="row">
        <div class="col-xs-2">
          <span class="content-tab__row-label">Заказ</span>
        </div>
        <div id="order-edit-payment--services" class="col-xs-10 ore-payment-services">
          <!-- services here -->
          <div class="spinner" style="display:block;">
            <img src="/app/img/common/loading.gif" alt="Загрузка">
          </div>
        </div>
      </div>
      <div class="row ore-payment__block-divider"></div>
      <div class="row">
        <div class="col-xs-2">
          <span class="content-tab__row-label">Счета</span>
        </div>
        <div id="order-edit-payment--invoices" class="col-xs-10 ore-invoice-list">
          <!-- invoices here -->
          <div class="spinner" style="display:block;">
            <img src="/app/img/common/loading.gif" alt="Загрузка">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-xs-2">
          &nbsp;
        </div>
        <!--
        <div id="order-edit-payment--invoice-actions" class="col-xs-10 ore-invoice-list-actions">
          <span class="iconed-link plus btn-invoices js-ore-show-invoices">Выставить счет</span>
        </div>
        -->
      </div>
    </div>
  </div>
</div>

<!-- TODO: rework -->
<div class="order-footer">
  <div class="inner">
    <div class="add-service-block container-fluid">
      <div class="block-title">ДОБАВИТЬ УСЛУГУ</div>
      <div class="row">
        <div class="col-xs-9 main-list">
            <div class="iconed-link srv-type col-xs-4" data-srv="flight">Перелет</div>
            <div class="iconed-link srv-type col-xs-4" data-srv="accommodation">Проживание</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="visa">Виза</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="train">Ж/Д билеты</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="excursion">Экскурсия</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="insurance">Страховка</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="carrent">Аренда машины</div>
            <div class="iconed-link disabled srv-type col-xs-4" data-srv="transfer">Трансфер</div>
        </div>
        <div class="col-xs-3 add-on">
          <div class="iconed-link disabled srv-type shade col-xs-12" data-srv="custom">Произвольная услуга</div>
        </div>
      </div>
      <div class="custom-req">Нужно что-то другое? Отправьте запрос.</div>
    </div>
  </div>
</div>