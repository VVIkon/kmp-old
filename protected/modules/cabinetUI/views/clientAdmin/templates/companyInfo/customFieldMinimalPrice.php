<tr class="js-custom-field-type" data-fieldid="{{fieldTypeId}}">
  <td>
    <div class="ci-custom-field-type">
      <div class="ci-custom-field-type__actions">
        {{#actions}}
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
          {{#add}}
          <button type="button" class="btn btn-small btn-lime js-custom-field-type--action-add-minimal-price">
            Добавить
          </button>
          {{/add}}
        {{/actions}}
      </div>
      Минимальная стоимость предложения
    </div>
  </td>
</tr>