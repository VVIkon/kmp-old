<div class="waterfall-form reports-form">
  <div class="col-xs-12">
    <div class="waterfall-form__header">
      Создание отчета
    </div>
  </div>
  <form action="javascript:void(0);" class="js-reports-form">
    {{#allowCompanySelect}}
      <div class="waterfall-form__control col-xs-12">
        <label>
          <span class="waterfall-form__control-block-label">Компания</span>
          <input type="text" 
            class="js-reports-form--company" 
            placeholder="Начните ввод..."
          >
        </label>
      </div>
      <div class="waterfall-form__control col-xs-12">
        <label class="juicy-checkbox">
          <input type="checkbox" hidden 
            class="js-reports-form--all-companies-switch"
          >
          <i class="control"></i>
          <span class="waterfall-form__control-label">Отчет по всем компаниям</span>
        </label>
      </div>
    {{/allowCompanySelect}}
    <div class="waterfall-form__control col-xs-12">
      <label>
        <span class="waterfall-form__control-block-label">Тип отчета</span>
        <input type="text" 
          class="js-reports-form--report-type" 
          placeholder="Тип отчета"
        >
      </label>
    </div>
    <div class="js-reports-form--report-type-config">
      <!-- if any special settings for type, render here -->
    </div>
    <div class="waterfall-form__control col-xs-12">
      <div class="waterfall-form__control-block-label">Период отчета</div>
      <div class="row">
        <div class="col-xs-5">
          <input type="text"
            class="js-reports-form--date-from"
            placeholder="с:"
          >
        </div>
        <div class="col-xs-1 waterfall-form__control-aside-label reports-form__arrow">
          <i class="kmpicon kmpicon-arrow-right"></i>
        </div>
        <div class="col-xs-5">
          <input type="text"
            class="js-reports-form--date-to"
            placeholder="по:"
          >
        </div>
      </div>
    </div>
    <div class="waterfall-form__control col-xs-12">
      <label>
        <span class="waterfall-form__control-block-label">E-mail для отправки</span>
        <input type="text" 
          class="js-reports-form--email" 
          placeholder="mailbox@kmp.travel"
          {{#email}}value="{{.}}"{{/email}}
        >
      </label>
    </div>
    <div class="waterfall-form__control col-xs-12">
      <span class="waterfall-form__control-label">Формат отчета: &nbsp;</span>
      <label class="juicy-radio">
        <input type="radio" hidden checked name="report-file-type" class="js-reports-form--file-type" value="xlsx">
        <i class="control"></i>
        <span class="waterfall-form__control-label">Excel &nbsp;</span>
      </label>
      <label class="juicy-radio">
        <input type="radio" hidden name="report-file-type" class="js-reports-form--file-type" value="pdf">
        <i class="control"></i>
        <span class="waterfall-form__control-label">PDF &nbsp;</span>
      </label>
    </div>
    <div class="waterfall-form__actions col-xs-12">
      <button type="button" class="btn btn-medium btn-lime js-travel-policy-form--action-save">
        Сформировать
      </button>
      <span class="js-travel-policy-form--action-save-loader"><!-- preloader here--></span>
    </div>
    <div class="waterfall-form__divider col-xs-12">
      <div class="waterfall-form__divider-wrapper">
        Запланировать отправку отчета
      </div>
    </div>
    <div class="waterfall-form__control col-xs-12">
      <label>
        <span class="waterfall-form__control-block-label">Периодичность</span>
        <input type="text" 
          class="js-reports-form--period" 
          placeholder="Период отправки"
        >
      </label>
    </div>
    <div class="js-reports-form--period-detail-container">
      <!-- period detail -->
    </div>
    <div class="waterfall-form__actions col-xs-12">
      <button type="button" class="btn btn-medium btn-darkblue js-travel-policy-form--action-add-task">
        Создать задачу
      </button>
      <span class="js-travel-policy-form--action-add-task-loader"><!-- preloader here--></span>
    </div>
  </form>
</div>