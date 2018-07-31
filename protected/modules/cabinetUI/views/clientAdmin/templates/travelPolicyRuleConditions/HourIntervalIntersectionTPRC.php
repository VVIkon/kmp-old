<div data-condition="{{dataField}}">
  <div class="waterfall-form__control-block-label">Оператор</div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-cond--operator" 
      placeholder="оператор"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
  <div class="waterfall-form__control"> 
    <div class="row">
      <div class="col-xs-6">
        <div class="waterfall-form__control-block-label">От (час):</div>
        <input type="number" 
          class="js-travel-policy-rule-cond--control-from" 
          placeholder="Час" 
          value="{{#value}}{{fromHour}}{{/value}}"
          {{#disableEdit}}disabled{{/disableEdit}}
        >
      </div>
      <div class="col-xs-6">
        <div class="waterfall-form__control-block-label">До (час):</div>
        <input type="number" maxlength="2" 
          class="js-travel-policy-rule-cond--control-to" 
          placeholder="Час" 
          value="{{#value}}{{toHour}}{{/value}}" 
          {{#disableEdit}}disabled{{/disableEdit}}
        >
      </div>
    </div>
  </div>
  <div class="waterfall-form__control"> 
    <div class="row">
      <div class="col-xs-6">
        <div class="waterfall-form__control-block-label">Время (в минутах):</div>
        <input type="number" maxlength="2" 
          class="js-travel-policy-rule-cond--control-time" 
          placeholder="Продолжительность" 
          value="{{#value}}{{value}}{{/value}}"
          {{#disableEdit}}disabled{{/disableEdit}}
        >
      </div>
    </div>
  </div>
</div>