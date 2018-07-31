<div data-condition="{{dataField}}">
  <div class="waterfall-form__control-block-label">Оператор</div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-cond--operator" 
      placeholder="оператор"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
  <div class="waterfall-form__control-block-label">
    {{#placeholder}}{{.}}{{/placeholder}}
    {{^placeholder}}Значение{{/placeholder}}
  </div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-cond--control" 
      placeholder="Начните ввод..." 
      value=""
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
</div>