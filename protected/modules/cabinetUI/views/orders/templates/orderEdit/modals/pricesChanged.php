<div class="modal-prices-changed">
<h2 class="modal-prices-changed__header">Цены предложения изменились</h2>
<table class="modal-prices-changed__pricing-table">
<tr>
    <th>&nbsp;</th>
    <th>старая цена</th>
    <th class="modal-prices-changed__arrow">&nbsp;</th>
    <th>новая цена</th>
</tr>
{{#client}}
<tr>
    <td>Цена в валюте продажи</td>
    <td>{{oldPrice}} {{currency}}</td>
    <td class="modal-prices-changed__arrow"><i class="kmpicon kmpicon-arrow-right"></i></td>
    <td>{{newPrice}} {{currency}}</td>
</tr>
{{/client}}
{{#supplier}}
<tr>
    <td>Цена нетто в валюте постащика</td>
    <td>{{oldPrice}} {{currency}}</td>
    <td class="modal-prices-changed__arrow"><i class="kmpicon kmpicon-arrow-right"></i></td>
    <td>{{newPrice}} {{currency}}</td>
</tr>
{{/supplier}}
</table>
<!-- <p class="modal-prices-changed__question">Вы согласны продолжить операцию?</p> -->
</div>