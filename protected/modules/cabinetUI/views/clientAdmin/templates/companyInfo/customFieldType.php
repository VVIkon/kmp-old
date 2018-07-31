<tr class="js-custom-field-type" data-fieldid="{{fieldTypeId}}">
  <td>
    <div class="ci-custom-field-type">
      <div class="ci-custom-field-type__actions">
        {{#actions}}
          {{#edit}}
          <button type="button" class="btn btn-small btn-darkblue js-custom-field-type--action-edit">
            Редактировать
          </button>
          {{/edit}}
          {{#view}}
          <button type="button" class="btn btn-small btn-blue js-custom-field-type--action-edit">
            Просмотр
          </button>
          {{/view}}
          {{#disable}}
          <button type="button" class="btn btn-small btn-lightred js-custom-field-type--action-disable">
            Отключить
          </button>
          {{/disable}}
          {{#enable}}
          <button type="button" class="btn btn-small btn-green js-custom-field-type--action-enable">
            Включить
          </button>
          {{/enable}}
        {{/actions}}
      </div>
      {{fieldTypeName}}
    </div>
  </td>
</tr>