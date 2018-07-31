<div>
  <div class="waterfall-form__control-block-label">
    {{#placeholder}}{{.}}{{/placeholder}}
    {{^placeholder}}Значение{{/placeholder}}
  </div>
  <div class="waterfall-form__control"> 
    <input type="text" 
      class="js-travel-policy-rule-effect--value" 
      placeholder="Значение"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
  </div>
</div>