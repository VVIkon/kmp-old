<div class="tp-travel-policy-rules container-fluid">
  <div class="row">
    <div class="col-xs-7">

      <div class="tp-travel-policy-rules__list pretty-table pretty-table--striped">
        <table>
          <thead>
            <tr><th>Правила авиаперелетов</th></tr>
          </thead>
          <tbody class="js-travel-policy-rules--avia">
            <!-- avia travel policy rules -->
            <tr class="tp-travel-policy-rules__empty" data-ruleid="{{id}}">
              <td>
                нет правил
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="tp-travel-policy-rules__list pretty-table pretty-table--striped">
        <table>
          <thead>
            <tr><th>Правила проживания</th></tr>
          </thead>
          <tbody class="js-travel-policy-rules--hotel">
            <!-- hotel travel policy rules -->
            <tr class="tp-travel-policy-rules__empty" data-ruleid="{{id}}">
              <td>
                нет правил
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-xs-5">
      <div class="tp-travel-policy-rules__action">
        <button type="button" class="iconed-link-sm iconed-link-sm--add js-travel-policy-rules--action-add-rule">
          Добавить правило
        </button>
      </div>
      <div class="js-travel-policy-rules--form-container">
        <!-- travel policy rules form here -->
        <div class="spinner" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
    </div>
  </div>
</div>