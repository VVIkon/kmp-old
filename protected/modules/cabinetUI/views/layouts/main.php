<?php

  $fingerprintsFile = Yii::app()->basePath . DS . '..' . DS . 'app' . DS . 'fingerprints.json';
  $fps = json_decode(file_get_contents($fingerprintsFile), true);
  /** @todo move scripts that are not libs to bottom */
  $cs = Yii::app()->clientScript;

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>KMP Travel</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
</head>
<body>

<?php

  $this->widget('cabinetUI.widgets.header.HeaderInfo');
  echo $content;

?>

</body>
</html>
