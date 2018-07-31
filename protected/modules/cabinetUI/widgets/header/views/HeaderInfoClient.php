<?php

  $fingerprintsFile = Yii::app()->basePath . DS . '..' . DS . 'app' . DS . 'fingerprints.json';
  $fps = json_decode(file_get_contents($fingerprintsFile), true);
  $cs = Yii::app()->clientScript;
  $cs->registerCssFile(cssUrl().'widgets/header.css' . '?' . $fps['header-css']);
  $cs->registerScriptFile(jsUrl().'widgets/header.min.js' . '?' . $fps['header-js']);

?>

<header id="header" class="header">
  <div class="inner">
    <div class="header__part header-logo">
      <a href="/"><img src="/app/img/logo.png" alt=""></a>
    </div>
    <div class="header__part header__spacer">
      <i class="header__delim"></i>
    </div>
    <div class="header__part header-usertype-controls js-header--usercontrols">
      <span class="header-usertype-controls__guest">
        <i class="kmpicon kmpicon-warning"></i>
        Загрузка данных...
      </span>
    </div>
    <div class="header__part header__spacer">
      <i class="header__delim"></i>
    </div>
    <div class="header__part header-usercontrols">
      <div class="header-usercontrols-auth js-header--userinfo">
        <!-- user info here -->
      </div>
      <div class="header-usercontrols-exrates js-header-exrates">
        <?php for ($i=0; $i<count($currates); $i++) { ?>
          <?php if ($i !== 0) { ?>
            <i class="header__delim header-usercontrols-exrates__delim"></i>
          <?php } ?>
          <span data-curcode="<?php echo $currates[$i]['isocode']; ?>" class="header-usercontrols-exrates__currency js-header-exrates--currency <? if($currates[$i]['isocode'] == $currency) {echo 'active';} ?>">
            <?php echo $currates[$i]['code']; ?>:
            <span class="header-usercontrols-exrates__rate"><?php echo $currates[$i]['rate']; ?></span>
          </span>
        <?php } ?>
      </div>
    </div>
    <div class="header__part header-helpdesk">
      <span class="header-helpdesk__phone"><?= e(param('servicePhone')['phone']); ?></span>
      <span class="header-helpdesk__phone">8 800 250 17 07</span>
    </div>
    <div class="header__part header-actions js-header--actions">
      <!-- actions -->
    </div>
  </div>
</header>
<nav class="cmenu">
  <div class="inner">
    <ul id="main-menu">
      <!-- menu -->
    </ul>
  </div>
</nav>
