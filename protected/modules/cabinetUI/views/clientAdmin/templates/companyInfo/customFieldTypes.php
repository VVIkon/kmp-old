<div class="ci-custom-field-types container-fluid">
  <div class="row">
    <div class="col-xs-7">
      <div class="ci-custom-field-types__list pretty-table pretty-table--striped">
        <table>
          <thead>
            <tr><th>Дополнительные поля услуги</th></tr>
          </thead>
          <tbody class="js-custom-field-types--service">
            <!-- sevice custom fields -->
            <tr class="ci-custom-field-types__empty" data-fieldid="{{fieldTypeId}}">
              <td>
                нет дополнительных полей
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="ci-custom-field-types__list pretty-table pretty-table--striped">
        <table>
          <thead>
            <tr><th>Дополнительные поля пользователей</th></tr>
          </thead>
          <tbody class="js-custom-field-types--user">
            <!-- company custom fields -->
            <tr class="ci-custom-field-types__empty" data-fieldid="{{fieldTypeId}}">
              <td>
                нет дополнительных полей
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-xs-5">
      <div class="ci-custom-field-types__action">
        <button type="button" class="iconed-link-sm iconed-link-sm--add js-custom-field-types--action-add-field">
          Добавить дополнительное поле
        </button>
      </div>
      <div class="js-custom-field-types--form-container">
        <!-- custom fields form here -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
  </div>
</div>