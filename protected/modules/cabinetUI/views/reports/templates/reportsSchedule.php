<div class="reports-schedule">
  <div class="pretty-table pretty-table--striped">
    <table>
      <thead>
        <tr>
          <th colspan="4">
            Расписание отправки отчетов 
            {{#company}}
              {{#name}}
                для {{.}}
              {{/name}}
              {{^name}}
                по всем компаниям
              {{/name}}
            {{/company}}
          </th>
        </tr>
      </thead>
      <tbody>
        {{#schedule}}
          <tr class="js-reports-schedule--report" data-taskid="{{taskId}}">
            <td>{{taskParams.reportTypeName}}</td>
            <td>{{periodName}}</td>
            <td>{{taskParams.email}}</td>
            <td class="reports-schedule__task-actions">
              <button type="button" class="btn btn-small btn-lightred js-reports-schedule--drop-task">
                Удалить
              </button>
            </td>
          </tr>
        {{/schedule}}
        {{^schedule}}
          <tr>
            <td colspan="4" class="reports-schedule__empty">
              Нет настроенных отчетов
            </td>
          </tr>
        {{/schedule}}
      </tbody>
    </table>
  </div>
</div>