<tr class="js-ore-documents--document" data-documentid="{{documentId}}">
  <td class="ore-documents-list__check">
    <label class="juicy-checkbox">
      <input type="checkbox" hidden 
        class="js-ore-documents--document-select" 
        data-documentid="{{documentId}}"
        data-name="{{fileName}}"
      >
      <i class="control"></i>
    </label>
  </td>
  <td class="ore-documents-list__delim">
    <i></i>
  </td>
  <td class="ore-documents-list__download">
    <a class="ore-documents-list__link" href="{{fileUrl}}" target="_blank">
      <i class="kmpicon kmpicon-save" title="Скачать"></i>
    </a>
  </td>
  <td class="ore-documents-list__delim">
    <i></i>
  </td>
  <td class="ore-documents-list__name">
    <label for="doc-check-{{documentId}}">
      {{fileName}}
    </label>
  </td>
  <td class="ore-documents-list__delim">
    <i></i>
  </td>
  <td class="ore-documents-list__comment">
    {{fileComment}}
  </td>
</tr>
