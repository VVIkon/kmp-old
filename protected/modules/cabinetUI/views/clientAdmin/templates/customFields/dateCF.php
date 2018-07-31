<div 
  class="row custom-field custom-field--date {{#required}}custom-field--required{{/required}} js-custom-field"
  data-idx="{{idx}}"
  data-fieldtypeid={{fieldTypeId}}
  {{#fieldId}}data-fieldid="{{.}}"{{/fieldId}}
>
  <div class="col-xs-4 custom-field__wrapper">
    <label>
      {{#required}}
        <i class="custom-field__required-mark kmpicon kmpicon-star"></i>
      {{/required}}
      <div class="custom-field__label">
        {{fieldTypeName}}
      </div>
      <div class="custom-field__control">
        <input type="text" class="js-custom-field--control" data-id="{{fieldTypeId}}" {{^active}}disabled{{/active}}>
      </div>
    </label>
  </div>
  <div class="col-xs-3 col-xs-offset-5 custom-field__actions">
    {{#active}}
    <button type="button" class="btn btn-small btn-lightred js-custom-field--action-disable">
      Отключить
    </button>
    {{/active}}
    {{^active}}
    <button type="button" class="btn btn-small btn-green js-custom-field--action-enable">
      Включить
    </button>
    {{/active}}
  </div>
</div>