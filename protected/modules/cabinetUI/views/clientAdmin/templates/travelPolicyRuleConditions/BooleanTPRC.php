<div data-condition="{{dataField}}">
  {{#placeholder}}
  <div class="waterfall-form__control-block-label">{{.}}</div>
  {{/placeholder}}
  <div class="waterfall-form__control"> 
    <label class="juicy-toggler juicy-toggler--yes-no">
      <input type="checkbox" hidden 
        class="js-travel-policy-rule-cond--control"
        {{#value}}checked{{/value}} 
        {{#disableEdit}}disabled{{/disableEdit}}
      >
      <i class="juicy-toggler__toggler"></i>
      <div class="juicy-toggler__on-text">да</div>
      <div class="juicy-toggler__off-text">нет</div>
    </label>
  </div>
</div>