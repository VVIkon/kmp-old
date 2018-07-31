<div data-condition="{{dataField}}">
  <div class="waterfall-form__control-block-label">Оператор</div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-cond--operator" 
      placeholder="Оператор"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
  <div class="waterfall-form__control-block-label">
    {{#placeholder}}{{.}}{{/placeholder}}
    {{^placeholder}}Значение{{/placeholder}}
  </div>
  <div class="waterfall-form__control"> 
    <div class="row">
      <div class="col-xs-9">
        <input type="number"
          class="js-travel-policy-rule-cond--control-price" 
          placeholder="Стоимость" 
          value="{{#value}}{{price}}{{/value}}"
          {{#disableEdit}}disabled{{/disableEdit}}
        >
      </div>
      <div class="col-xs-3">
        <input type="text" maxlength="3" 
          class="js-travel-policy-rule-cond--control-currency" 
          placeholder="Валюта" 
          value="{{#value}}{{currency}}{{/value}}"
          {{#disableEdit}}disabled{{/disableEdit}}
        >
      </div>
    </div>
  </div>
</div>