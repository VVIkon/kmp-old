<div class="service-form-tourist js-service-form-tourist" data-touristid="{{touristId}}">
  <div class="col-xs-4 service-form-tourist__info">
    <label>
      <div class="simpletoggler toggler-small {{#attached}}active{{/attached}}">
        <input class="js-service-form-tourist--service-bound" type="checkbox" {{#attached}}checked{{/attached}} {{^allowSave}}disabled{{/allowSave}} hidden name="tourist-{{touristId}}" data-touristid="{{touristId}}">
        <i class="toggler"></i>
        <div class="on-text"><i class="kmpicon kmpicon-success"></i></div>
        <div class="off-text"><i class="kmpicon kmpicon-close"></i></div>
      </div>
      {{firstName}} {{surName}}
    </label>
  </div>
  <div class="col-xs-8 service-form-tourist__extra">
    {{{touristExtra}}}
  </div>
</div>
<div class="clearfix"></div>
