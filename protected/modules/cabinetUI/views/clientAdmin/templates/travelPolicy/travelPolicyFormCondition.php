<div class="waterfall-form__control col-xs-12 js-travel-policy-form-condition">
  <div class="waterfall-form__group tp-travel-policy-form-block">
    <div class="tp-travel-policy-form-block__actions">
      <div class="tp-travel-policy-form-block__action">
        <button type="button" 
          class="btn btn-small btn-error js-travel-policy-form-condition--action-remove"
          data-tooltip="Удалить условие"
          {{#disableEdit}}disabled{{/disableEdit}}
        ><i class="kmpicon kmpicon-close"></i></button>
      </div>
      <div class="tp-travel-policy-form-block__remove-confirm js-travel-policy-form-condition--remove-confirm">
        Действительно удалить условие?
        <button type="button" 
          class="btn btn-small btn-error js-travel-policy-form-condition--action-remove-completely"
          {{#disableEdit}}disabled{{/disableEdit}}
        >Да</button>
      </div>
    </div>
    <div class="waterfall-form__control-block-label">Условие</div>
    <div class="waterfall-form__control">
      <input type="text" 
        class="tp-travel-policy-form-block__long-name-select js-travel-policy-form-condition--cond" 
        placeholder="условие"
        {{#disableEdit}}disabled{{/disableEdit}}
      >
    </div>
    <div class="js-travel-policy-form-condition--body"> 
      <!-- condition body -->
    </div>
  </div>
</div>