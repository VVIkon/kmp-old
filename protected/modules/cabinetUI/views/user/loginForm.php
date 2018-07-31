<?php
  $cs=Yii::app()->clientScript;
  $cs->registerCssFile(cssUrl() . 'core.css');
?>

<div class="login-form-wrapper">
  <div class="cell">
    <div class="login-form">
      <form action="/cabinetUI/user/login" method="POST" role="form">
        <legend>Вход</legend>
        <input type="text" name="loginForm[login]" placeholder="Логин">
        <input type="password" name="loginForm[pass]" placeholder="Пароль">
        <label><input type="checkbox" name="loginForm[remember]" checked> Запомнить меня</label>
        <?php if ($info!='') { ?>
          <div class="alert-block">
            <?php echo $info; ?>
          </div>
        <?php } ?>
        <button type="submit">Войти</button>
      </form>
    </div>
  </div>
</div>
