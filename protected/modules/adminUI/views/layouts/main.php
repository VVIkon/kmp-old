<?php

  $assetsPath = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('adminUI.assets'));
  $manifest = json_decode(file_get_contents(Yii::getPathOfAlias('adminUI.assets') . '/manifest.json'), true);

  $cs = Yii::app()->clientScript;

  $cs->registerCssFile($assetsPath . '/' . $manifest['adminui.css']);

  $cs->registerScriptFile($assetsPath . '/' . $manifest['polyfills.js'], CClientScript::POS_END);
  $cs->registerScriptFile($assetsPath . '/' . $manifest['vendor.js'], CClientScript::POS_END);
  $cs->registerScriptFile($assetsPath . '/' . $manifest['adminui.js'], CClientScript::POS_END);

  /* couln't manage manifest in right order 
  foreach ($manifest as $asset => $assetFile) {
    if (preg_match('/.+\.css$/u', $asset)) {
        $cs->registerCssFile($assetsPath . '/' . $assetFile);
    } else if (preg_match('/.+\.js$/u', $asset)) {
        $cs->registerScriptFile($assetsPath . '/' . $assetFile, CClientScript::POS_END);
    }
  }
  */

  // render content

  echo $content;
?>