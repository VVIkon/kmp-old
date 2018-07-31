<tr class="js-travel-policy-rule" data-ruleid="{{id}}">
  <td>
    <div class="tp-travel-policy-rule">
      <div class="tp-travel-policy-rule__actions">
        {{#actions}}
          {{#edit}}
          <button type="button" class="btn btn-small btn-darkblue js-travel-policy-rule--action-edit">
            Редактировать
          </button>
          {{/edit}}
          {{#view}}
          <button type="button" class="btn btn-small btn-blue js-travel-policy-rule--action-edit">
            Просмотр
          </button>
          {{/view}}
          {{#disable}}
          <button type="button" class="btn btn-small btn-lightred js-travel-policy-rule--action-disable">
            Отключить
          </button>
          {{/disable}}
          {{#enable}}
          <button type="button" class="btn btn-small btn-green js-travel-policy-rule--action-enable">
            Включить
          </button>
          {{/enable}}
        {{/actions}}
      </div>
      {{#name}}{{.}}{{/name}}
      {{^name}}<span class="tp-travel-policy-rule__name-empty">&lt; без названия &gt;</span>{{/name}}
    </div>
  </td>
</tr>