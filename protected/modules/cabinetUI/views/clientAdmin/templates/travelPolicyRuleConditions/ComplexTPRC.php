<div data-condition="{{dataField}}">
  <div class="waterfall-form__control-block-label">
    {{#fieldPlaceholder}}{{.}}{{/fieldPlaceholder}}
    {{^fieldPlaceholder}}Поле{{/fieldPlaceholder}}
  </div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="tp-travel-policy-form-block__long-name-select js-travel-policy-rule-cond--field" 
      placeholder="Выберите из списка" 
      value="{{#value}}{{.}}{{/value}}"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
  <div class="waterfall-form__control-block-label">Оператор</div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-cond--operator" 
      placeholder="оператор"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
  <div class="js-travel-policy-rule-cond--value-container">
    <!-- value here -->
  </div>
</div>