<div class="col-xs-4 custom-field custom-field--date {{#required}}custom-field--required{{/required}}">
  <label class="custom-field__wrapper">
    {{#required}}
      <i class="custom-field__required-mark kmpicon kmpicon-star"></i>
    {{/required}}
    <div class="custom-field__label">
      {{fieldTypeName}}
    </div>
    <div class="custom-field__control">
      <input type="text" class="js-custom-field--control" data-id="{{fieldTypeId}}">
    </div>
  </label>
</div>