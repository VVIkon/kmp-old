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
        Yii::getPathOfAlias('cabinetUI.assets.css').'/cui-reports.css'
     ) . '?' . $fps['reports-css']
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
  $cs->registerScriptFile($jsFolder . '/cui-reports.min.js' . '?' . $fps['reports-js']);

?>

<div class="top"></div>

<div class="content">
  <div class="inner">
    <!-- Reports -->
    <div class="content-tab active tab-reports">
      <div class="submodule-headers">
        <div class="submodule-headers__item active">
          <div class="submodule-headers__item-label">
            Отчеты
          </div>
        </div>
      </div>
      <div id="reports">
        <!-- Reports -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
  </div>
</div>