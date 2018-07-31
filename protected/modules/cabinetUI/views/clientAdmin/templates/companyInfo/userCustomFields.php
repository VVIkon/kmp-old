<div class="ci-user-custom-fields container-fluid">
  <div class="row">
    <div class="col-xs-6">
      <div class="ci-user-suggest">
        <label>
          <span class="ci-user-suggest__label">Выберите сотрудника</span>
          <span class="ci-user-suggest__control">
            <input type="text" class="js-user-custom-fields--user-suggest" placeholder="Фильтр">
          </span>
        </label>
      </div>
      <div class="ci-user-custom-fields__list">
        <div class="pretty-table pretty-table--striped">
          <table>
            <tbody class="js-user-custom-fields--user-list">
              <!-- user custom fields -->
              <tr>
                <td>
                  <div class="spinner" style="display:block;">
                    <img src="/app/img/common/loading.gif" alt="Загрузка">
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-xs-6 js-user-custom-fields--form-container">
    </div>
  </div>
</div>