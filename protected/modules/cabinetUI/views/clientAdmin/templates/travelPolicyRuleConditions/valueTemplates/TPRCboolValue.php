<div>
  <div class="waterfall-form__control-block-label">
    {{#placeholder}}{{.}}{{/placeholder}}
    {{^placeholder}}Значение{{/placeholder}}
  </div>
  <div class="waterfall-form__control"> 
    <label class="juicy-toggler juicy-toggler--yes-no">
      <input type="checkbox" hidden checked 
        class="js-travel-policy-rule-cond--value"
        {{#disableEdit}}disabled{{/disableEdit}}
      >
      <i class="juicy-toggler__toggler"></i>
      <div class="juicy-toggler__on-text">да</div>
      <div class="juicy-toggler__off-text">нет</div>
    </label>
  </div>
</div>