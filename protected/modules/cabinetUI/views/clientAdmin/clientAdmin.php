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
        Yii::getPathOfAlias('cabinetUI.assets.css').'/cui-clientAdmin.css'
     ) . '?' . $fps['clientAdmin-css']
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
  $cs->registerScriptFile($jsFolder . '/cui-clientAdmin.min.js' . '?' . $fps['clientAdmin-js']);

?>

<div class="top">
  <div id="client-admin-header" class="inner"></div>
</div>

<div class="content">
  <div class="inner">
    <div id="tab-headers" class="tab-headers">
      <!-- tab headers -->
    </div>
    <!-- loader -->
    <div id="start-screen" class="content-tab js-content-tab active" data-tab="startScreen">
      <div class="spinner" style="display:block;">
        <img src="/app/img/common/loading.gif" alt="Загрузка">
      </div>
    </div>
    <!-- Company info tab -->
    <div id="company-info" class="content-tab js-content-tab tab-company-info" data-tab="companyInfo">
      <div class="submodule-headers">
        <div class="submodule-headers__item active">
          <div id="company-info--header" class="submodule-headers__item-label">
            Редактирование данных компании
          </div>
        </div>
      </div>
      <div id="custom-field-types--toggler" class="partition-header partition-header--switch is-active">
        Настройка дополнительных полей
      </div>
      <div id="custom-field-types">
        <!-- Company custom fields -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
      <div id="user-custom-fields--toggler" class="partition-header partition-header--switch is-active">
        Редактирование дополнительных полей сотрудников
      </div>
      <div id="user-custom-fields">
        <!-- user custom fields -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
    <!-- Travel policy rules tab -->
    <div id="travel-policy" class="content-tab js-content-tab tab-travel-policy" data-tab="travelPolicy">
      <div class="submodule-headers">
        <div class="submodule-headers__item active">
          <div id="travel-policy--header" class="submodule-headers__item-label">
            Редактирование правил корпоративных политик
          </div>
        </div>
      </div>
      <div class="partition-header">
        Редактирование правил услуг
      </div>
      <div id="travel-policy-rules">
        <!-- travel policy rules here -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
    <!-- Authorization rules tab -->
    <div id="authorization-rules" class="container-fluid content-tab js-content-tab tab-authorization-rules" data-tab="authorizationRules">
      <div class="spinner" style="display:block;">
        <img src="/app/img/common/loading.gif" alt="Загрузка">
      </div>
    </div>
  </div>
</div>