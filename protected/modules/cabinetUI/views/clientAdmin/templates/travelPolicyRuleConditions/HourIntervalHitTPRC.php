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
        <input type="number" maxlength="2" 
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
  <!-- awating BE for proper implementation -->
  <div class="waterfall-form__control" style="display:none">
    <label class="juicy-checkbox">
      <input type="checkbox" hidden 
        {{#value}}{{#value}}checked{{/value}}{{/value}}
        class="js-travel-policy-rule-cond--control-switch"
        {{#disableEdit}}disabled{{/disableEdit}}
      >
      <i class="control"></i>
      <span class="waterfall-form__control-label">Проверять наличие</span>
    </label>
  </div>
</div>