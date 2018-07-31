<div class="waterfall-form tp-travel-policy-form js-travel-policy-form" {{#id}}data-ruleid="{{.}}"{{/id}}>
  {{#id}}
  <div class="col-xs-12">
    <div class="waterfall-form__header">
      <b>Правило:</b> 
      {{#name}}{{.}}{{/name}}
      {{^name}}&lt; без названия &gt;{{/name}}
    </div>
  </div>
  {{/id}}
  <form action="javascript:void(0);" class="js-travel-policy-form--rule">
  </form>
  <div class="waterfall-form__actions col-xs-12">
    <button type="button" 
      class="btn btn-medium btn-lime js-travel-policy-form--action-save"
      {{#disableEdit}}disabled{{/disableEdit}}
    >
      Сохранить
    </button>
  </div>
</div>