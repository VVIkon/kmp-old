<?php
$root=realpath(dirname(__FILE__).'/../..');

Yii::setPathOfAlias('root', $root);
return [
  //  'bootstrap' => 'ext.bootstrap',
  //  'menu'      => 'application.menu',
//    'widgets'   => 'application.widgets',
//    'tours'     => 'application.modules.tour.views.tour',
//    'orders'    => 'application.modules.order.views.partials',
    'kmpfrwk'   => 'application.vendors.kmp.framework',
    'JsonStreamingParser' => 'application.vendors.JsonStreamingParser'
];
