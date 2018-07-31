$.extend(KT.notifications, {
  'failedToGetReportsSchedule': {
    type:'error',
    title:'Не удалось загрузить расписание',
    msg:'Не удалось загрузить расписание отчетов, попробуйте перезагрузить страницу',
    killtarget:'body'
  },
  'creatingReportFailed': {
    type:'error',
    title:'Не удалось сформировать отчет',
    msg:'',
    killtarget:'body'
  },
  'creatingReportTaskFailed': {
    type:'error',
    title:'Не удалось добавить задачу',
    msg:'',
    killtarget:'body'
  },
  'reportTaskDroppingFailed': {
    type:'error',
    title:'Не удалось удалить задачу',
    msg:'',
    killtarget:'body'
  }
});
