<div class="waterfall-form ci-custom-field-form js-custom-field-form" {{#fieldTypeId}}data-fieldid="{{.}}"{{/fieldTypeId}}>
  {{#fieldTypeId}}
    <div class="col-xs-12">
      <div class="waterfall-form__header">
        <b>Редактирование поля:</b><br>
        {{fieldTypeName}}
      </div>
    </div> 
  {{/fieldTypeId}}
  <form action="javascript:void(0);">
    <div class="waterfall-form__control col-xs-6">
      <label class="juicy-radio">
        <input 
          type="radio" hidden 
          {{#targetType.service}}checked{{/targetType.service}} 
          {{^targetType.service}}{{#fieldTypeId}}disabled{{/fieldTypeId}}{{/targetType.service}}
          name="field-target-type" 
          class="js-custom-field-form--target-type" 
          value="service"
        >
        <i class="control"></i>
        <span class="waterfall-form__control-label">Данные услуги</span>
      </label>
    </div>
    <div class="waterfall-form__control col-xs-6">
      <label class="juicy-radio">
        <input 
          type="radio" hidden 
          {{#targetType.user}}checked{{/targetType.user}}  
          {{^targetType.user}}{{#fieldTypeId}}disabled{{/fieldTypeId}}{{/targetType.user}}
          name="field-target-type" 
          class="js-custom-field-form--target-type"
          value="user"
        >
        <i class="control"></i>
        <span class="waterfall-form__control-label">Данные сотрудника</span>
      </label>
    </div>
    <div class="waterfall-form__control col-xs-12">
      <label>
        <span class="waterfall-form__control-block-label">Название</span>
        <input 
          type="text" 
          class="js-custom-field-form--name" 
          placeholder="Название поля"
          {{#fieldTypeId}}disabled{{/fieldTypeId}}
          {{#fieldTypeName}}value="{{.}}"{{/fieldTypeName}}
        >
      </label>
    </div>
    {{#companyInHolding}}
      <div class="waterfall-form__control col-xs-12">
        <label class="juicy-checkbox">
          <input 
            type="checkbox" 
            hidden
            class="js-custom-field--for-all-holding"
            {{#forAllCompanyInHolding}}checked{{/forAllCompanyInHolding}}
            {{^companyIsMain}}disabled{{/companyIsMain}}
          >
          <i class="control"></i>
          <span class="waterfall-form__control-label">Для всех компаний холдинга</span>
        </label>
      </div>
    {{/companyInHolding}}
    <div class="waterfall-form__control col-xs-12">
      <label>
        <span class="waterfall-form__control-block-label">Тип данных</span>
        <input 
          type="text" 
          class="js-custom-field-form--type" 
          name="field-type" 
          placeholder=""
          {{#fieldTypeId}}disabled{{/fieldTypeId}}
        >
      </label>
    </div>
    <div class="waterfall-form__control col-xs-12 js-custom-field-form--value-options-block {{^valueOptionsList}}is-hidden{{/valueOptionsList}}">
      <label>
        <span class="waterfall-form__control-block-label ci-custom-field-form__option-list-label">
          Список значений
          <label 
            class="ci-custom-field-form__load-options-from-file"
            data-tooltip="Импорт значений из файла"
          >
            <input type="file" hidden 
              class="js-custom-field-form--load-options-from-file"
              {{#blockEdit}}disabled{{/blockEdit}}
            >
            <i class="kmpicon kmpicon-upload"></i>
          </label>
        </span>
        <textarea 
          class="js-custom-field-form--value-list" 
          rows="4" 
          placeholder="Вводите значения по одному на строку или загрузите из файла"
          {{#blockEdit}}disabled{{/blockEdit}}
        >{{#valueOptionsList}}{{.}}{{/valueOptionsList}}</textarea>
      </label>
    </div>
    <div class="waterfall-form__control col-xs-6">
      <label class="juicy-checkbox">
        <input 
          type="checkbox" 
          hidden 
          name="field-required"
          class="js-custom-field-form--required" 
          {{#require}}checked{{/require}}
          {{#blockEdit}}disabled{{/blockEdit}}
        >
        <i class="control"></i>
        <span class="waterfall-form__control-label">Обязательное поле</span>
      </label>
    </div>
    <div class="waterfall-form__control col-xs-6">
      <label class="juicy-checkbox">
        <input 
          type="checkbox" 
          hidden 
          class="js-custom-field-form--editable"
          name="field-editable" 
          {{#modifyAvailable}}checked{{/modifyAvailable}}
          {{#blockEdit}}disabled{{/blockEdit}}
        >
        <i class="control"></i>
        <span class="waterfall-form__control-label">Редактируемое</span>
      </label>
    </div>
    <div class="waterfall-form__control col-xs-12">
      <label class="juicy-checkbox">
        <input 
          type="checkbox" 
          hidden 
          class="js-custom-field-form--reason-fail-tp"
          name="field-tp-violation-reason" 
          {{#reasonFailTP}}checked{{/reasonFailTP}}
          {{#blockEdit}}disabled{{/blockEdit}}
        >
        <i class="control"></i>
        <span class="waterfall-form__control-label">Причина нарушения КП</span>
      </label>
    </div>
  </form>
  <div class="waterfall-form__actions col-xs-12">
    {{#actions}}
      {{#create}}
        <button type="button" class="btn btn-medium btn-green js-custom-field-form--action-save">Создать</button>
      {{/create}}
      {{#save}}
        <button type="button" class="btn btn-medium btn-lime js-custom-field-form--action-save">Сохранить</button>
      {{/save}}
    {{/actions}}
  </div>
</div>