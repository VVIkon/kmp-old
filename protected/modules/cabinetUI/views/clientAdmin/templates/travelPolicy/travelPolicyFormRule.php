<div class="js-travel-policy-form-rule">
  <div class="waterfall-form__control col-xs-12">
    <label>
      <span class="waterfall-form__control-block-label">Название</span>
      <input 
        type="text" 
        class="js-travel-policy-form-rule--name" 
        placeholder="Название правила" 
        {{#name}}value="{{.}}"{{/name}}
        {{#disableEdit}}disabled{{/disableEdit}}
      >
    </label>
  </div>
  {{#companyInHolding}}
    <div class="waterfall-form__control col-xs-12">
      <label class="juicy-checkbox">
        <input 
          type="checkbox" 
          hidden
          class="js-travel-policy-form-rule--for-all-holding"
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
      <span class="waterfall-form__control-block-label">Тип услуги</span>
      <input 
        type="text" 
        class="js-travel-policy-form-rule--service-type" 
        placeholder="Выберите тип"
        {{#disableEdit}}disabled{{/disableEdit}}
      >
    </label>
  </div>
  <div class="waterfall-form__control col-xs-12">
    <label>
      <span class="waterfall-form__control-block-label">Область применения</span>
      <input 
        type="text" 
        class="js-travel-policy-form-rule--type" 
        placeholder="Область применения"
        {{#disableEdit}}disabled{{/disableEdit}}
      >
    </label>
  </div>
  <!-- conditions block -->
  <div class="waterfall-form__divider">
    <div class="waterfall-form__divider-wrapper">
      Условия
    </div>
  </div>
  <div class="js-travel-policy-form-rule--conditions">
    <!-- travel policy rule conditions here -->
  </div>
  <div class="waterfall-form__control col-xs-12">
    <button 
      type="button" disabled
      class="iconed-link-sm iconed-link-sm--add js-travel-policy-form-rule--action-add-condition"
    >
      Добавить условие
    </button>
  </div>
  <!-- effects block -->
  <div class="waterfall-form__divider">
    <div class="waterfall-form__divider-wrapper">
      Действия
    </div>
  </div>
  <div class="js-travel-policy-form-rule--effects">
    <!-- travel policy rule effects (actions) here -->
  </div>
  <div class="waterfall-form__control col-xs-12">
    <button 
      type="button" disabled
      class="iconed-link-sm iconed-link-sm--add js-travel-policy-form-rule--action-add-effect"
    >
      Добавить действие
    </button>
  </div>
</div>