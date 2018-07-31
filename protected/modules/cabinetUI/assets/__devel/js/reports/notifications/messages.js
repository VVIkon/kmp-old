$.extend(KT.notifications, {
  'reportCreated': {
    type:'success',
    title:'Отчет успешно сформирован',
    msg:'Отчет сформирован и отправлен на указанный адрес',
    killtarget:'body',
    timeout:1000
  },
  'reportTaskCreated': {
    type:'success',
    title:'Задача успешно создана',
    msg:'Задача по отправке отчета добавлена',
    killtarget:'body',
    timeout:1000
  },
  'reportTaskDropped': {
    type:'success',
    title:'Задача успешно удалена',
    msg:'',
    killtarget:'body',
    timeout:1000
  }
});
