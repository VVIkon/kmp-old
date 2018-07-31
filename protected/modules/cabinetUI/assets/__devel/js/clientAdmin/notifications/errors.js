$.extend(KT.notifications, {
  'customFieldTypeSavingFailed': {
    type:'error',
    title:'Ошибка сохранения данных',
    msg:'',
    killtarget:'body'
  },
  'userCustomFieldsSavingFailed': {
    type:'error',
    title:'Ошибка сохранения дополнительных полей',
    msg:'',
    killtarget:'body'
  },
  'travelPolicyRuleSavingFailed': {
    type:'error',
    title:'Ошибка сохранения данных правила',
    msg:'',
    killtarget:'body'
  },
  'brokenTravelPolicyRule': {
    type:'error',
    title:'Извините, но данное правило не получается отредактировать, обратитесь к менеджеру',
    msg:'',
    killtarget:'body'
  },
});
