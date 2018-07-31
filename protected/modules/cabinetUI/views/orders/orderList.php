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
        Yii::getPathOfAlias('cabinetUI.assets.css').'/cui-orderList.css'
     ) . '?' . $fps['orderList-css']
  );

  $cs->registerScriptFile(jsUrl().'jquery.min.js' . '?' . $fps['framework']);
  $cs->registerScriptFile(jsUrl().'polyfills.min.js' . '?' . $fps['polyfills']);
  $cs->registerScriptFile(jsUrl().'helpers.min.js' . '?' . $fps['helpers']);
  $cs->registerScriptFile(jsUrl().'lib/mustache.min.js' . '?' . $fps['mustache-js']);
  $cs->registerScriptFile(jsUrl().'lib/selectize.min.js' . '?' . $fps['selectize-js']);
  $cs->registerScriptFile(jsUrl().'lib/animate-shadow.min.js' . '?' . $fps['animate-shadow-js']);
  $cs->registerScriptFile(jsUrl().'lib/pulse.min.js' . '?' . $fps['pulse-js']);
  $cs->registerScriptFile(jsUrl().'lib/clndr.min.js' . '?' . $fps['clndr-js']);
  $cs->registerScriptFile(jsUrl().'lib/scrllr.min.js' . '?' . $fps['scrllr-js']);
  $cs->registerScriptFile(jsUrl().'lib/clndrize.min.js' . '?' . $fps['clndrize-js']);
  $cs->registerScriptFile(jsUrl().'lib/jirafize.min.js' . '?' . $fps['jirafize-js']);
  $cs->registerScriptFile(jsUrl().'lib/featherlight.min.js' . '?' . $fps['featherlight-js']);

  $cs->registerScriptFile(jsUrl().'core.min.js' . '?' . $fps['core-js']);

  $jsFolder = Yii::app()->assetManager->publish(
    Yii::getPathOfAlias('cabinetUI.assets.js')
  );
  $cs->registerScriptFile($jsFolder . '/cui-orderList.min.js' . '?' . $fps['orderList-js']);

?>
<div class="top"></div>
<div class="content">
  <div class="inner">
    <div class="orl-wrapper">
      <div class="orl-filter">
        <div class="orl-filter__wrapper">
          <div class="orl-filter__add-new-order js-orl--add-new-order">
            <span class="iconed-link plus">Новая заявка</span>
          </div>
          <div id="order-list-filter" class="orl-filter-control">
            <div class="orl-filter-control__statusbar orl-filter-statusbar js-orl-filter-statusbar">
              <span class="orl-filter-statusbar__placeholder js-orl-filter-statusbar--placeholder">Фильтр поиска</span>
            </div>
            <div class="orl-filter-control__selectors orl-filter-selectors js-orl-filter--selectors">
              <!--filter controls here -->
              <div class="spinner">
                <img src="/app/img/common/loading.gif" alt="Загрузка">
              </div>
            </div>
            <div class="orl-filter-control__actions">
              <button class="btn btn-medium btn-reset js-orl-filter--action-reset">
                  <i class="kmpicon kmpicon-roundarrow"></i>
                  Сбросить
              </button>
              <button class="btn btn-medium btn-find js-orl-filter--action-find">
                  <i class="kmpicon kmpicon-search"></i>
                  Найти
              </button>
            </div>
          </div>
        </div>
      </div>
      <div id="order-list-sorting" class="orl-sorting">
        <table>
          <tbody>
            <tr>
              <td class="orl-sorting__amount js-orl-sorting--amount">0</td>
              <td class="orl-sorting__amount-label js-orl-sorting--amount-label">найденных заявок</td>
              <td class="orl-sorting__actions-label">сортировать:</td>
              <td class="orl-sorting__actions js-orl-sorting--actions">
                <!-- sort options here -->
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div id="order-list-items" class="orl-list">
        <div class="spinner">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
      <div id="order-list-actions" class="orl-list-actions">
        <button type="button" class="btn btn-medium orl-list-actions__show-more js-orl-list-actions--show-more">Показать еще</button>
      </div>
    </div>
  </div>
</div>
<div class="orl-footer"></div>
