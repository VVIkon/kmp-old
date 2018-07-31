{{#orderNumber}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--ordernumber">Номер заявки</label>
    <input type="text" id="orl-filter--ordernumber">
  </div>
{{/orderNumber}}
{{#startDate}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block orl-filter-selectors__labeled-block--dates">
    <label for="orl-filter--start-date-from">Дата заезда</label>
    <input type="text" id="orl-filter--start-date-from">
    <span> - </span>
    <input type="text" id="orl-filter--start-date-to">
  </div>
{{/startDate}}
{{#country}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--country">Страна</label>
    <!-- class .olSelect - custom class not to interfer width .select, processed by selectize globally in main.js -->
    <select id="orl-filter--country" class="orl-filter-selectors__selectize sel-country">
      <option></option>
    </select>
  </div>
{{/country}}
{{#modificationDate}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block orl-filter-selectors__labeled-block--dates">
    <label for="orl-filter--modification-date-from">Дата модификации</label>
    <input type="text" id="orl-filter--modification-date-from">
    <span> - </span>
    <input type="text" id="orl-filter--modification-date-to">
  </div>
{{/modificationDate}}
{{#city}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--city">Город</label>
    <!-- class .olSelect - custom class not to interfer width .select, processed by selectize globally in main.js -->
    <select id="orl-filter--city" class="orl-filter-selectors__selectize sel-city">
      <option></option>
    </select>
  </div>
{{/city}}
{{#company}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--company">Компания</label>
    <select id="orl-filter--company" class="orl-filter-selectors__selectize" placeholder="название или ИНН">
      <option></option>
    </select>
  </div>
{{/company}}
{{#manager}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--creator">Менеджер</label>
    <select id="orl-filter--creator" class="orl-filter-selectors__selectize">
      <option></option>
    </select>
  </div>
{{/manager}}
{{#orderStatus}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--status">Статус</label>
    <select id="orl-filter--status" class="orl-filter-selectors__selectize">
      <option></option>
    </select>
  </div>
{{/orderStatus}}
{{#tourleader}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--tourleader">Турлидер</label>
    <select id="orl-filter--tourleader" class="orl-filter-selectors__selectize">
      <option></option>
    </select>
  </div>
{{/tourleader}}
{{#offline}}
  <div class="col-xs-6 orl-filter-selectors__labeled-block">
    <label for="orl-filter--offline">Тип заявки</label>
    <select id="orl-filter--offline" class="orl-filter-selectors__selectize" placeholder="Любой"></select>
  </div>
{{/offline}}
{{#archive}}
  <div class="col-xs-12 orl-filter-selectors__archive">
    <label class="juicy-checkbox">
      <input type="checkbox" hidden id="orl-filter--archived" class="zayavkaFilter-control-archive">
      <i class="control"></i>
      Искать в архиве
    </label>
  </div>
{{/archive}}
<div class="clearfix"></div>
<div class="orl-filter-selectors__actions">
  <button class="btn btn-medium btn-reset js-orl-filter--action-reset">
      <i class="kmpicon kmpicon-roundarrow"></i>
      Сбросить
  </button>
  <button class="btn btn-medium btn-find js-orl-filter--action-find">
      <i class="kmpicon kmpicon-search"></i>
      Найти
  </button>
</div>