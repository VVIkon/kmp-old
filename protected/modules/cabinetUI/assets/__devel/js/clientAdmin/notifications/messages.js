$.extend(KT.notifications, {
  'customFieldTypeCreated': {
    type:'success',
    title:'Новый тип создан успешно',
    msg:'Тип поля <b>{{fieldName}}</b> создан',
    killtarget:'body',
    timeout:5000
  },
  'customFieldTypeUpdated': {
    type:'success',
    title:'Параметры поля обновлены',
    msg:'Изменения поля <b>{{fieldName}}</b> сохранены',
    killtarget:'body',
    timeout:5000
  },
  'userCustomFieldsSaved': {
    type:'success',
    title:'Данные сохранены',
    msg:'Данные дополнительных полей сохранены',
    killtarget:'body',
    timeout:5000
  },
  'travelPolicyRuleCreated': {
    type:'success',
    title:'Правило создано успешно',
    msg:'Правило <b>{{ruleName}}</b> сохранено',
    killtarget:'body',
    timeout:5000
  },
  'travelPolicyRuleUpdated': {
    type:'success',
    title:'Параметры правила обновлены',
    msg:'Изменения правила <b>{{ruleName}}</b> сохранены',
    killtarget:'body',
    timeout:5000
  },
});
