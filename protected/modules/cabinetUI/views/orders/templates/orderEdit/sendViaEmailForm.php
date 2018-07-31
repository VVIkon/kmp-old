<div class="send-via-email">
  <label>
    <span class="send-via-email__label">
      {{label}}
    </span>
    <input type="text" 
      class="send-via-email__input js-send-via-email--email" 
      placeholder="email"
      {{#email}}value="{{.}}"{{/email}}
    >
  <label>
  <button type="button" class="send-via-email__action btn btn-medium btn-blue js-send-via-email--action-send">
    Отправить
  </button>
</div>