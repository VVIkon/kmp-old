<div class="lightbox-block__title">Документы</div>
<div class="lightbox-block__content js-ore-documents--content">
  <div class="ore-documents-list pretty-table-right pretty-table--striped">
    <table>
      <thead>
        <tr>
          <th colspan="4"></th>
          <th colspan="2">Название</th>
          <th>Комментарий</th>
        </tr>
      </thead>
      <tbody>
        {{{documents}}}
      </tbody>
    </table>
  </div>
  <div class="ore-documents-add">
    <form class="ore-documents-add__wrapper js-ore-documents--upload" action="/cabinetUI/orders/addDocument" method="POST" role="form">
      <table class="ore-documents-add__panel">
        <tr>
          <td class="ore-documents-add__upload">
            <label class="js-ore-documents--upload-control">
              <input class="ore-documents-add__upload-field js-ore-documents--upload-field" type="file" hidden name="doc">
              <i class="kmpicon kmpicon-upload"></i><span class="js-ore-documents--upload-label">Добавить документ</span>
            </label>
            <div class="ore-documents-add__upload-progress js-ore-documents--upload-progress" style="display:none;">
              <div class="ore-documents-add__upload-progress-bar js-ore-documents--upload-progress-bar"></div>
              <div class="ore-documents-add__upload-progress-text js-ore-documents--upload-progress-text"></div>
            </div>
          </td>
          <td class="ore-documents-add__delim">
            <i></i>
          </td>
          <td class="ore-documents-add__comment">
            <input class="js-ore-documents--upload-comment" type="text" name="comment" placeholder="Комментарий к документу">
          </td>
      </table>
    </form>
  </div>
  <div class="ore-documents-actions js-ore-documents--actions">
    <button type="button" class="btn btn-medium btn-confirm js-ore-documents--upload-confirm">
      <i class="kmpicon kmpicon-confirm"></i>
      Загрузить
    </button>
    <button type="button" class="btn btn-medium btn-reset js-ore-documents--upload-decline">
      <i class="kmpicon kmpicon-roundarrow"></i>
      Отмена
    </button>
    <span class="js-ore-documents--send-form">
      <!-- send via email form here -->
    </span>
  </div>
</div>
